<?php

namespace App\Livewire\Biz;

use App\Livewire\Biz\Concerns\DateRange;
use App\Livewire\Biz\Concerns\InBusiness;
use Livewire\Attributes\Title;
use Livewire\Component;

/** Every account's balance as a debit or a credit. The two columns are always equal. */
#[Title('Trial balance')]
class TrialBalance extends Component
{
    use DateRange, InBusiness;

    public function render()
    {
        $rows = $this->ledger()->accounts(null, $this->to)
            ->filter(fn ($a) => $a->total_debit || $a->total_credit)
            ->map(function ($a) {
                $net = $a->total_debit - $a->total_credit;
                $a->dr = max(0, $net);
                $a->cr = max(0, -$net);

                return $a;
            })->values();

        return view('livewire.biz.trial-balance', ['rows' => $rows, 'dr' => (int) $rows->sum('dr'), 'cr' => (int) $rows->sum('cr')]);
    }
}
