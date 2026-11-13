<?php

namespace App\Livewire\Biz;

use App\Livewire\Biz\Concerns\DateRange;
use App\Livewire\Biz\Concerns\InBusiness;
use App\Models\Biz\Contract;
use App\Models\Biz\Invoice;
use App\Models\Biz\InvoiceLine;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * Profit of each sale against the cost of the very pieces sold: installment sales (trading profit
 * plus markup) and cash / wholesale sales. The same product bought at different prices gives different profits.
 */
#[Title('Profit on sales')]
class SalesProfit extends Component
{
    use DateRange, InBusiness;

    public function render()
    {
        $sales = Contract::with(['customer', 'product', 'stockUnit.purchase'])
            ->whereBetween('sale_date', [$this->from, $this->to])->orderBy('sale_date')->orderBy('id')->get();
        $lines = InvoiceLine::with(['product', 'invoice.customer'])
            ->whereHas('invoice', fn ($q) => $q->whereNull('voided_at')->whereBetween('date', [$this->from, $this->to]))
            ->orderBy('invoice_id')->get();

        $known = $sales->whereNotNull('cost_price');
        $rows = $known->map(fn ($c) => ['name' => $c->product?->label() ?? $c->item, 'count' => 1, 'sales' => $c->cash_price, 'cost' => $c->cost_price, 'markup' => $c->markup, 'profit' => $c->grossProfit()])
            ->concat($lines->map(fn ($l) => ['name' => $l->product->label(), 'count' => $l->quantity, 'sales' => $l->total, 'cost' => $l->cost, 'markup' => 0, 'profit' => $l->total - $l->cost]));
        $byProduct = $rows->groupBy('name')->map(fn ($g) => [
            'count' => (int) $g->sum('count'), 'sales' => (int) $g->sum('sales'), 'cost' => (int) $g->sum('cost'), 'markup' => (int) $g->sum('markup'), 'profit' => (int) $g->sum('profit'),
        ])->sortByDesc('profit');

        // Invoice discounts lower the profit of the bill as a whole.
        $discounts = (int) Invoice::whereNull('voided_at')->whereBetween('date', [$this->from, $this->to])->sum('discount');

        return view('livewire.biz.sales-profit', [
            'sales' => $sales,
            'lines' => $lines,
            'byProduct' => $byProduct,
            'discounts' => $discounts,
            'totals' => [
                'cash' => (int) $rows->sum('sales'),
                'cost' => (int) $rows->sum('cost'),
                'markup' => (int) $rows->sum('markup'),
                'profit' => (int) $rows->sum('profit') - $discounts,
            ],
            'unknown' => $sales->whereNull('cost_price')->count(),
        ]);
    }
}
