<?php

namespace App\Livewire\Biz;

use App\Livewire\Biz\Concerns\DateRange;
use App\Livewire\Biz\Concerns\InBusiness;
use App\Models\Biz\Account;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/** One account's entries with the running balance: the general ledger. Cash in hand gives the cash book. */
#[Title('Ledger')]
class LedgerReport extends Component
{
    use DateRange, InBusiness;

    #[Url]
    public ?int $account = null;

    public function render()
    {
        $accounts = Account::orderBy('code')->get();
        $chosen = $accounts->firstWhere('id', $this->account) ?? $accounts->firstWhere('system_key', 'cash') ?? $accounts->first();

        return view('livewire.biz.ledger', [
            'accounts' => $accounts,
            'chosen' => $chosen,
            'statement' => $chosen ? $this->ledger()->statement($chosen, $this->from, $this->to) : null,
        ]);
    }
}
