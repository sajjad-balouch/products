<?php

namespace App\Livewire\Platform;

use App\Livewire\Platform\Concerns\OwnerOnly;
use App\Models\SubscriptionEntry;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/** Every payment, free month and date change across all schools. */
#[Title('Payments')]
class Payments extends Component
{
    use OwnerOnly;
    use WithPagination;

    #[Url]
    public string $month = '';

    #[Url]
    public string $type = '';

    public function updated(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = SubscriptionEntry::with(['school', 'user'])
            ->when($this->type !== '', fn ($q) => $q->where('type', $this->type))
            ->when(preg_match('/^\d{4}-\d{2}$/', $this->month), fn ($q) => $q->where(fn ($w) => $w
                ->whereBetween('paid_on', [$this->month.'-01', date('Y-m-t', strtotime($this->month.'-01'))])
                ->orWhere(fn ($n) => $n->whereNull('paid_on')->whereBetween('created_at', [$this->month.'-01 00:00:00', date('Y-m-t', strtotime($this->month.'-01')).' 23:59:59']))));

        return view('livewire.platform.payments', [
            'total' => (clone $query)->where('type', 'payment')->sum('amount'),
            'entries' => $query->latest('id')->paginate(30),
            // The last twelve months, for the filter.
            'months' => collect(range(0, 11))->mapWithKeys(fn ($i) => [now()->subMonthsNoOverflow($i)->format('Y-m') => now()->subMonthsNoOverflow($i)->format('F Y')]),
        ]);
    }
}
