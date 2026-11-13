<?php

namespace App\Support;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/** Lets the software owner use the system as one of a school's logins, and come back. */
class Impersonation
{
    private const KEY = 'impersonator_id';

    public static function start(User $owner, User $target): void
    {
        abort_unless($owner->isRole('super_admin') && $target->school_id && ! $target->isRole('super_admin'), 403);

        AuditLog::record('login_as_started', $target, null, null, $owner->name.' signed in as '.$target->username);
        session()->regenerate();
        Auth::login($target);
        session()->put(self::KEY, $owner->id);
    }

    public static function active(): bool
    {
        return session()->has(self::KEY);
    }

    public static function owner(): ?User
    {
        return self::active() ? User::find(session(self::KEY)) : null;
    }

    /** Returns the school the owner was looking at, to go back to it. */
    public static function stop(): ?int
    {
        $owner = self::owner();
        $schoolId = Auth::user()?->school_id;
        session()->forget(self::KEY);

        if (! $owner?->isRole('super_admin')) {
            return null;
        }

        AuditLog::record('login_as_ended', Auth::user(), null, null, $owner->name.' returned to the owner panel');
        session()->regenerate();
        Auth::login($owner);

        return $schoolId;
    }
}
