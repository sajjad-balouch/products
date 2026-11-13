<?php

namespace App\Livewire\Biz;

use App\Livewire\Biz\Concerns\InBusiness;
use App\Models\Biz\Order;
use App\Services\Biz\Orders as OrderService;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/** Orders salesmen took on the phone app: accept, cancel, or turn into a sale. */
#[Title('Orders from salesmen')]
class Orders extends Component
{
    use InBusiness, WithPagination;

    #[Url]
    public string $status = 'open';

    public ?int $openId = null;

    public string $cancelReason = '';

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function open(int $id): void
    {
        $order = Order::findOrFail($id);
        if (! $order->seen_at) {
            $order->update(['seen_at' => now()]);
        }
        $this->openId = $order->id;
        $this->cancelReason = '';
        $this->resetValidation();
    }

    public function accept(OrderService $orders): void
    {
        if ($this->guarded(fn () => $orders->accept(Order::findOrFail($this->openId)), 'cancelReason')) {
            $this->dispatch('toast', message: 'Order accepted.');
        }
    }

    public function cancel(OrderService $orders): void
    {
        $this->validate(['cancelReason' => 'required|string|max:255'], [], ['cancelReason' => 'reason']);
        if ($this->guarded(fn () => $orders->cancel(Order::findOrFail($this->openId), $this->cancelReason), 'cancelReason')) {
            $this->openId = null;
            $this->dispatch('toast', message: 'Order cancelled.');
        }
    }

    public function render()
    {
        return view('livewire.biz.orders', [
            'orders' => Order::with(['customer', 'salesman', 'user', 'invoice'])->withCount('lines')
                ->when($this->status === 'open', fn ($q) => $q->whereIn('status', ['new', 'accepted']))
                ->when(! in_array($this->status, ['open', 'all'], true), fn ($q) => $q->where('status', $this->status))
                ->latest('ordered_at')->latest('id')->paginate(30),
            'counts' => Order::query()->selectRaw('status, COUNT(*) as n')->groupBy('status')->pluck('n', 'status'),
            'order' => $this->openId ? Order::with(['lines', 'customer', 'salesman', 'user', 'invoice'])->find($this->openId) : null,
        ]);
    }
}
