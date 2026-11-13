<?php

namespace App\Livewire\Biz;

use App\Livewire\Biz\Concerns\InBusiness;
use App\Livewire\Biz\Concerns\ShowsImpact;
use App\Models\Biz\Invoice;
use App\Models\Biz\Receipt;
use App\Services\Biz\Sales;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Sale')]
class SaleShow extends Component
{
    use InBusiness, ShowsImpact;

    public Invoice $invoice;

    public bool $showPay = false;

    public string $amount = '';

    public string $date = '';

    public string $method = 'cash';

    public string $reference = '';

    public bool $showVoid = false;

    public string $voidReason = '';

    public function openPay(): void
    {
        $this->resetValidation();
        $this->amount = (string) $this->invoice->balance();
        $this->date = today()->toDateString();
        $this->showPay = true;
    }

    private function payRules(): array
    {
        return [
            'amount' => 'required|integer|min:1',
            'date' => 'required|date|before_or_equal:today',
            'method' => ['required', Rule::in(array_keys(Receipt::METHODS))],
            'reference' => 'nullable|string|max:60',
        ];
    }

    public function receive(Sales $sales): void
    {
        $this->validate($this->payRules());
        if ($this->guarded(fn () => $sales->receive($this->invoice, (int) $this->amount, $this->date, $this->method, $this->reference ?: null), 'amount')) {
            $this->showPay = false;
            $this->invoice->refresh();
            $this->dispatch('toast', message: 'Payment received.');
        }
    }

    public function cancel(Sales $sales): void
    {
        abort_unless(Auth::user()->isRole('admin'), 403);
        $this->validate(['voidReason' => 'required|string|max:255'], [], ['voidReason' => 'reason']);
        if ($this->guarded(fn () => $sales->void($this->invoice, $this->voidReason) ?? true, 'voidReason')) {
            $this->showVoid = false;
            $this->invoice->refresh();
            $this->dispatch('toast', message: 'Sale cancelled. The pieces are back in stock.');
        }
    }

    public function render()
    {
        $this->invoice->load(['lines.product', 'payments.receiver', 'customer', 'creator']);

        return view('livewire.biz.sale-show', ['inv' => $this->invoice, 'isAdmin' => Auth::user()->isRole('admin'), 'impact' => $this->impact()]);
    }

    /** What the open box will change, worked out on a fresh copy of the sale. */
    private function impact(): ?array
    {
        $sales = app(Sales::class);
        $fresh = fn () => Invoice::findOrFail($this->invoice->id);

        return match (true) {
            $this->showVoid => $this->impactOf([], fn () => $sales->void($fresh(), $this->voidReason ?: 'Preview')),
            $this->showPay => $this->impactOf($this->payRules(), fn () => $sales->receive($fresh(), (int) $this->amount, $this->date, $this->method, $this->reference ?: null)),
            default => null,
        };
    }
}
