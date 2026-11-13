<?php

namespace App\Livewire\Students;

use App\Models\Section;
use App\Models\Student;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Students')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $section = '';

    #[Url]
    public string $status = 'active';

    public function updated($property): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $students = Student::query()
            ->with(['section.schoolClass', 'guardian'])
            ->when($this->status !== '', fn ($q) => $q->where('status', $this->status))
            ->when($this->section !== '', fn ($q) => $q->where('section_id', $this->section))
            ->when($this->search !== '', function ($q) {
                $q->where(fn ($s) => $s->where('name', 'like', "%{$this->search}%")
                    ->orWhere('admission_no', 'like', "%{$this->search}%")
                    ->orWhere('father_name', 'like', "%{$this->search}%")
                    ->orWhereHas('guardian', fn ($g) => $g->where('phone', 'like', "%{$this->search}%")));
            })
            ->orderBy('name')->paginate(25);

        return view('livewire.students.index', [
            'students' => $students,
            'sections' => Section::with('schoolClass')->get()->sortBy(fn ($s) => [$s->schoolClass->sort, $s->name]),
        ]);
    }
}
