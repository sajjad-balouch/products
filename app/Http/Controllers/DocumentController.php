<?php

namespace App\Http\Controllers;

use App\Livewire\Fees\Defaulters;
use App\Models\Attendance;
use App\Models\Certificate;
use App\Models\Exam;
use App\Models\FeeVoucher;
use App\Models\Guardian;
use App\Models\Payment;
use App\Models\PaymentProof;
use App\Models\Payroll;
use App\Models\PickupPerson;
use App\Models\Section;
use App\Models\Student;
use App\Services\FeeService;
use App\Services\ResultService;
use App\Services\StudentImporter;
use App\Support\IdCards;
use App\Support\Qr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

/** Printable documents. Tenant scope limits every lookup; family checks limit parents to their own. */
class DocumentController extends Controller
{
    public function voucher(FeeService $fees, int $id)
    {
        $voucher = FeeVoucher::with(['guardian', 'lines.student.section.schoolClass', 'school'])->findOrFail($id);
        $this->ensureOffice($voucher->guardian_id);

        return view('print.voucher', ['voucher' => $voucher, 'school' => $voucher->school, 'arrears' => $fees->arrears($voucher)]);
    }

    public function receipt(int $id)
    {
        $payment = Payment::with(['voucher.guardian', 'voucher.lines.student', 'receivedBy', 'school'])->findOrFail($id);
        $this->ensureOffice($payment->voucher->guardian_id);

        return view('print.receipt', ['payment' => $payment, 'school' => $payment->school]);
    }

    public function reportCard(ResultService $results, int $exam, int $student)
    {
        $exam = Exam::findOrFail($exam);
        $student = Student::with(['section.schoolClass', 'school'])->findOrFail($student);
        $user = Auth::user();

        if ($user->isRole('parent', 'student')) {
            $this->ensureOffice($student->guardian_id);
            abort_unless($exam->is_published, 403);
        } else {
            abort_unless($user->isRole('admin', 'accountant', 'teacher'), 403);
        }

        $row = $results->classResult($exam, $student->section->school_class_id)->first(fn ($r) => $r['student']->id === $student->id);
        abort_unless($row, 404);

        $attendance = Attendance::where('student_id', $student->id)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');

        return view('print.report-card', ['exam' => $exam, 'student' => $student, 'row' => $row, 'school' => $student->school, 'attendance' => $attendance]);
    }

    public function proofImage(int $id)
    {
        $proof = PaymentProof::findOrFail($id);
        $this->ensureOffice($proof->guardian_id);
        abort_unless(Storage::disk('local')->exists($proof->image_path), 404);

        return Storage::disk('local')->response($proof->image_path);
    }

    public function payslip(int $id)
    {
        $slip = Payroll::with(['staff', 'school'])->findOrFail($id);

        return view('print.payslip', ['slip' => $slip, 'school' => $slip->school]);
    }

    public function certificate(int $id)
    {
        $certificate = Certificate::with(['student.section.schoolClass', 'school'])->findOrFail($id);

        return view('print.certificate', [
            'certificate' => $certificate,
            'school' => $certificate->school,
            'qr' => Qr::svg(route('certificate.verify', $certificate->token)),
        ]);
    }

    /** The old one-class card list now opens the card designer for that class. */
    public function idCards(int $section)
    {
        return redirect()->route('cards', ['section' => Section::findOrFail($section)->id]);
    }

    /** Cards on A4 sheets or one per page for a PVC card printer, with the same choices as the cards screen. */
    public function cards(Request $request)
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(array_keys(IdCards::TYPES))],
            'layout' => ['nullable', Rule::in(['a4', 'card'])],
            'download' => ['nullable', Rule::in(['a4', 'card'])],
            'section' => 'nullable|integer',
            'search' => 'nullable|string|max:100',
            // Card keys such as student:12, guardian:4 or pickup:9.
            'excluded' => ['nullable', 'string', 'max:30000', 'regex:/^[a-z0-9:,]*$/'],
            'only' => ['nullable', 'string', 'regex:/^(student|guardian|pickup):[0-9]+$/'],
        ]);

        $school = Auth::user()->school;
        $excluded = array_filter(explode(',', $data['excluded'] ?? ''));
        $people = IdCards::people($data['type'], isset($data['section']) ? (int) $data['section'] : null, $data['search'] ?? '')
            ->when($data['only'] ?? null, fn ($people, $key) => $people->filter(fn ($person) => $person->cardKey() === $key))
            ->reject(fn ($person) => in_array($person->cardKey(), $excluded, true));

        return view('print.cards', [
            'school' => $school,
            'type' => $data['type'],
            'layout' => $data['download'] ?? $data['layout'] ?? 'a4',
            'download' => isset($data['download']),
            's' => IdCards::settings($school),
            'cards' => $people->map(fn ($person) => IdCards::card($person, $school))->values(),
        ] + IdCards::assets($school));
    }

    public function cardPhoto(string $type, int $id)
    {
        $person = match ($type) {
            'guardian' => Guardian::findOrFail($id),
            'pickup' => PickupPerson::findOrFail($id),
            default => Student::findOrFail($id),
        };
        abort_unless($person->photo_path && Storage::disk('local')->exists($person->photo_path), 404);

        return Storage::disk('local')->response($person->photo_path, null, ['Cache-Control' => 'private, max-age=604800']);
    }

    public function cardAsset(string $kind)
    {
        $school = Auth::user()->school;
        $path = $kind === 'logo' ? IdCards::logoPath($school) : IdCards::settings($school)['signature_path'];
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, ['Cache-Control' => 'private, max-age=604800']);
    }

    /** Public: shows just enough to confirm a certificate is real. */
    public function verify(string $token)
    {
        $certificate = Certificate::withoutGlobalScopes()->with([
            'student' => fn ($q) => $q->withoutGlobalScopes(),
            'school',
        ])->where('token', $token)->first();

        return response()->view('print.verify', ['certificate' => $certificate], $certificate ? 200 : 404);
    }

    public function defaultersCsv()
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // lets Excel read it as UTF-8
            fputcsv($out, ['Guardian', 'Phone', 'Children', '0-30 days', '31-60 days', '60+ days', 'Total']);

            foreach (Defaulters::report() as $row) {
                fputcsv($out, [
                    $this->csvSafe($row['guardian']->name),
                    $this->csvSafe($row['guardian']->phone),
                    $this->csvSafe($row['guardian']->students->pluck('name')->join(', ')),
                    $row['d30'], $row['d60'], $row['d90'], $row['total'],
                ]);
            }
            fclose($out);
        }, 'defaulters-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function importTemplate()
    {
        return response()->streamDownload(function () {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, StudentImporter::COLUMNS);
            fputcsv($out, ['', 'Ali Raza', 'Muhammad Raza', '03001234567', 'Class 5', 'A', 'male', '14/08/2015', '12', 'Street 4, Multan', '0']);
            fclose($out);
        }, 'students-template.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /** Parents may open only their own family's documents; office staff may open any in their school. */
    private function ensureOffice(int $guardianId): void
    {
        $user = Auth::user();

        if ($user->isRole('parent', 'student')) {
            abort_unless(Guardian::where('user_id', $user->id)->whereKey($guardianId)->exists(), 403);

            return;
        }

        abort_unless($user->isRole('admin', 'accountant'), 403);
    }

    /** Stops Excel running a name like =cmd() as a formula. */
    private function csvSafe(?string $value): string
    {
        return preg_match('/^[=+\-@\t\r]/', (string) $value) ? "'".$value : (string) $value;
    }
}
