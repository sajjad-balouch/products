<?php

namespace App\Livewire\Staff;

use App\Livewire\ResourcePage;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class Directory extends ResourcePage
{
    protected function model(): string
    {
        return Staff::class;
    }

    protected function title(): string
    {
        return 'Staff';
    }

    protected function searchable(): array
    {
        return ['name', 'phone', 'designation'];
    }

    protected function query(): Builder
    {
        return Staff::query()->orderBy('name');
    }

    protected function canDelete(): bool
    {
        return false;
    }

    protected function subtitle(): ?string
    {
        return 'Set status to Left instead of deleting, so payroll history stays complete.';
    }

    protected function fields(): array
    {
        return [
            'name' => ['label' => 'Name', 'rules' => ['required', 'string', 'max:120']],
            'designation' => ['label' => 'Designation', 'rules' => ['nullable', 'string', 'max:80'], 'hint' => 'For example Teacher, Guard, Aya, Driver'],
            'category' => ['label' => 'Group', 'rules' => ['required', 'in:'.implode(',', array_keys(Staff::CATEGORIES))], 'options' => Staff::CATEGORIES, 'default' => 'teaching', 'hint' => 'Groups the staff attendance sheet'],
            'duty_starts_at' => ['label' => 'Own duty start', 'type' => 'time', 'rules' => ['nullable', 'date_format:H:i'], 'hint' => 'Only for different hours, such as a night guard. Empty uses the school hours from Settings.'],
            'duty_ends_at' => ['label' => 'Own duty end', 'type' => 'time', 'rules' => ['nullable', 'date_format:H:i']],
            'biometric_id' => ['label' => 'Machine id', 'rules' => ['nullable', 'string', 'max:20', 'regex:/^[\w-]+$/', Rule::unique('staff', 'biometric_id')->where('school_id', Auth::user()->school_id)->ignore($this->editingId)], 'hint' => 'The user number this person has on the attendance machine'],
            'phone' => ['label' => 'Phone', 'rules' => ['nullable', 'string', 'max:20']],
            'cnic' => ['label' => 'CNIC', 'rules' => ['nullable', 'string', 'max:20']],
            'joined_on' => ['label' => 'Joined on', 'type' => 'date', 'rules' => ['nullable', 'date']],
            'basic_salary' => ['label' => 'Basic salary (Rs)', 'type' => 'number', 'rules' => ['required', 'integer', 'min:0'], 'default' => 0],
            'allowance' => ['label' => 'Allowance (Rs)', 'type' => 'number', 'rules' => ['required', 'integer', 'min:0'], 'default' => 0],
            'is_teacher' => ['label' => 'Teaching staff', 'type' => 'checkbox', 'rules' => ['boolean'], 'default' => true],
            'status' => ['label' => 'Status', 'rules' => ['required'], 'options' => ['active' => 'Active', 'left' => 'Left'], 'default' => 'active'],
        ];
    }

    protected function columns(): array
    {
        return [
            'name' => 'Name',
            'designation' => 'Designation',
            'category' => ['Group', fn ($r) => explode(' (', Staff::CATEGORIES[$r->category] ?? '')[0]],
            'phone' => 'Phone',
            'salary' => ['Salary', fn ($r) => 'Rs '.number_format($r->basic_salary + $r->allowance)],
            'status' => ['Status', fn ($r) => ucfirst($r->status)],
        ];
    }
}
