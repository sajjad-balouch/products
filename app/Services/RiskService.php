<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\Exam;
use App\Models\FeeVoucher;
use App\Models\Mark;
use App\Models\School;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Early warning: a rule-based score, not a prediction. Every point has a
 * written reason a principal can read and check, so nothing is a black box.
 */
class RiskService
{
    public const HIGH = 50;

    public const WATCH = 25;

    /** @return Collection<int, array{student: Student, score: int, level: string, reasons: array<int, string>}> sorted worst first */
    public function assess(School $school, ?Carbon $today = null): Collection
    {
        $today = ($today ?? now())->copy()->startOfDay();

        $students = Student::withoutGlobalScopes()->where('school_id', $school->id)->where('status', 'active')
            ->with(['section' => fn ($q) => $q->withoutGlobalScopes()->with(['schoolClass' => fn ($c) => $c->withoutGlobalScopes()])])
            ->get();

        $attendance = $this->attendanceStats($school->id, $today);
        $recentAbsences = $this->consecutiveAbsences($school->id, $today);
        $marks = $this->markTrends($school->id);
        $overdue = $this->overdueByGuardian($school->id, $today);

        return $students->map(function (Student $student) use ($school, $attendance, $recentAbsences, $marks, $overdue, $today) {
            $score = 0;
            $reasons = [];

            if ($stat = $attendance->get($student->id)) {
                $percent = (int) round($stat['present'] / max(1, $stat['total']) * 100);
                if ($stat['total'] >= 5 && $percent < $school->risk_attendance_threshold) {
                    $score += $percent < 60 ? 40 : 25;
                    $reasons[] = "Attendance {$percent}% in the last 30 days";
                }
            }

            if (($streak = $recentAbsences->get($student->id, 0)) >= 3) {
                $score += 30;
                $reasons[] = "Absent {$streak} school days in a row";
            }

            if ($trend = $marks->get($student->id)) {
                if ($trend['latest'] < 40) {
                    $score += 30;
                    $reasons[] = "Scored {$trend['latest']}% in the latest exam";
                }
                if ($trend['previous'] !== null && $trend['previous'] - $trend['latest'] >= 10) {
                    $drop = $trend['previous'] - $trend['latest'];
                    $score += 20;
                    $reasons[] = "Marks fell {$drop} points since the previous exam";
                }
            }

            if ($oldest = $overdue->get($student->guardian_id)) {
                $days = (int) $oldest->diffInDays($today);
                if ($days >= 60) {
                    $score += 15;
                    $reasons[] = "Family fee overdue for {$days} days";
                }
            }

            return [
                'student' => $student,
                'score' => min(100, $score),
                'level' => $score >= self::HIGH ? 'high' : ($score >= self::WATCH ? 'watch' : 'ok'),
                'reasons' => $reasons,
            ];
        })->filter(fn ($row) => $row['level'] !== 'ok')->sortByDesc('score')->values();
    }

    private function attendanceStats(int $schoolId, Carbon $today): Collection
    {
        return Attendance::withoutGlobalScopes()->where('school_id', $schoolId)
            ->whereDate('date', '>=', $today->copy()->subDays(30))
            ->selectRaw("student_id, count(*) as total, sum(case when status in ('P','L') then 1 else 0 end) as present")
            ->groupBy('student_id')->get()
            ->mapWithKeys(fn ($r) => [$r->student_id => ['total' => (int) $r->total, 'present' => (int) $r->present]]);
    }

    /** Counts back from the student's most recent marked days until a day they attended. */
    private function consecutiveAbsences(int $schoolId, Carbon $today): Collection
    {
        return Attendance::withoutGlobalScopes()->where('school_id', $schoolId)
            ->whereDate('date', '>=', $today->copy()->subDays(21))
            ->orderByDesc('date')->get(['student_id', 'status'])
            ->groupBy('student_id')
            ->map(function ($rows) {
                $streak = 0;
                foreach ($rows as $row) {
                    if ($row->status !== 'A') {
                        break;
                    }
                    $streak++;
                }

                return $streak;
            });
    }

    /** Percent in each student's two most recent published exams. */
    private function markTrends(int $schoolId): Collection
    {
        $examIds = Exam::withoutGlobalScopes()->where('school_id', $schoolId)->where('is_published', true)
            ->orderByDesc('starts_on')->orderByDesc('id')->limit(2)->pluck('id')->all();

        if (! $examIds) {
            return collect();
        }

        $byStudent = Mark::withoutGlobalScopes()->where('marks.school_id', $schoolId)
            ->join('exam_papers', 'exam_papers.id', '=', 'marks.exam_paper_id')
            ->whereIn('exam_papers.exam_id', $examIds)
            ->selectRaw('marks.student_id, exam_papers.exam_id, sum(coalesce(marks.obtained, 0)) as got, sum(exam_papers.total_marks) as out_of')
            ->groupBy('marks.student_id', 'exam_papers.exam_id')->get()->groupBy('student_id');

        return $byStudent->map(function ($rows) use ($examIds) {
            $percent = fn ($examId) => ($row = $rows->firstWhere('exam_id', $examId)) && $row->out_of > 0
                ? (int) round($row->got / $row->out_of * 100) : null;

            $latest = $percent($examIds[0]);

            return $latest === null ? null : ['latest' => $latest, 'previous' => isset($examIds[1]) ? $percent($examIds[1]) : null];
        })->filter();
    }

    private function overdueByGuardian(int $schoolId, Carbon $today): Collection
    {
        return FeeVoucher::withoutGlobalScopes()->where('school_id', $schoolId)->open()
            ->whereDate('due_on', '<', $today)->orderBy('due_on')->get(['guardian_id', 'due_on'])
            ->groupBy('guardian_id')->map(fn ($rows) => $rows->first()->due_on);
    }
}
