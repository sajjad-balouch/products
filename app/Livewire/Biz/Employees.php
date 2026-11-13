<?php

namespace App\Livewire\Biz;

use App\Livewire\Biz\Concerns\InBusiness;
use App\Models\Biz\Employee;
use App\Services\Biz\Numbers;
use App\Support\Phone;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Component;

/** Staff and salesmen: salary, advances and, for salesmen, commission on their sales. */
#[Title('Employees')]
class Employees extends Component
{
    use InBusiness;

    public bool $showForm = false;

    public ?int $editingId = null;

    public array $form = [];

    public const FIELDS = ['name' => '', 'father_name' => '', 'cnic' => '', 'phone' => '', 'address' => '', 'designation' => '', 'is_salesman' => false,
        'salary' => '', 'commission_percent' => '', 'joined_on' => '', 'notes' => '', 'is_active' => true];

    public function openForm(?int $id = null, bool $salesman = false): void
    {
        $this->resetValidation();
        $e = $id ? Employee::findOrFail($id) : null;
        $this->editingId = $e?->id;
        $data = $e ? array_merge(self::FIELDS, $e->only(array_keys(self::FIELDS)), ['joined_on' => $e->joined_on?->toDateString() ?? '']) : array_merge(self::FIELDS, ['is_salesman' => $salesman, 'designation' => $salesman ? 'Salesman' : '']);
        $this->form = array_map(fn ($v) => is_bool($v) ? $v : (string) $v, $data);
        $this->showForm = true;
    }

    public function save(): void
    {
        $this->validate([
            'form.name' => 'required|string|max:120',
            'form.father_name' => 'nullable|string|max:120',
            'form.cnic' => ['nullable', 'string', 'max:20', 'regex:/^[0-9-]+$/'],
            'form.phone' => 'nullable|string|max:30',
            'form.address' => 'nullable|string|max:255',
            'form.designation' => 'nullable|string|max:80',
            'form.salary' => 'nullable|integer|min:0|max:100000000',
            'form.commission_percent' => 'nullable|numeric|min:0|max:100',
            'form.joined_on' => 'nullable|date',
            'form.notes' => 'nullable|string|max:1000',
        ], ['form.cnic.regex' => 'Use numbers and dashes, like 36302-1234567-1.'], ['form.name' => 'name', 'form.salary' => 'salary', 'form.commission_percent' => 'commission']);

        $data = [
            'name' => trim($this->form['name']), 'father_name' => $this->form['father_name'] ?: null, 'cnic' => $this->form['cnic'] ?: null,
            'phone' => $this->form['phone'] !== '' ? Phone::normalize($this->form['phone']) : null, 'address' => $this->form['address'] ?: null,
            'designation' => $this->form['designation'] ?: null, 'is_salesman' => (bool) $this->form['is_salesman'],
            'salary' => (int) $this->form['salary'], 'commission_percent' => (float) ($this->form['commission_percent'] ?: 0),
            'joined_on' => $this->form['joined_on'] ?: null, 'notes' => $this->form['notes'] ?: null, 'is_active' => (bool) $this->form['is_active'],
        ];
        if ($this->editingId) {
            Employee::findOrFail($this->editingId)->update($data);
        } else {
            DB::transaction(fn () => Employee::create($data + ['school_id' => $this->business()->id, 'code' => Numbers::next($this->business(), 'biz_employees', 'E-', 3, [], 'code')]));
        }
        $this->showForm = false;
        $this->dispatch('toast', message: 'Employee saved.');
    }

    public function render()
    {
        $from = today()->startOfMonth()->toDateString();
        $to = today()->toDateString();
        $employees = Employee::orderByDesc('is_active')->orderByDesc('is_salesman')->orderBy('name')->get();

        return view('livewire.biz.employees', [
            'employees' => $employees,
            'month' => $employees->mapWithKeys(fn ($e) => [$e->id => [
                'sales' => $e->is_salesman ? $e->salesBetween($from, $to) : null,
                'paid' => $e->paid('salaries', $from, $to),
                'advance' => $e->advance(),
            ]]),
            'payroll' => (int) $employees->where('is_active', true)->sum('salary'),
        ]);
    }
}
