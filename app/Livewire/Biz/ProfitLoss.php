<?php

namespace App\Livewire\Biz;

use App\Livewire\Biz\Concerns\DateRange;
use App\Livewire\Biz\Concerns\InBusiness;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Profit and loss')]
class ProfitLoss extends Component
{
    use DateRange, InBusiness;

    public function render()
    {
        return view('livewire.biz.profit-loss', $this->ledger()->profitLoss($this->from, $this->to));
    }
}
