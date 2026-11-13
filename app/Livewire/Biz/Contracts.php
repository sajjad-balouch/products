<?php

namespace App\Livewire\Biz;

use App\Livewire\Biz\Concerns\InBusiness;
use App\Models\Biz\Contract;
use App\Models\Biz\Customer;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Installment plans')]
class Contracts extends Component
{
    use InBusiness, WithPagination;

    #[Url]
    public string $status = 'active';

    #[Url]
    public string $search = '';

    public function updated(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $term = trim($this->search);

        return view('livewire.biz.contracts', [
            'contracts' => Contract::with(['customer', 'schedule'])
                ->when($this->status !== 'all', fn ($q) => $q->where('status', $this->status))
                ->when($term !== '', fn ($q) => $q->where(fn ($q) => $q->where('number', $term)->orWhere('item', 'like', "%{$term}%")
                    ->orWhereIn('customer_id', Customers::search(Customer::query(), $term)->select('id'))))
                ->latest('id')->paginate(25),
        ]);
    }
}
