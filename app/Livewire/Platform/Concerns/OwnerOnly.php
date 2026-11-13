<?php

namespace App\Livewire\Platform\Concerns;

use Illuminate\Support\Facades\Auth;

/** Checked on every request to the component, not only by the route, so no action can be called by anyone else. */
trait OwnerOnly
{
    public function bootOwnerOnly(): void
    {
        abort_unless(Auth::user()?->isRole('super_admin'), 403);
    }
}
