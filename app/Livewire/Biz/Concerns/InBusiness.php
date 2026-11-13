<?php

namespace App\Livewire\Biz\Concerns;

use App\Models\School;
use App\Services\Biz\Ledger;
use Illuminate\Support\Facades\Auth;

/** Shared by the business screens. The route middleware already keeps schools out. */
trait InBusiness
{
    public function bootInBusiness(): void
    {
        abort_unless(Auth::user()?->school?->isBusiness(), 403);
    }

    protected function business(): School
    {
        return Auth::user()->school;
    }

    protected function ledger(): Ledger
    {
        return Ledger::for($this->business());
    }

    /** Shows a service's "you can't do that" message under the right field instead of an error page. */
    protected function guarded(callable $action, string $field = 'form'): mixed
    {
        try {
            return $action();
        } catch (\InvalidArgumentException $e) {
            $this->addError($field, $e->getMessage());

            return null;
        }
    }
}
