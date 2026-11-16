<?php

namespace App\Livewire\Auth;

use App\Services\SchoolProvisioner;
use App\Support\BusinessKinds;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.guest')]
#[Title('Start a free trial')]
class Signup extends Component
{
    public string $school = '';

    public string $type = 'school';

    public string $name = '';

    public string $email = '';

    public string $phone = '';

    public string $password = '';

    /** What the business sells, which sets its catalog, dashboard and menu. */
    public string $kind = '';

    /** The product pages link here with ?product=business, and can name the kind with &kind=mobile. */
    public function mount(): void
    {
        if (request('product') === 'business') {
            $this->type = 'business';
        }
        if (isset(BusinessKinds::KINDS[request('kind')])) {
            $this->type = 'business';
            $this->kind = request('kind');
        }
    }

    public function register(SchoolProvisioner $provisioner)
    {
        $data = $this->validate([
            'school' => 'required|string|max:150',
            'type' => 'required|in:school,college,both,business',
            'kind' => [Rule::requiredIf($this->type === 'business'), 'nullable', Rule::in(array_keys(BusinessKinds::KINDS))],
            'name' => 'required|string|max:120',
            'email' => 'required|email|max:150|unique:users,username',
            'phone' => 'required|string|max:20',
            'password' => 'required|string|min:8',
        ], ['kind.required' => 'Choose what your business sells.']);

        $key = 'signup:'.request()->ip();
        if (RateLimiter::tooManyAttempts($key, 3)) {
            $this->addError('school', 'Too many sign-ups from this connection. Please try again later.');

            return null;
        }
        RateLimiter::hit($key, 3600);

        $user = $provisioner->create($data, starterData: true);
        Auth::login($user);
        session()->regenerate();

        return redirect()->route($user->homeRoute());
    }

    public function render()
    {
        return view('livewire.auth.signup');
    }
}
