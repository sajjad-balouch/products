<?php

namespace App\Http\Middleware;

use App\Support\Impersonation;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Blocks disabled users and suspended schools, locks schools whose access has run out,
 * switches off features the owner turned off, and enforces first-login password change.
 * The owner using a school's login skips the account checks, so they can fix the school.
 */
class EnsureAccountActive
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        $asOwner = $user && Impersonation::active();

        if ($user && ! $asOwner && ($user->is_active === false || ($user->school_id && $user->school?->is_active === false))) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors(['username' => __('This account has been disabled. Please contact the school office.')]);
        }

        $school = $user?->school;
        $route = $request->route()?->getName();
        $always = ['logout', 'password.change', 'subscription.expired', 'impersonation.stop', 'locale'];

        if ($school && ! $asOwner && $school->isLocked() && ! in_array($route, $always, true)) {
            return redirect()->route('subscription.expired');
        }

        if ($school && ! $school->allowsRoute($route)) {
            abort(403, 'This feature is switched off for your school.');
        }

        if ($user?->must_change_password && ! $asOwner && ! $request->routeIs('password.change', 'logout') && ! $request->is('livewire*')) {
            return redirect()->route('password.change');
        }

        return $next($request);
    }
}
