<?php

namespace App\Livewire\Biz;

use App\Livewire\Biz\Concerns\DateRange;
use App\Livewire\Biz\Concerns\InBusiness;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Balance sheet')]
class BalanceSheet extends Component
{
    use DateRange, InBusiness;

    public function render()
    {
        return view('livewire.biz.balance-sheet', $this->ledger()->balanceSheet($this->to));
    }
}
