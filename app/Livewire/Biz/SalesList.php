<?php

namespace App\Livewire\Biz;

use App\Livewire\Biz\Concerns\DateRange;
use App\Livewire\Biz\Concerns\InBusiness;
use App\Models\Biz\Customer;
use App\Models\Biz\Invoice;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Sales')]
class SalesList extends Component
{
    use DateRange, InBusiness;

    #[Url]
    public string $search = '';

    #[Url]
    public bool $unpaid = false;

    public function render()
    {
        $term = trim($this->search);
        $invoices = Invoice::with('customer')
            ->when(! $this->unpaid, fn ($q) => $q->whereBetween('date', [$this->from, $this->to]))
            ->when($this->unpaid, fn ($q) => $q->whereNull('voided_at')->whereColumn('paid', '<', 'total'))
            ->when($term !== '', fn ($q) => $q->where(fn ($q) => $q->where('number', $term)->orWhere('customer_name', 'like', "%{$term}%")
                ->orWhereIn('customer_id', Customers::search(Customer::query(), $term)->select('id'))))
            ->latest('date')->latest('id')->get();
        $live = $invoices->whereNull('voided_at');

        return view('livewire.biz.sales-list', [
            'invoices' => $invoices,
            'sum' => ['total' => (int) $live->sum('total'), 'paid' => (int) $live->sum('paid'), 'profit' => (int) $live->sum(fn ($i) => $i->profit()), 'owed' => (int) $live->sum(fn ($i) => $i->balance())],
        ]);
    }
}
