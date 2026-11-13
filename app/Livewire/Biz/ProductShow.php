<?php

namespace App\Livewire\Biz;

use App\Livewire\Biz\Concerns\InBusiness;
use App\Models\Biz\Product;
use App\Models\Biz\StockUnit;
use App\Services\Biz\Stock;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Product')]
class ProductShow extends Component
{
    use InBusiness;

    public Product $product;

    /** Details of a piece still in stock can be written or corrected here. */
    public ?int $editUnit = null;

    public array $unitDetails = [];

    public function openUnit(int $id): void
    {
        $unit = StockUnit::where('product_id', $this->product->id)->where('status', 'in_stock')->findOrFail($id);
        $this->editUnit = $unit->id;
        $this->unitDetails = $unit->details ?? [];
    }

    public function saveUnit(): void
    {
        $this->validate(['unitDetails.*' => 'nullable|string|max:120']);
        $unit = StockUnit::where('product_id', $this->product->id)->where('status', 'in_stock')->findOrFail($this->editUnit);
        $details = array_filter(array_map(fn ($v) => is_string($v) ? trim($v) : $v, $this->unitDetails), fn ($v) => $v !== null && $v !== '');
        if ($id = $this->product->identifier()) {
            if (! $this->guarded(fn () => Stock::assertUnique($this->business(), $id, [$details[$id['key']] ?? null], [$unit->id]) ?? true, 'unitDetails.'.$id['key'])) {
                return;
            }
        }
        $unit->update(['details' => $details ?: null]);
        $this->editUnit = null;
        $this->dispatch('toast', message: 'Details saved.');
    }

    public function render()
    {
        $this->product->load('type');
        $units = $this->product->units()->with(['purchase', 'contract.customer'])->orderBy('id')->get();
        $sales = $this->product->contracts()->with('customer')->latest('sale_date')->get();
        $lines = $this->product->invoiceLines()->with('invoice.customer')->whereHas('invoice', fn ($q) => $q->whereNull('voided_at'))->latest('id')->get();

        return view('livewire.biz.product-show', [
            'p' => $this->product,
            'inStock' => $units->where('status', 'in_stock'),
            'sold' => $units->where('status', 'sold'),
            'purchases' => $this->product->purchases()->get(),
            'sales' => $sales,
            'lines' => $lines,
            'profit' => (int) $sales->sum(fn ($c) => $c->grossProfit() ?? 0) + (int) $lines->sum(fn ($l) => $l->total - $l->cost),
        ]);
    }
}
