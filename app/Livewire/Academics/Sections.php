<?php

namespace App\Livewire\Academics;

use App\Livewire\ResourcePage;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Builder;

class Sections extends ResourcePage
{
    protected function model(): string
    {
        return Section::class;
    }

    protected function title(): string
    {
        return 'Sections';
    }

    protected function query(): Builder
    {
        return Section::query()->with(['schoolClass', 'classTeacher'])->withCount('students')->orderBy('school_class_id')->orderBy('name');
    }

    protected function fields(): array
    {
        return [
            'school_class_id' => ['label' => 'Class', 'rules' => ['required'], 'options' => SchoolClass::orderBy('sort')->get()->mapWithKeys(fn ($c) => [$c->id => $c->label])->all()],
            'name' => ['label' => 'Section', 'rules' => ['required', 'string', 'max:30'], 'default' => 'A'],
            'class_teacher_id' => ['label' => 'Class teacher', 'rules' => ['nullable'], 'options' => Staff::where('is_teacher', true)->where('status', 'active')->orderBy('name')->pluck('name', 'id')->all()],
            'capacity' => ['label' => 'Capacity', 'type' => 'number', 'rules' => ['required', 'integer', 'min:1'], 'default' => 40],
        ];
    }

    protected function columns(): array
    {
        return [
            'class' => ['Class', fn ($r) => $r->schoolClass->label],
            'name' => 'Section',
            'teacher' => ['Class teacher', fn ($r) => $r->classTeacher?->name],
            'strength' => ['Strength', fn ($r) => $r->students_count.' / '.$r->capacity],
        ];
    }
}
