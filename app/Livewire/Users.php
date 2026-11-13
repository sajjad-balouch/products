<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('User logins')]
class Users extends Component
{
    public string $name = '';

    public string $email = '';

    public string $role = 'teacher';

    public string $staff_id = '';

    public ?array $issued = null;

    public function create(): void
    {
        $schoolId = Auth::user()->school_id;

        $this->validate([
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:150|unique:users,username',
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
            'staff_id' => ['nullable', Rule::exists('staff', 'id')->where('school_id', $schoolId)],
        ]);

        $password = Str::password(10, symbols: false);
        $email = Str::lower($this->email);

        $user = User::create([
            'school_id' => $schoolId,
            'name' => $this->name,
            'username' => $email,
            'email' => $email,
            'role' => $this->role,
            'password' => Hash::make($password),
            'must_change_password' => true,
        ]);

        if ($this->staff_id) {
            Staff::whereKey($this->staff_id)->update(['user_id' => $user->id]);
        }

        AuditLog::record('login_created', $user, null, null, $email.' as '.$this->role);
        $this->issued = ['username' => $email, 'password' => $password];
        $this->reset('name', 'email', 'staff_id');
    }

    public function resetPassword(int $id): void
    {
        $user = $this->managed($id);
        $password = Str::password(10, symbols: false);
        $user->update(['password' => Hash::make($password), 'must_change_password' => true]);
        AuditLog::record('password_reset', $user, null, null, $user->username);
        $this->issued = ['username' => $user->username, 'password' => $password];
    }

    public function toggle(int $id): void
    {
        $user = $this->managed($id);
        abort_if($user->id === Auth::id(), 422);
        $user->update(['is_active' => ! $user->is_active]);
        AuditLog::record($user->is_active ? 'login_enabled' : 'login_disabled', $user, null, null, $user->username);
    }

    /** User has no tenant scope of its own, so this lookup must stay inside the school. */
    private function managed(int $id): User
    {
        return User::where('school_id', Auth::user()->school_id)->findOrFail($id);
    }

    public function render()
    {
        return view('livewire.users', [
            'users' => User::where('school_id', Auth::user()->school_id)->whereIn('role', array_keys(User::ROLES))->orderBy('name')->get(),
            'roles' => User::ROLES,
            'staff' => Staff::where('status', 'active')->whereNull('user_id')->orderBy('name')->get(),
        ]);
    }
}
