<?php

namespace App\Livewire\Academics;

use App\Livewire\ResourcePage;
use App\Models\SchoolClass;
use Illuminate\Database\Eloquent\Builder;

class Classes extends ResourcePage
{
    protected function model(): string
    {
        return SchoolClass::class;
    }

    protected function title(): string
    {
        return 'Classes';
    }

    protected function subtitle(): ?string
    {
        return 'For a college, add each year and group, such as XI with Pre-Medical. Sort order decides where students go when promoted.';
    }

    protected function query(): Builder
    {
        return SchoolClass::query()->orderBy('sort');
    }

    protected function fields(): array
    {
        return [
            'name' => ['label' => 'Class', 'rules' => ['required', 'string', 'max:50']],
            'stream' => ['label' => 'College group (optional)', 'rules' => ['nullable', 'string', 'max:50']],
            'sort' => ['label' => 'Sort order', 'type' => 'number', 'rules' => ['required', 'integer', 'min:0'], 'default' => 0],
        ];
    }

    protected function columns(): array
    {
        return ['name' => 'Class', 'stream' => 'Group', 'sort' => 'Order'];
    }
}
