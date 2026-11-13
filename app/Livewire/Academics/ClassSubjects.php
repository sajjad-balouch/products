<?php

namespace App\Livewire\Academics;

use App\Livewire\ResourcePage;
use App\Models\ClassSubject;
use App\Models\SchoolClass;
use App\Models\Staff;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Builder;

class ClassSubjects extends ResourcePage
{
    protected function model(): string
    {
        return ClassSubject::class;
    }

    protected function title(): string
    {
        return 'Subject teachers';
    }

    protected function subtitle(): ?string
    {
        return 'A teacher can enter marks only for the class and subject assigned here.';
    }

    protected function query(): Builder
    {
        return ClassSubject::query()->with(['schoolClass', 'subject', 'staff'])->orderBy('school_class_id');
    }

    protected function fields(): array
    {
        return [
            'school_class_id' => ['label' => 'Class', 'rules' => ['required'], 'options' => SchoolClass::orderBy('sort')->get()->mapWithKeys(fn ($c) => [$c->id => $c->label])->all()],
            'subject_id' => ['label' => 'Subject', 'rules' => ['required'], 'options' => Subject::orderBy('name')->pluck('name', 'id')->all()],
            'staff_id' => ['label' => 'Teacher', 'rules' => ['nullable'], 'options' => Staff::where('is_teacher', true)->where('status', 'active')->orderBy('name')->pluck('name', 'id')->all()],
        ];
    }

    protected function columns(): array
    {
        return [
            'class' => ['Class', fn ($r) => $r->schoolClass->label],
            'subject' => ['Subject', fn ($r) => $r->subject->name],
            'teacher' => ['Teacher', fn ($r) => $r->staff?->name ?? 'Not assigned'],
        ];
    }
}
