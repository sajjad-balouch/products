<?php

namespace App\Livewire\Biz;

use App\Livewire\Biz\Concerns\DateRange;
use App\Livewire\Biz\Concerns\InBusiness;
use App\Models\Biz\Receipt;
use App\Models\User;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/** Money received, by day, method and the person who took it. */
#[Title('Collections')]
class CollectionReport extends Component
{
    use DateRange, InBusiness;

    #[Url]
    public string $method = '';

    #[Url]
    public string $by = '';

    public function render()
    {
        $receipts = Receipt::with(['contract.customer', 'receiver'])->whereNull('voided_at')
            ->whereBetween('date', [$this->from, $this->to])
            ->when($this->method, fn ($q) => $q->where('method', $this->method))
            ->when($this->by, fn ($q) => $q->where('received_by', (int) $this->by))
            ->orderBy('date')->orderBy('id')->get();

        return view('livewire.biz.collection-report', [
            'receipts' => $receipts,
            'byMethod' => $receipts->groupBy('method')->map(fn ($g) => $g->sum(fn ($r) => $r->total())),
            'byPerson' => $receipts->groupBy(fn ($r) => $r->receiver?->name ?? '—')->map(fn ($g) => $g->sum(fn ($r) => $r->total())),
            'staff' => User::where('school_id', $this->business()->id)->whereIn('role', ['admin', 'accountant'])->orderBy('name')->get(),
        ]);
    }
}
