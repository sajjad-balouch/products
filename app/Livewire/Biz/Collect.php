<?php

namespace App\Livewire\Biz;

use App\Livewire\Biz\Concerns\InBusiness;
use App\Models\Biz\Contract;
use App\Models\Biz\Customer;
use Livewire\Attributes\Title;
use Livewire\Component;

/** The counter: find the customer, see what they owe, take the money. */
#[Title('Collect payment')]
class Collect extends Component
{
    use InBusiness;

    public string $search = '';

    public function render()
    {
        $term = trim($this->search);
        $contracts = collect();

        if (strlen($term) >= 2) {
            $contracts = Contract::with(['customer', 'schedule'])->where('status', 'active')
                ->where(fn ($q) => $q->where('number', $term)->orWhere('item', 'like', "%{$term}%")
                    ->orWhereIn('customer_id', Customers::search(Customer::query(), $term)->select('id')))
                ->orderBy('customer_id')->limit(30)->get();
        }

        return view('livewire.biz.collect', ['contracts' => $contracts]);
    }
}
