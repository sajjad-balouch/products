<?php

namespace App\Livewire\Platform;

use App\Livewire\Platform\Concerns\OwnerOnly;
use App\Models\AuditLog;
use App\Models\School;
use App\Models\Student;
use App\Services\SchoolProvisioner;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/** Every customer school, with a quick way to add one by hand. */
#[Title('Schools')]
class Schools extends Component
{
    use OwnerOnly;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    public bool $showForm = false;

    public string $school = '';

    public string $type = 'school';

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $monthlyFee = '';

    public string $freeDays = '30';

    public ?array $issued = null;

    public function toggle(int $id): void
    {
        $school = School::findOrFail($id);
        $school->update(['is_active' => ! $school->is_active]);
        AuditLog::record($school->is_active ? 'school_activated' : 'school_suspended', $school, null, null, $school->name);
    }

    public function create(SchoolProvisioner $provisioner): void
    {
        $this->validate([
            'school' => 'required|string|max:150',
            'type' => 'required|in:school,college,both',
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:150|unique:users,username',
            'phone' => 'required|string|max:30',
            'monthlyFee' => 'nullable|integer|min:0|max:10000000',
            'freeDays' => 'required|integer|min:0|max:3650',
        ], [], ['school' => 'school name', 'name' => 'admin name', 'monthlyFee' => 'monthly fee', 'freeDays' => 'free days']);

        $password = Str::password(10, symbols: false);
        $admin = $provisioner->create(['school' => $this->school, 'type' => $this->type, 'name' => $this->name, 'email' => $this->email, 'phone' => $this->phone, 'password' => $password]);
        $admin->update(['must_change_password' => true]);
        $until = now()->addDays((int) $this->freeDays);
        $admin->school->update(['monthly_fee' => (int) $this->monthlyFee, 'trial_ends_at' => $until, 'access_until' => $until]);

        AuditLog::record('school_created', $admin->school, null, null, $admin->school->name.' added by the owner');
        $this->issued = ['school' => $admin->school->name, 'username' => $admin->username, 'password' => $password, 'id' => $admin->school_id];
        $this->reset('showForm', 'school', 'name', 'email', 'phone', 'monthlyFee');
        $this->freeDays = '30';
    }

    public function render()
    {
        $students = Student::withoutGlobalScopes()->where('status', 'active')->selectRaw('school_id, count(*) as total')->groupBy('school_id')->pluck('total', 'school_id');

        $schools = School::latest()->get()
            ->filter(fn ($s) => $this->search === '' || Str::contains(Str::lower($s->name.' '.$s->phone.' '.$s->email.' '.$s->slug), Str::lower($this->search)))
            ->filter(fn ($s) => $this->status === '' || $s->status() === $this->status)
            ->values();

        return view('livewire.platform.schools', ['schools' => $schools, 'students' => $students]);
    }
}
