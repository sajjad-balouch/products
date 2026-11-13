<?php

namespace App\Livewire\Biz;

use App\Livewire\Biz\Concerns\InBusiness;
use App\Models\Biz\Contract;
use App\Services\Biz\Installments;
use App\Services\Biz\StarterCatalog;
use App\Support\BusinessKinds;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Business settings')]
class Settings extends Component
{
    use InBusiness;

    public array $details = [];

    public array $plan = [];

    public string $kind = 'general';

    public function mount(): void
    {
        $b = $this->business();
        $this->details = ['name' => $b->name, 'phone' => (string) $b->phone, 'email' => (string) $b->email, 'address' => (string) $b->address, 'principal_name' => (string) $b->principal_name];
        $this->plan = array_map(fn ($v) => (string) $v, Installments::settings($b));
        $this->kind = BusinessKinds::of($b);
    }

    public function save(): void
    {
        $this->validate([
            'details.name' => 'required|string|max:150',
            'details.phone' => 'nullable|string|max:30',
            'details.email' => 'nullable|email|max:150',
            'details.address' => 'nullable|string|max:255',
            'details.principal_name' => 'nullable|string|max:120',
            'plan.markup_percent' => 'required|numeric|min:0|max:500',
            'plan.installments' => 'required|integer|min:1|max:120',
            'plan.frequency' => ['required', Rule::in(array_keys(Contract::FREQUENCIES))],
            'plan.late_fee' => 'required|integer|min:0|max:1000000',
            'plan.grace_days' => 'required|integer|min:0|max:60',
            'plan.rounding' => ['required', Rule::in(['1', '10', '50', '100'])],
            'plan.receipt_note' => 'nullable|string|max:200',
            'plan.terms' => 'nullable|string|max:5000',
            'kind' => ['required', Rule::in(array_keys(BusinessKinds::KINDS))],
        ], [], ['details.name' => 'business name', 'details.principal_name' => 'owner name', 'plan.markup_percent' => 'markup', 'kind' => 'business type']);

        $business = $this->business();
        $changed = BusinessKinds::of($business) !== $this->kind || $business->business_kind === null;
        $business->update($this->details + ['business_settings' => $this->plan, 'business_kind' => $this->kind]);

        // A new kind brings its own product types, categories and brands. Nothing already there is removed.
        if ($changed) {
            StarterCatalog::seed($business->id, $this->kind);
        }
        $this->dispatch('toast', message: $changed ? 'Saved. Products, brands and dashboard are now set for a '.strtolower(BusinessKinds::label($this->kind)).'.' : 'Settings saved.');
    }

    public function render()
    {
        return view('livewire.biz.settings');
    }
}
