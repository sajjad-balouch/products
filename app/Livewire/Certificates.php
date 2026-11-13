<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\Certificate;
use App\Models\Student;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Certificates')]
class Certificates extends Component
{
    public string $admission_no = '';

    public string $type = 'character';

    public string $remarks = '';

    public function issue(): void
    {
        $this->validate([
            'admission_no' => 'required|string',
            'type' => ['required', Rule::in(array_keys(Certificate::TYPES))],
            'remarks' => 'nullable|string|max:500',
        ]);

        $student = Student::where('admission_no', trim($this->admission_no))->first();
        if (! $student) {
            $this->addError('admission_no', 'No student has this admission number.');

            return;
        }

        $next = Certificate::count() + 1;
        $certificate = Certificate::create([
            'student_id' => $student->id,
            'type' => $this->type,
            'serial' => 'C'.now()->format('y').'-'.str_pad((string) $next, 4, '0', STR_PAD_LEFT),
            'token' => Str::random(32),
            'issued_on' => now(),
            'remarks' => $this->remarks ?: null,
            'issued_by' => Auth::id(),
        ]);
        AuditLog::record('certificate_issued', $certificate, null, null, $certificate->serial.' for '.$student->name);

        $this->reset('admission_no', 'remarks');
        $this->dispatch('toast', message: 'Certificate '.$certificate->serial.' issued.');
    }

    public function render()
    {
        return view('livewire.certificates', [
            'types' => Certificate::TYPES,
            'certificates' => Certificate::with('student')->latest('id')->limit(30)->get(),
        ]);
    }
}
