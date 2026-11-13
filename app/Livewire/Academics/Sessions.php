<?php

namespace App\Livewire\Academics;

use App\Livewire\ResourcePage;
use App\Models\AcademicSession;

class Sessions extends ResourcePage
{
    protected function model(): string
    {
        return AcademicSession::class;
    }

    protected function title(): string
    {
        return 'Academic sessions';
    }

    protected function subtitle(): ?string
    {
        return 'Only one session is current at a time.';
    }

    protected function fields(): array
    {
        return [
            'name' => ['label' => 'Name', 'rules' => ['required', 'string', 'max:50'], 'hint' => 'For example 2026-27'],
            'starts_on' => ['label' => 'Starts on', 'type' => 'date', 'rules' => ['required', 'date']],
            'ends_on' => ['label' => 'Ends on', 'type' => 'date', 'rules' => ['required', 'date', 'after:form.starts_on']],
            'is_current' => ['label' => 'This is the current session', 'type' => 'checkbox', 'rules' => ['boolean']],
        ];
    }

    protected function columns(): array
    {
        return [
            'name' => 'Name',
            'starts_on' => ['Starts', fn ($r) => $r->starts_on->format('j M Y')],
            'ends_on' => ['Ends', fn ($r) => $r->ends_on->format('j M Y')],
            'is_current' => ['Current', fn ($r) => $r->is_current ? 'Yes' : ''],
        ];
    }

    protected function afterSave($record): void
    {
        if ($record->is_current) {
            AcademicSession::where('id', '!=', $record->id)->update(['is_current' => false]);
        }
    }
}
