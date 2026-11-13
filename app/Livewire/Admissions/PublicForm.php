<?php

namespace App\Livewire\Admissions;

use App\Models\Inquiry;
use App\Models\School;
use App\Models\SchoolClass;
use App\Support\Phone;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.guest')]
#[Title('Online admission')]
class PublicForm extends Component
{
    #[Locked]
    public int $schoolId;

    public string $student_name = '';

    public string $guardian_name = '';

    public string $phone = '';

    public string $school_class_id = '';

    public string $notes = '';

    // Hidden from people. Bots that fill it are ignored.
    public string $website = '';

    public bool $done = false;

    public function mount(string $slug): void
    {
        $school = School::where('slug', $slug)->where('is_active', true)->firstOrFail();
        abort_unless($school->hasFeature('admissions') && ! $school->isLocked(), 404);
        $this->schoolId = $school->id;
    }

    public function submit(): void
    {
        $data = $this->validate([
            'student_name' => 'required|string|max:120',
            'guardian_name' => 'required|string|max:120',
            'phone' => 'required|string|max:20',
            'school_class_id' => ['required', Rule::exists('school_classes', 'id')->where('school_id', $this->schoolId)],
            'notes' => 'nullable|string|max:500',
        ]);

        $key = 'apply:'.request()->ip();
        if ($this->website !== '' || RateLimiter::tooManyAttempts($key, 5)) {
            $this->done = true;

            return;
        }
        RateLimiter::hit($key, 3600);

        Inquiry::create($data + ['school_id' => $this->schoolId, 'source' => 'website']);
        $this->done = true;
    }

    public function render()
    {
        return view('livewire.admissions.public-form', [
            'school' => School::findOrFail($this->schoolId),
            'classes' => SchoolClass::withoutGlobalScopes()->where('school_id', $this->schoolId)->orderBy('sort')->get(),
        ]);
    }

    public function updatedPhone(): void
    {
        $this->phone = Phone::normalize($this->phone);
    }
}
