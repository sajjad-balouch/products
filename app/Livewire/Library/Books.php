<?php

namespace App\Livewire\Library;

use App\Livewire\ResourcePage;
use App\Models\Book;
use Illuminate\Database\Eloquent\Builder;

class Books extends ResourcePage
{
    protected function model(): string
    {
        return Book::class;
    }

    protected function title(): string
    {
        return 'Library books';
    }

    protected function searchable(): array
    {
        return ['title', 'author', 'isbn'];
    }

    protected function query(): Builder
    {
        return Book::query()->withCount(['loans as out_count' => fn ($q) => $q->whereNull('returned_on')])->orderBy('title');
    }

    protected function fields(): array
    {
        return [
            'title' => ['label' => 'Title', 'rules' => ['required', 'string', 'max:190']],
            'author' => ['label' => 'Author', 'rules' => ['nullable', 'string', 'max:120']],
            'isbn' => ['label' => 'ISBN', 'rules' => ['nullable', 'string', 'max:30']],
            'copies' => ['label' => 'Copies', 'type' => 'number', 'rules' => ['required', 'integer', 'min:1'], 'default' => 1],
        ];
    }

    protected function columns(): array
    {
        return ['title' => 'Title', 'author' => 'Author', 'isbn' => 'ISBN', 'available' => ['Available', fn ($r) => ($r->copies - $r->out_count).' of '.$r->copies]];
    }
}
