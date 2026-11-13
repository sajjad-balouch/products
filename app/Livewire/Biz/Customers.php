<?php

namespace App\Livewire\Biz;

use App\Livewire\Biz\Concerns\InBusiness;
use App\Models\Biz\Customer;
use App\Services\Biz\Numbers;
use App\Support\Phone;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Title('Customers')]
class Customers extends Component
{
    use InBusiness, WithFileUploads, WithPagination;

    #[Url]
    public string $search = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public array $form = [];

    public $photo;

    public const FIELDS = ['name' => '', 'father_name' => '', 'cnic' => '', 'phone' => '', 'phone2' => '', 'address' => '', 'occupation' => '', 'notes' => '', 'is_blacklisted' => false];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openForm(?int $id = null): void
    {
        $this->resetValidation();
        $customer = $id ? Customer::findOrFail($id) : null;
        $this->editingId = $customer?->id;
        $this->form = $customer ? array_merge(self::FIELDS, $customer->only(array_keys(self::FIELDS))) : self::FIELDS;
        $this->form = array_map(fn ($v) => is_bool($v) ? $v : (string) $v, $this->form);
        $this->photo = null;
        $this->showForm = true;
    }

    /** Shared with the new-sale screen, which can add a customer on the spot. */
    public static function rules(): array
    {
        return [
            'form.name' => 'required|string|max:120',
            'form.father_name' => 'nullable|string|max:120',
            'form.cnic' => ['nullable', 'string', 'max:20', 'regex:/^[0-9-]+$/'],
            'form.phone' => 'required|string|max:30',
            'form.phone2' => 'nullable|string|max:30',
            'form.address' => 'nullable|string|max:255',
            'form.occupation' => 'nullable|string|max:100',
            'form.notes' => 'nullable|string|max:2000',
            'form.is_blacklisted' => 'boolean',
        ];
    }

    public static function messages(): array
    {
        return ['form.cnic.regex' => 'Use numbers and dashes, like 36302-1234567-1.'];
    }

    public static function attributes(): array
    {
        return ['form.name' => 'name', 'form.phone' => 'phone', 'form.cnic' => 'CNIC', 'form.father_name' => "father's name"];
    }

    public function save(): void
    {
        $this->validate(self::rules() + ['photo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:3072'], self::messages(), self::attributes());
        $values = array_map(fn ($v) => $v === '' ? null : $v, $this->form);
        $values['phone'] = Phone::normalize($values['phone']);
        $values['is_blacklisted'] = (bool) $this->form['is_blacklisted'];

        $customer = DB::transaction(function () use ($values) {
            if ($this->editingId) {
                return tap(Customer::findOrFail($this->editingId))->update($values);
            }

            return Customer::create($values + ['code' => Numbers::next($this->business(), 'biz_customers', 'C-', 4)]);
        });

        if ($this->photo) {
            if ($customer->photo_path) {
                Storage::disk('local')->delete($customer->photo_path);
            }
            $customer->update(['photo_path' => $this->photo->store('photos/'.$customer->school_id.'/customers', 'local')]);
        }

        $this->showForm = false;
        $this->dispatch('toast', message: 'Customer saved.');
    }

    public function render()
    {
        return view('livewire.biz.customers', [
            'customers' => self::search(Customer::withCount(['contracts as running' => fn ($q) => $q->where('status', 'active')]), $this->search)
                ->orderBy('name')->paginate(25),
        ]);
    }

    /** Finds customers by name, phone, CNIC or customer number. */
    public static function search($query, string $term)
    {
        $term = trim($term);
        $digits = Phone::normalize($term);

        return $query->when($term !== '', fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', "%{$term}%")
            ->orWhere('cnic', 'like', "%{$term}%")->orWhere('code', $term)
            ->when(strlen($digits) >= 4, fn ($q) => $q->orWhere('phone', 'like', "%{$digits}%")->orWhere('phone2', 'like', "%{$digits}%"))));
    }
}
