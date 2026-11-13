<?php

namespace App\Livewire;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Search, list, add, edit and delete for simple records. Subclasses describe
 * fields and columns. Only declared fields are ever saved, and select values
 * are checked against the options shown, which keeps them inside the school.
 */
abstract class ResourcePage extends Component
{
    use WithPagination;

    public array $form = [];

    public ?int $editingId = null;

    public bool $showForm = false;

    public string $search = '';

    abstract protected function model(): string;

    abstract protected function title(): string;

    /** @return array<string, array{label: string, type?: string, rules?: array, options?: array, default?: mixed, hint?: string}> */
    abstract protected function fields(): array;

    /** @return array<string, string|array{0: string, 1: Closure}> column => label, or [label, formatter] */
    abstract protected function columns(): array;

    protected function subtitle(): ?string
    {
        return null;
    }

    protected function searchable(): array
    {
        return [];
    }

    protected function query(): Builder
    {
        return ($this->model())::query()->latest('id');
    }

    protected function canDelete(): bool
    {
        return true;
    }

    protected function beforeSave(array $data): array
    {
        return $data;
    }

    protected function afterSave($record): void {}

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function create(): void
    {
        $this->resetValidation();
        $this->editingId = null;
        $this->form = collect($this->fields())->map(fn ($f) => $f['default'] ?? (($f['type'] ?? 'text') === 'checkbox' ? false : ''))->all();
        $this->showForm = true;
    }

    public function edit(int $id): void
    {
        $this->resetValidation();
        $record = ($this->model())::findOrFail($id);
        $this->editingId = $id;
        $this->form = collect($this->fields())->map(function ($f, $key) use ($record) {
            $value = $record->{$key};

            return $value instanceof \DateTimeInterface ? $value->format('Y-m-d') : $value;
        })->all();
        $this->showForm = true;
    }

    public function save(): void
    {
        $rules = [];
        $attributes = [];

        foreach ($this->fields() as $key => $field) {
            $fieldRules = $field['rules'] ?? ['nullable'];
            if (isset($field['options'])) {
                $fieldRules[] = Rule::in(array_keys($field['options']));
            }
            $rules["form.$key"] = $fieldRules;
            $attributes["form.$key"] = strtolower($field['label']);
        }

        $validated = $this->validate($rules, [], $attributes)['form'];

        $data = collect($validated)
            ->map(fn ($value) => $value === '' ? null : $value)
            ->only(array_keys($this->fields()))
            ->all();

        $data = $this->beforeSave($data);

        try {
            if ($this->editingId) {
                $record = ($this->model())::findOrFail($this->editingId);
                $record->update($data);
            } else {
                $record = ($this->model())::create($data);
            }
        } catch (QueryException $e) {
            if ((string) $e->getCode() === '23000') {
                $this->addError('form', 'This record already exists.');

                return;
            }
            throw $e;
        }

        $this->afterSave($record);
        $this->showForm = false;
        $this->dispatch('toast', message: 'Saved.');
    }

    public function delete(int $id): void
    {
        abort_unless($this->canDelete(), 403);

        try {
            ($this->model())::findOrFail($id)->delete();
            $this->dispatch('toast', message: 'Deleted.');
        } catch (QueryException) {
            $this->dispatch('toast', message: 'This record is in use, so it cannot be deleted.', error: true);
        }
    }

    public function render()
    {
        $query = $this->query();

        if ($this->search !== '' && $this->searchable()) {
            $query->where(function ($q) {
                foreach ($this->searchable() as $column) {
                    $q->orWhere($column, 'like', '%'.$this->search.'%');
                }
            });
        }

        return view('livewire.resource-page', [
            'rows' => $query->paginate(20),
            'heading' => $this->title(),
            'subheading' => $this->subtitle(),
            'columnDefs' => $this->columns(),
            'fieldDefs' => $this->fields(),
            'hasSearch' => (bool) $this->searchable(),
            'deletable' => $this->canDelete(),
        ])->title($this->title());
    }
}
