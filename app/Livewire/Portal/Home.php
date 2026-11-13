<?php

namespace App\Livewire\Portal;

use App\Models\Announcement;
use App\Models\Attendance;
use App\Models\Diary;
use App\Models\Exam;
use App\Models\FeeVoucher;
use App\Models\Guardian;
use App\Models\PaymentProof;
use App\Models\TimetableEntry;
use App\Services\ResultService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;

/** Everything here starts from the signed-in parent's own family, never from an ID in the request. */
#[Title('Parent portal')]
class Home extends Component
{
    use WithFileUploads;

    #[Url]
    public string $tab = 'today';

    public ?int $childId = null;

    public ?int $payVoucherId = null;

    public int $amount = 0;

    public string $method = 'jazzcash';

    public string $reference = '';

    public $screenshot = null;

    private function guardian(): Guardian
    {
        $guardian = Guardian::where('user_id', Auth::id())->first();
        abort_unless($guardian, 403, 'This login is not linked to a family.');

        return $guardian;
    }

    private function children()
    {
        return $this->guardian()->students()->where('status', 'active')->with('section.schoolClass')->orderBy('name')->get();
    }

    public function startPay(int $voucherId): void
    {
        $voucher = FeeVoucher::where('guardian_id', $this->guardian()->id)->open()->findOrFail($voucherId);
        $this->resetValidation();
        $this->payVoucherId = $voucher->id;
        $this->amount = $voucher->balance;
        $this->reference = '';
        $this->screenshot = null;
    }

    public function submitProof(): void
    {
        $guardian = $this->guardian();
        $voucher = FeeVoucher::where('guardian_id', $guardian->id)->open()->findOrFail($this->payVoucherId);

        $this->validate([
            'amount' => 'required|integer|min:1|max:'.$voucher->balance,
            'method' => 'required|in:jazzcash,easypaisa,bank,raast',
            'reference' => 'required|string|min:4|max:100',
            'screenshot' => 'required|image|max:5120',
        ], [], ['reference' => __('transaction ID'), 'screenshot' => __('screenshot'), 'amount' => __('amount')]);

        $path = $this->screenshot->storeAs('proofs/'.$guardian->school_id, Str::random(40).'.'.$this->screenshot->extension(), 'local');

        PaymentProof::create([
            'fee_voucher_id' => $voucher->id,
            'guardian_id' => $guardian->id,
            'amount' => $this->amount,
            'method' => $this->method,
            'reference' => trim($this->reference),
            'image_path' => $path,
        ]);

        $this->reset('payVoucherId', 'screenshot', 'reference');
        $this->dispatch('toast', message: __('Sent. The school will confirm your payment soon.'));
    }

    public function render(ResultService $results)
    {
        $guardian = $this->guardian();
        $children = $this->children();
        $child = $children->firstWhere('id', $this->childId) ?? $children->first();
        $this->childId = $child?->id;

        $data = [
            'school' => Auth::user()->school,
            'children' => $children,
            'child' => $child,
            'vouchers' => FeeVoucher::where('guardian_id', $guardian->id)->where('status', '!=', 'void')->with(['lines.student', 'proofs', 'payments'])->latest('month')->limit(12)->get(),
            'payVoucher' => $this->payVoucherId ? FeeVoucher::where('guardian_id', $guardian->id)->find($this->payVoucherId) : null,
            'announcements' => Announcement::latest('published_on')->limit(5)->get(),
            'diary' => collect(),
            'attendance' => collect(),
            'attendanceStats' => ['percent' => null, 'absent' => 0],
            'results' => collect(),
            'todayStatus' => null,
            'timetable' => collect(),
        ];

        if ($child) {
            $monthly = Attendance::where('student_id', $child->id)->whereDate('date', '>=', now()->subDays(30))->latest('date')->get();
            $data['attendance'] = $monthly;
            $data['todayStatus'] = $monthly->first(fn ($a) => $a->date->isToday())?->status;
            $data['attendanceStats'] = [
                'percent' => $monthly->count() ? round($monthly->whereIn('status', ['P', 'L'])->count() / $monthly->count() * 100) : null,
                'absent' => $monthly->where('status', 'A')->count(),
            ];
            $data['diary'] = Diary::with('subject')->where('section_id', $child->section_id)->latest('date')->latest('id')->limit(10)->get();
            $data['timetable'] = TimetableEntry::live()->with(['period', 'subject'])->where('section_id', $child->section_id)->where('day', now()->dayOfWeekIso)->get()->sortBy(fn ($e) => $e->period->starts_at);

            // Parents see published results only.
            $data['results'] = Exam::where('is_published', true)->latest('starts_on')->limit(5)->get()
                ->map(function ($exam) use ($results, $child) {
                    $row = $results->classResult($exam, $child->section->school_class_id)->first(fn ($r) => $r['student']->id === $child->id);

                    return $row && $row['total'] > 0 ? compact('exam', 'row') : null;
                })->filter()->values();
        }

        $data['balance'] = $data['vouchers']->whereIn('status', ['unpaid', 'partial'])->sum('balance');

        return view('livewire.portal.home', $data);
    }
}
