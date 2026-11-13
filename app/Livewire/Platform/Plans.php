<?php

namespace App\Livewire\Platform;

use App\Livewire\Platform\Concerns\OwnerOnly;
use App\Models\Plan;
use App\Models\School;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

/** The packages the owner sells, each a monthly price and a set of features. */
#[Title('Plans')]
class Plans extends Component
{
    use OwnerOnly;

    public bool $showForm = false;

    public ?int $editingId = null;

    public string $name = '';

    public string $code = '';

    public string $monthlyPrice = '0';

    public string $description = '';

    public array $features = [];

    /** school or business. Fixed once the plan exists, since schools or businesses may be on it. */
    public string $product = 'school';

    public bool $updateSchools = false;

    public function openForm(?int $id = null, string $product = 'school'): void
    {
        $this->resetValidation();
        $plan = $id ? Plan::findOrFail($id) : null;
        $this->editingId = $plan?->id;
        $this->product = $plan?->product ?? (isset(School::PRODUCTS[$product]) ? $product : 'school');
        $this->name = (string) $plan?->name;
        $this->code = (string) $plan?->code;
        $this->monthlyPrice = (string) ($plan?->monthly_price ?? 0);
        $this->description = (string) $plan?->description;
        $this->features = array_values(array_intersect($plan?->features ?? array_keys(School::FEATURES), array_keys(School::featuresFor($this->product))));
        $this->updateSchools = false;
        $this->showForm = true;
    }

    public function save(): void
    {
        if (! $this->editingId) {
            $this->code = Str::slug($this->code ?: $this->name);
        }

        $this->validate([
            'name' => 'required|string|max:60',
            'code' => ['required', 'alpha_dash', 'max:40', 'not_in:trial', Rule::unique('plans', 'code')->ignore($this->editingId)],
            'monthlyPrice' => 'required|integer|min:0|max:10000000',
            'description' => 'nullable|string|max:255',
            'features' => 'array',
            'product' => [Rule::in(array_keys(School::PRODUCTS))],
            'features.*' => [Rule::in(array_keys(School::featuresFor($this->product)))],
        ], ['code.not_in' => 'Trial is kept for the free period.'], ['monthlyPrice' => 'monthly price']);

        $values = ['name' => $this->name, 'monthly_price' => (int) $this->monthlyPrice, 'description' => $this->description ?: null, 'features' => array_values($this->features)];

        if ($this->editingId) {
            $plan = Plan::findOrFail($this->editingId);
            $plan->update($values);
        } else {
            $plan = Plan::create($values + ['code' => $this->code, 'product' => $this->product, 'sort' => (int) Plan::max('sort') + 1]);
        }

        $message = 'Plan saved.';
        if ($this->updateSchools) {
            $schools = $plan->schools()->get();
            $schools->each(fn ($school) => $plan->applyTo($school));
            $message .= ' '.$schools->count().' '.Str::plural('school', $schools->count()).' updated to its price and features.';
        }

        $this->showForm = false;
        $this->dispatch('toast', message: $message);
    }

    public function delete(int $id): void
    {
        $plan = Plan::withCount('schools')->findOrFail($id);

        if ($plan->schools_count) {
            $this->dispatch('toast', message: 'Move the schools on this plan to another plan first.', error: true);

            return;
        }

        $plan->delete();
    }

    public function render()
    {
        return view('livewire.platform.plans', ['plans' => Plan::withCount('schools')->orderBy('sort')->get()]);
    }
}
