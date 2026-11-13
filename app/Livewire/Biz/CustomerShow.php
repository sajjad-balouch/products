<?php

namespace App\Livewire\Biz;

use App\Livewire\Biz\Concerns\InBusiness;
use App\Models\Biz\Customer;
use App\Models\Biz\Invoice;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Customer')]
class CustomerShow extends Component
{
    use InBusiness;

    public Customer $customer;

    public function render()
    {
        $contracts = $this->customer->contracts()->with('schedule')->get();

        $invoices = Invoice::where('customer_id', $this->customer->id)->latest('date')->latest('id')->get();

        return view('livewire.biz.customer-show', [
            'contracts' => $contracts,
            'invoices' => $invoices,
            'credit' => (int) $invoices->sum(fn ($i) => $i->balance()),
            'owed' => $contracts->where('status', 'active')->sum(fn ($c) => $c->outstanding()),
            'overdue' => $contracts->where('status', 'active')->sum(fn ($c) => $c->overdue()),
            'paidTotal' => (int) $this->customer->contracts()->join('biz_receipts as r', 'r.contract_id', '=', 'biz_contracts.id')->whereNull('r.voided_at')->sum('r.amount'),
        ]);
    }
}
