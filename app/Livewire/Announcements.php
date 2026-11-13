<?php

namespace App\Livewire;

use App\Models\Announcement;
use Illuminate\Database\Eloquent\Builder;

class Announcements extends ResourcePage
{
    protected function model(): string
    {
        return Announcement::class;
    }

    protected function title(): string
    {
        return 'Announcements';
    }

    protected function subtitle(): ?string
    {
        return 'Shown to all parents in the parent portal.';
    }

    protected function query(): Builder
    {
        return Announcement::query()->latest('published_on')->latest('id');
    }

    protected function fields(): array
    {
        return [
            'title' => ['label' => 'Title', 'rules' => ['required', 'string', 'max:150']],
            'body' => ['label' => 'Message', 'type' => 'textarea', 'rules' => ['required', 'string', 'max:3000']],
            'published_on' => ['label' => 'Date', 'type' => 'date', 'rules' => ['required', 'date'], 'default' => now()->toDateString()],
        ];
    }

    protected function columns(): array
    {
        return ['published_on' => ['Date', fn ($r) => $r->published_on->format('j M Y')], 'title' => 'Title'];
    }
}
