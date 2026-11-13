<?php

namespace App\Livewire\Academics;

use App\Livewire\ResourcePage;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Builder;

class Subjects extends ResourcePage
{
    protected function model(): string
    {
        return Subject::class;
    }

    protected function title(): string
    {
        return 'Subjects';
    }

    protected function searchable(): array
    {
        return ['name', 'code'];
    }

    protected function query(): Builder
    {
        return Subject::query()->orderBy('name');
    }

    protected function fields(): array
    {
        return [
            'name' => ['label' => 'Name', 'rules' => ['required', 'string', 'max:80']],
            'code' => ['label' => 'Code', 'rules' => ['nullable', 'string', 'max:20']],
        ];
    }

    protected function columns(): array
    {
        return ['name' => 'Subject', 'code' => 'Code'];
    }
}
