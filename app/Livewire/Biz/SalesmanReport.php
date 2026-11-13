<?php

namespace App\Livewire\Biz;

use App\Livewire\Biz\Concerns\DateRange;
use App\Livewire\Biz\Concerns\InBusiness;
use App\Models\Biz\Employee;
use App\Models\Biz\Invoice;
use App\Models\Biz\Contract;
use Livewire\Attributes\Title;
use Livewire\Component;

/** What each salesman sold in the dates chosen, the commission earned on it, and what was paid. */
#[Title('Salesman report')]
class SalesmanReport extends Component
{
    use DateRange, InBusiness;

    public function render()
    {
        $rows = Employee::where('is_salesman', true)->orderBy('name')->get()->map(function (Employee $e) {
            $s = $e->salesBetween($this->from, $this->to);
            $paid = $e->paid('commission', $this->from, $this->to);

            return ['e' => $e] + $s + ['paid' => $paid, 'due' => max(0, $s['commission'] - $paid)];
        });
        $unassigned = (int) Invoice::whereNull('salesman_id')->whereNull('voided_at')->whereBetween('date', [$this->from, $this->to])->sum('total')
            + (int) Contract::whereNull('salesman_id')->whereBetween('sale_date', [$this->from, $this->to])->sum('cash_price');

        return view('livewire.biz.salesman-report', ['rows' => $rows, 'unassigned' => $unassigned]);
    }
}
