<?php

namespace App\Livewire\Admissions;

use App\Models\Inquiry;
use App\Models\SchoolClass;
use App\Support\Phone;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Admissions pipeline')]
class Board extends Component
{
    public bool $showForm = false;

    public string $student_name = '';

    public string $guardian_name = '';

    public string $phone = '';

    public string $school_class_id = '';

    public string $source = 'walk-in';

    public ?string $follow_up_on = null;

    public string $notes = '';

    public function save(): void
    {
        $data = $this->validate([
            'student_name' => 'required|string|max:120',
            'guardian_name' => 'nullable|string|max:120',
            'phone' => 'required|string|max:20',
            'school_class_id' => ['nullable', Rule::exists('school_classes', 'id')->where('school_id', Auth::user()->school_id)],
            'source' => 'required|in:walk-in,phone,facebook,whatsapp,referral,website',
            'follow_up_on' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        $data['phone'] = Phone::normalize($data['phone']);
        $data['school_class_id'] = $data['school_class_id'] ?: null;
        $data['follow_up_on'] = $data['follow_up_on'] ?: null;

        Inquiry::create($data);
        $this->reset('showForm', 'student_name', 'guardian_name', 'phone', 'school_class_id', 'source', 'follow_up_on', 'notes');
        $this->dispatch('toast', message: 'Inquiry added.');
    }

    public function move(int $id, string $stage): void
    {
        abort_unless(array_key_exists($stage, Inquiry::STAGES), 422);
        Inquiry::findOrFail($id)->update(['stage' => $stage]);
    }

    public function followUp(int $id, ?string $date): void
    {
        Inquiry::findOrFail($id)->update(['follow_up_on' => $date ?: null]);
    }

    public function render()
    {
        $inquiries = Inquiry::with('schoolClass')->latest()->get()->groupBy('stage');
        $total = $inquiries->flatten()->count();
        $admitted = $inquiries->get('admitted', collect())->count();

        return view('livewire.admissions.board', [
            'inquiries' => $inquiries,
            'stages' => Inquiry::STAGES,
            'classes' => SchoolClass::orderBy('sort')->get(),
            'conversion' => $total ? round($admitted / $total * 100) : 0,
            'publicLink' => route('apply', Auth::user()->school->slug),
        ]);
    }
}
