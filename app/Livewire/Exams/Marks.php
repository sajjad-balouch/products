<?php

namespace App\Livewire\Exams;

use App\Models\Exam;
use App\Models\ExamPaper;
use App\Models\Mark;
use App\Models\Student;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Enter marks')]
class Marks extends Component
{
    public string $exam_id = '';

    public string $paper_id = '';

    /** student id => marks; empty means absent */
    public array $marks = [];

    public function updatedExamId(): void
    {
        $this->paper_id = '';
        $this->marks = [];
    }

    public function updatedPaperId(): void
    {
        $paper = $this->paper();
        $this->marks = [];

        if (! $paper) {
            return;
        }

        $this->marks = Mark::where('exam_paper_id', $paper->id)->pluck('obtained', 'student_id')->map(fn ($m) => $m === null ? '' : (float) $m)->all();
    }

    /** Null when the paper is outside this school or not assigned to this teacher. */
    private function paper(): ?ExamPaper
    {
        $paper = $this->paper_id !== '' ? ExamPaper::with(['subject', 'schoolClass'])->find($this->paper_id) : null;

        return $paper && Auth::user()->canEnterMarks($paper) && (string) $paper->exam_id === $this->exam_id ? $paper : null;
    }

    private function students(ExamPaper $paper)
    {
        return Student::active()->whereHas('section', fn ($q) => $q->where('school_class_id', $paper->school_class_id))
            ->with('section')->orderBy('section_id')->orderBy('name')->get();
    }

    public function save(): void
    {
        $paper = $this->paper();
        abort_unless($paper, 403);

        $this->validate(
            ['marks.*' => ['nullable', 'numeric', 'min:0', 'max:'.$paper->total_marks]],
            ['marks.*.max' => "Marks cannot be more than {$paper->total_marks}."],
            ['marks.*' => 'marks'],
        );

        DB::transaction(function () use ($paper) {
            foreach ($this->students($paper) as $student) {
                if (! array_key_exists($student->id, $this->marks)) {
                    continue;
                }

                $value = $this->marks[$student->id];

                Mark::updateOrCreate(
                    ['exam_paper_id' => $paper->id, 'student_id' => $student->id],
                    ['school_id' => $paper->school_id, 'obtained' => $value === '' || $value === null ? null : (float) $value, 'entered_by' => Auth::id()],
                );
            }
        });

        $this->dispatch('toast', message: 'Marks saved.');
    }

    public function render()
    {
        $papers = collect();

        if ($this->exam_id !== '') {
            $papers = ExamPaper::with(['subject', 'schoolClass'])->where('exam_id', $this->exam_id)->get()
                ->filter(fn ($p) => Auth::user()->canEnterMarks($p));
        }

        $paper = $this->paper();

        return view('livewire.exams.marks', [
            'exams' => Exam::latest('starts_on')->latest('id')->get(),
            'papers' => $papers,
            'paper' => $paper,
            'students' => $paper ? $this->students($paper) : collect(),
        ]);
    }
}
