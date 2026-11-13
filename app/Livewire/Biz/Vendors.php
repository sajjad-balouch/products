<?php

namespace App\Livewire\Biz;

use App\Livewire\Biz\Concerns\InBusiness;
use App\Models\Biz\Vendor;
use App\Models\School;
use App\Services\Biz\Numbers;
use App\Support\Phone;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/** The people and companies the business buys from, with what is owed to each. */
#[Title('Vendors')]
class Vendors extends Component
{
    use InBusiness, WithPagination;

    #[Url]
    public string $search = '';

    public bool $showForm = false;

    public ?int $editingId = null;

    public array $form = [];

    public const FIELDS = ['name' => '', 'company' => '', 'phone' => '', 'address' => '', 'notes' => '', 'is_active' => true];

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openForm(?int $id = null): void
    {
        $this->resetValidation();
        $vendor = $id ? Vendor::findOrFail($id) : null;
        $this->editingId = $vendor?->id;
        $this->form = array_map(fn ($v) => is_bool($v) ? $v : (string) $v, $vendor ? array_merge(self::FIELDS, $vendor->only(array_keys(self::FIELDS))) : self::FIELDS);
        $this->showForm = true;
    }

    public static function rules(string $prefix = 'form'): array
    {
        return [
            $prefix.'.name' => 'required|string|max:120',
            $prefix.'.company' => 'nullable|string|max:120',
            $prefix.'.phone' => 'nullable|string|max:30',
            $prefix.'.address' => 'nullable|string|max:255',
            $prefix.'.notes' => 'nullable|string|max:1000',
        ];
    }

    /** Also used by stock in, which can add a vendor on the spot. */
    public static function create(array $data, School $school): Vendor
    {
        return DB::transaction(fn () => Vendor::create([
            'school_id' => $school->id,
            'code' => Numbers::next($school, 'biz_vendors', 'V-', 4, [], 'code'),
            'name' => trim($data['name']),
            'company' => ($data['company'] ?? '') ?: null,
            'phone' => ($data['phone'] ?? '') !== '' ? Phone::normalize($data['phone']) : null,
            'address' => ($data['address'] ?? '') ?: null,
            'notes' => ($data['notes'] ?? '') ?: null,
        ]));
    }

    public function save(): void
    {
        $this->validate(self::rules(), [], ['form.name' => 'name']);
        if ($this->editingId) {
            Vendor::findOrFail($this->editingId)->update([
                'name' => trim($this->form['name']), 'company' => $this->form['company'] ?: null,
                'phone' => $this->form['phone'] !== '' ? Phone::normalize($this->form['phone']) : null,
                'address' => $this->form['address'] ?: null, 'notes' => $this->form['notes'] ?: null, 'is_active' => (bool) $this->form['is_active'],
            ]);
        } else {
            self::create($this->form, $this->business());
        }
        $this->showForm = false;
        $this->dispatch('toast', message: 'Vendor saved.');
    }

    public function render()
    {
        $term = trim($this->search);
        $vendors = Vendor::when($term !== '', fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('company', 'like', "%{$term}%")->orWhere('phone', 'like', "%{$term}%")->orWhere('code', $term)))
            ->orderByDesc('is_active')->orderBy('name')->paginate(30);
        $balances = Vendor::balances($vendors->pluck('id')->all());

        return view('livewire.biz.vendors', [
            'vendors' => $vendors,
            'balances' => $balances,
            'owedTotal' => (int) Vendor::balances()->sum(),
        ]);
    }
}
