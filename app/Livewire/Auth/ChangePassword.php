<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.guest')]
#[Title('Change password')]
class ChangePassword extends Component
{
    public string $current = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function save()
    {
        $this->validate([
            'current' => 'required|current_password',
            'password' => 'required|string|min:8|confirmed|different:current',
        ], [], ['current' => 'current password', 'password' => 'new password']);

        $user = Auth::user();
        $user->update(['password' => Hash::make($this->password), 'must_change_password' => false]);

        return redirect()->route($user->homeRoute());
    }

    public function render()
    {
        return view('livewire.auth.change-password');
    }
}
