<?php

namespace App\Livewire\Exams;

use App\Models\AcademicSession;
use App\Models\AuditLog;
use App\Models\ClassSubject;
use App\Models\Exam;
use App\Models\ExamPaper;
use App\Models\SchoolClass;
use App\Models\Subject;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Exams and papers')]
class Index extends Component
{
    public string $name = '';

    public ?string $starts_on = null;

    public ?int $openId = null;

    public string $class_id = '';

    public string $subject_id = '';

    public int $total_marks = 100;

    public int $pass_marks = 33;

    public function createExam(): void
    {
        $this->validate(['name' => 'required|string|max:120', 'starts_on' => 'nullable|date']);

        $exam = Exam::create([
            'name' => $this->name,
            'starts_on' => $this->starts_on ?: null,
            'academic_session_id' => AcademicSession::where('is_current', true)->value('id'),
        ]);

        $this->reset('name', 'starts_on');
        $this->openId = $exam->id;
    }

    /** Adds every subject assigned to the class, so nobody sets up papers one by one. */
    public function addClassPapers(): void
    {
        $this->validate([
            'class_id' => ['required', Rule::exists('school_classes', 'id')->where('school_id', Auth::user()->school_id)],
            'total_marks' => 'required|integer|min:1|max:1000',
            'pass_marks' => 'required|integer|min:0|lte:total_marks',
        ]);

        $exam = Exam::findOrFail($this->openId);
        $subjects = ClassSubject::where('school_class_id', $this->class_id)->pluck('subject_id');

        if ($subjects->isEmpty()) {
            $this->addError('class_id', 'This class has no subjects yet. Add them under Subject teachers.');

            return;
        }

        foreach ($subjects as $subjectId) {
            ExamPaper::firstOrCreate(
                ['exam_id' => $exam->id, 'school_class_id' => $this->class_id, 'subject_id' => $subjectId],
                ['school_id' => $exam->school_id, 'total_marks' => $this->total_marks, 'pass_marks' => $this->pass_marks],
            );
        }

        $this->dispatch('toast', message: 'Papers added.');
    }

    public function removePaper(int $id): void
    {
        $paper = ExamPaper::withCount('marks')->findOrFail($id);

        if ($paper->marks_count > 0) {
            $this->dispatch('toast', message: 'Marks are already entered for this paper.', error: true);

            return;
        }

        $paper->delete();
    }

    public function togglePublish(int $id): void
    {
        $exam = Exam::findOrFail($id);
        $exam->update(['is_published' => ! $exam->is_published]);
        AuditLog::record($exam->is_published ? 'exam_published' : 'exam_unpublished', $exam, null, null, $exam->name);
    }

    public function render()
    {
        return view('livewire.exams.index', [
            'exams' => Exam::withCount('papers')->latest('starts_on')->latest('id')->get(),
            'classes' => SchoolClass::orderBy('sort')->get(),
            'subjects' => Subject::orderBy('name')->get(),
            'current' => $this->openId ? Exam::with(['papers.subject', 'papers.schoolClass'])->find($this->openId) : null,
        ]);
    }
}
