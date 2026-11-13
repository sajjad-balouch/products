<?php

namespace App\Livewire\Finance;

use App\Livewire\ResourcePage;
use App\Models\Expense;
use Illuminate\Database\Eloquent\Builder;

class Expenses extends ResourcePage
{
    protected function model(): string
    {
        return Expense::class;
    }

    protected function title(): string
    {
        return 'Expenses';
    }

    protected function searchable(): array
    {
        return ['category', 'description'];
    }

    protected function query(): Builder
    {
        return Expense::query()->latest('spent_on')->latest('id');
    }

    protected function fields(): array
    {
        return [
            'category' => ['label' => 'Category', 'rules' => ['required'], 'options' => array_combine($c = ['Rent', 'Utilities', 'Stationery', 'Maintenance', 'Transport', 'Marketing', 'Events', 'Other'], $c)],
            'description' => ['label' => 'Description', 'rules' => ['nullable', 'string', 'max:190']],
            'amount' => ['label' => 'Amount (Rs)', 'type' => 'number', 'rules' => ['required', 'integer', 'min:1', 'max:100000000']],
            'spent_on' => ['label' => 'Date', 'type' => 'date', 'rules' => ['required', 'date'], 'default' => now()->toDateString()],
        ];
    }

    protected function columns(): array
    {
        return [
            'spent_on' => ['Date', fn ($r) => $r->spent_on->format('j M Y')],
            'category' => 'Category',
            'description' => 'Description',
            'amount' => ['Amount', fn ($r) => 'Rs '.number_format($r->amount)],
        ];
    }
}
