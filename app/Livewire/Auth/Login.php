<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.guest')]
#[Title('Sign in')]
class Login extends Component
{
    public string $username = '';

    public string $password = '';

    public bool $remember = false;

    public function login()
    {
        $this->validate(['username' => 'required|string|max:120', 'password' => 'required|string']);

        $key = 'login:'.Str::lower($this->username).'|'.request()->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            $this->addError('username', 'Too many attempts. Please try again in '.RateLimiter::availableIn($key).' seconds.');

            return null;
        }

        if (! Auth::attempt(['username' => Str::lower(trim($this->username)), 'password' => $this->password, 'is_active' => true], $this->remember)) {
            RateLimiter::hit($key, 60);
            $this->addError('username', 'These details do not match our records.');

            return null;
        }

        RateLimiter::clear($key);
        Auth::user()->forceFill(['last_login_at' => now()])->save();
        session()->regenerate();

        /** @var User $user */
        $user = Auth::user();

        return redirect()->route($user->must_change_password ? 'password.change' : $user->homeRoute());
    }

    public function render()
    {
        return view('livewire.auth.login');
    }
}
