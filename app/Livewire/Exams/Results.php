<?php

namespace App\Livewire\Exams;

use App\Models\Exam;
use App\Models\ExamPaper;
use App\Models\SchoolClass;
use App\Services\ResultService;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Results')]
class Results extends Component
{
    public string $exam_id = '';

    public string $class_id = '';

    public function render(ResultService $results)
    {
        $exam = $this->exam_id !== '' ? Exam::find($this->exam_id) : null;
        $class = $this->class_id !== '' ? SchoolClass::find($this->class_id) : null;
        $rows = $exam && $class ? $results->classResult($exam, $class->id) : collect();
        $papers = $exam && $class ? ExamPaper::with('subject')->where('exam_id', $exam->id)->where('school_class_id', $class->id)->get() : collect();

        $subjectStats = $papers->map(function ($paper) use ($rows) {
            $marks = $rows->map(fn ($r) => $r['papers'][$paper->id] ?? null)->filter();
            $taken = $marks->filter(fn ($m) => $m['obtained'] !== null);

            return [
                'subject' => $paper->subject->name,
                'average' => $taken->count() ? round($taken->avg('obtained') / $paper->total_marks * 100) : null,
                'failed' => $marks->where('failed', true)->count(),
            ];
        });

        return view('livewire.exams.results', [
            'exams' => Exam::latest('starts_on')->latest('id')->get(),
            'classes' => SchoolClass::orderBy('sort')->get(),
            'exam' => $exam,
            'rows' => $rows,
            'papers' => $papers,
            'subjectStats' => $subjectStats,
            'passCount' => $rows->where('passed', true)->count(),
        ]);
    }
}
