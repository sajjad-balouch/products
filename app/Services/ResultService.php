<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamPaper;
use App\Models\Mark;
use App\Models\Student;
use App\Support\Grades;
use Illuminate\Support\Collection;

class ResultService
{
    /**
     * Class result for one exam, ranked. Equal totals share a position (1, 2, 2, 4).
     *
     * @return Collection<int, array{student: Student, papers: array, obtained: float, total: int, percent: float, grade: string, passed: bool, position: int}>
     */
    public function classResult(Exam $exam, int $classId, ?int $sectionId = null): Collection
    {
        $papers = ExamPaper::where('exam_id', $exam->id)->where('school_class_id', $classId)->with('subject')->get();

        $students = Student::active()
            ->whereHas('section', fn ($q) => $q->where('school_class_id', $classId))
            ->when($sectionId, fn ($q) => $q->where('section_id', $sectionId))
            ->orderBy('name')->get();

        $marks = Mark::whereIn('exam_paper_id', $papers->pluck('id'))->whereIn('student_id', $students->pluck('id'))
            ->get()->groupBy('student_id');

        $rows = $students->map(function (Student $student) use ($papers, $marks) {
            $own = $marks->get($student->id, collect())->keyBy('exam_paper_id');
            $obtained = 0.0;
            $total = 0;
            $passed = true;
            $paperRows = [];

            foreach ($papers as $paper) {
                $mark = $own->get($paper->id);
                $got = $mark?->obtained;
                $obtained += (float) $got;
                $total += $paper->total_marks;
                $failed = $got === null || $got < $paper->pass_marks;
                $passed = $passed && ! $failed;

                $paperRows[$paper->id] = [
                    'subject' => $paper->subject->name,
                    'obtained' => $got,
                    'total' => $paper->total_marks,
                    'pass' => $paper->pass_marks,
                    'failed' => $failed,
                ];
            }

            $percent = $total > 0 ? round($obtained / $total * 100, 1) : 0.0;

            return [
                'student' => $student,
                'papers' => $paperRows,
                'obtained' => $obtained,
                'total' => $total,
                'percent' => $percent,
                'grade' => Grades::for($percent),
                'passed' => $passed && $total > 0,
                'position' => 0,
            ];
        })->sortByDesc('obtained')->values();

        $position = 0;
        $previous = null;

        return $rows->map(function ($row, $index) use (&$position, &$previous) {
            if ($row['obtained'] !== $previous) {
                $position = $index + 1;
                $previous = $row['obtained'];
            }
            $row['position'] = $position;

            return $row;
        });
    }
}
