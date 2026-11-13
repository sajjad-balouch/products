<?php

namespace App\Livewire\Students;

use App\Models\Guardian;
use App\Models\Inquiry;
use App\Models\Section;
use App\Models\Student;
use App\Models\TransportRoute;
use App\Services\StudentImporter;
use App\Support\Phone;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Student')]
class Form extends Component
{
    public ?Student $student = null;

    #[Url]
    public ?int $inquiry = null;

    public string $name = '';

    public string $father_name = '';

    public string $gender = 'male';

    public ?string $dob = null;

    public string $b_form = '';

    public string $section_id = '';

    public string $roll_no = '';

    public string $transport_route_id = '';

    public int $monthly_concession = 0;

    public string $concession_reason = '';

    public string $status = 'active';

    public string $phone = '';

    public string $guardian_name = '';

    public string $cnic = '';

    public string $address = '';

    public function mount(?Student $student = null): void
    {
        if ($student?->exists) {
            $this->student = $student;
            $this->fill($student->only(['name', 'gender', 'status', 'monthly_concession']));
            $this->father_name = (string) $student->father_name;
            $this->dob = $student->dob?->toDateString();
            $this->b_form = (string) $student->b_form;
            $this->section_id = (string) $student->section_id;
            $this->roll_no = (string) $student->roll_no;
            $this->transport_route_id = (string) $student->transport_route_id;
            $this->concession_reason = (string) $student->concession_reason;
            $this->phone = $student->guardian->phone;
            $this->guardian_name = $student->guardian->name;
            $this->cnic = (string) $student->guardian->cnic;
            $this->address = (string) $student->guardian->address;
        } elseif ($this->inquiry && ($inquiry = Inquiry::find($this->inquiry))) {
            $this->name = $inquiry->student_name;
            $this->guardian_name = (string) $inquiry->guardian_name;
            $this->father_name = (string) $inquiry->guardian_name;
            $this->phone = $inquiry->phone;
        }
    }

    /** Typing a known phone shows the family so siblings link instead of duplicating. */
    #[Computed]
    public function existingFamily(): ?Guardian
    {
        $phone = Phone::normalize($this->phone);

        return strlen($phone) >= 10 && ! $this->student
            ? Guardian::with('students')->where('phone', $phone)->first()
            : null;
    }

    public function save()
    {
        $schoolId = Auth::user()->school_id;

        $data = $this->validate([
            'name' => 'required|string|max:120',
            'father_name' => 'nullable|string|max:120',
            'gender' => 'required|in:male,female',
            'dob' => 'nullable|date|before:today',
            'b_form' => 'nullable|string|max:20',
            'section_id' => ['required', Rule::exists('sections', 'id')->where('school_id', $schoolId)],
            'roll_no' => 'nullable|string|max:20',
            'transport_route_id' => ['nullable', Rule::exists('transport_routes', 'id')->where('school_id', $schoolId)],
            'monthly_concession' => 'required|integer|min:0',
            'concession_reason' => 'nullable|string|max:120',
            'status' => 'required|in:active,left,graduated',
            'phone' => 'required|string|max:20',
            'guardian_name' => 'required|string|max:120',
            'cnic' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:190',
        ]);

        $phone = Phone::normalize($data['phone']);
        if (strlen($phone) < 10) {
            $this->addError('phone', 'Enter a valid mobile number.');

            return null;
        }

        $student = DB::transaction(function () use ($data, $phone, $schoolId) {
            $guardian = Guardian::firstOrNew(['school_id' => $schoolId, 'phone' => $phone]);
            $guardian->fill(['name' => $data['guardian_name'], 'cnic' => $data['cnic'] ?: $guardian->cnic, 'address' => $data['address'] ?: $guardian->address])->save();

            $values = [
                'name' => $data['name'],
                'father_name' => $data['father_name'] ?: null,
                'gender' => $data['gender'],
                'dob' => $data['dob'] ?: null,
                'b_form' => $data['b_form'] ?: null,
                'section_id' => $data['section_id'],
                'roll_no' => $data['roll_no'] ?: null,
                'transport_route_id' => $data['transport_route_id'] ?: null,
                'monthly_concession' => $data['monthly_concession'],
                'concession_reason' => $data['concession_reason'] ?: null,
                'status' => $data['status'],
                'left_on' => $data['status'] === 'left' ? ($this->student?->left_on ?? now()) : null,
                'guardian_id' => $guardian->id,
            ];

            if ($this->student) {
                $this->student->update($values);

                return $this->student;
            }

            $student = Student::create($values + ['admission_no' => StudentImporter::nextAdmissionNo($schoolId), 'admitted_on' => now()]);

            if ($this->inquiry) {
                Inquiry::where('id', $this->inquiry)->update(['stage' => 'admitted', 'student_id' => $student->id]);
            }

            return $student;
        });

        return redirect()->route('students.show', $student);
    }

    public function render()
    {
        return view('livewire.students.form', [
            'sections' => Section::with('schoolClass')->get()->sortBy(fn ($s) => [$s->schoolClass->sort, $s->name]),
            'routes' => TransportRoute::orderBy('name')->get(),
        ]);
    }
}
