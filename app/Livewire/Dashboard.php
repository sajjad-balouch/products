<?php

namespace App\Livewire;

use App\Models\Attendance;
use App\Models\Expense;
use App\Models\FeeVoucher;
use App\Models\Inquiry;
use App\Models\Message;
use App\Models\Payment;
use App\Models\PaymentProof;
use App\Models\Section;
use App\Models\Student;
use App\Models\TimetableEntry;
use App\Services\RiskService;
use App\Support\Navigation;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Dashboard')]
class Dashboard extends Component
{
    public function render(RiskService $risk)
    {
        $user = Auth::user();

        return $user->isRole('teacher')
            ? view('livewire.dashboard-teacher', $this->teacherData($user))
            : view('livewire.dashboard', $this->officeData($user, $risk));
    }

    private function officeData($user, RiskService $risk): array
    {
        $month = now()->startOfMonth();
        $vouchers = FeeVoucher::whereDate('month', $month)->where('status', '!=', 'void')->get();
        $billed = (int) $vouchers->sum('total');
        $collected = (int) $vouchers->sum('paid');

        $marked = Attendance::whereDate('date', now())->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $markedTotal = $marked->sum();

        $trend = collect(range(5, 0))->map(function ($back) {
            $m = now()->startOfMonth()->subMonths($back);

            return ['label' => $m->format('M'), 'amount' => (int) Payment::valid()->whereBetween('paid_on', [$m->copy(), $m->copy()->endOfMonth()])->sum('amount')];
        });

        $sectionsMarked = Attendance::whereDate('date', now())->distinct()->pluck('section_id');
        $flags = $user->isRole('admin') ? $risk->assess($user->school) : collect();

        return [
            'students' => Student::active()->count(),
            'billed' => $billed,
            'collected' => $collected,
            'recovery' => $billed ? round($collected / $billed * 100) : 0,
            'overdue' => (int) FeeVoucher::open()->whereDate('due_on', '<', now())->get()->sum('balance'),
            'todayCash' => (int) Payment::valid()->whereDate('paid_on', now())->sum('amount'),
            'expenses' => (int) Expense::whereBetween('spent_on', [$month, $month->copy()->endOfMonth()])->sum('amount'),
            'attendancePercent' => $markedTotal ? round((($marked['P'] ?? 0) + ($marked['L'] ?? 0)) / $markedTotal * 100) : null,
            'absentToday' => $marked['A'] ?? 0,
            'pendingProofs' => PaymentProof::where('status', 'pending')->count(),
            'queuedMessages' => Message::where('status', 'queued')->count(),
            'unmarked' => Section::with('schoolClass')->whereHas('students', fn ($q) => $q->where('status', 'active'))->whereNotIn('id', $sectionsMarked)->get(),
            'followUps' => Inquiry::whereDate('follow_up_on', '<=', now())->whereNotIn('stage', ['admitted', 'lost'])->orderBy('follow_up_on')->limit(5)->get(),
            'newInquiries' => Inquiry::where('stage', 'new')->count(),
            'highRisk' => $flags->where('level', 'high')->take(5),
            'riskCount' => $flags->count(),
            'trend' => $trend,
            'isAdmin' => $user->isRole('admin'),
            'needsSetup' => Section::count() === 0,
            'actions' => $this->actions($user),
            'greeting' => $this->greeting(),
        ];
    }

    private function greeting(): string
    {
        $hour = now()->hour;

        return $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
    }

    /** The everyday jobs as big buttons, only those this user can open. */
    private function actions($user): array
    {
        $routes = Navigation::routes($user);
        $all = [
            ['attendance.mark', 'Mark attendance', 'Today, class by class', 'check-square', 'brand'],
            ['fees.vouchers', 'Collect fee', 'Find a family and take payment', 'wallet', 'green'],
            ['students.index', 'Students', 'Search, add or edit', 'users', 'blue'],
            ['fees.generate', 'Generate vouchers', "This month's fee vouchers", 'file-plus', 'amber'],
            ['exams.marks', 'Enter marks', 'Marks by class and subject', 'pencil', 'violet'],
            ['diary', 'Daily diary', 'Homework for parents', 'book-open', 'blue'],
            ['staff.attendance', 'Staff attendance', 'Teachers, guards and all staff', 'id-card', 'brand'],
            ['messages.index', 'WhatsApp outbox', 'Messages ready to send', 'message', 'green'],
            ['fees.closing', 'Cash closing', "Today's cash in hand", 'cash', 'amber'],
            ['timetable', 'Timetable', 'Who teaches what, when', 'clock', 'violet'],
        ];

        return collect($all)->filter(fn ($a) => in_array($a[0], $routes, true))->take(8)->values()->all();
    }

    private function teacherData($user): array
    {
        $staffId = $user->staffProfile?->id;
        $ids = $user->teachableSectionIds() ?? [];
        $marked = Attendance::whereDate('date', now())->whereIn('section_id', $ids)->distinct()->pluck('section_id')->all();

        return [
            'today' => $staffId
                ? TimetableEntry::live()->with(['period', 'subject', 'section.schoolClass'])->where('staff_id', $staffId)->where('day', now()->dayOfWeekIso)->get()->sortBy(fn ($e) => $e->period->starts_at)
                : collect(),
            'sections' => Section::with('schoolClass')->whereIn('id', $ids)->get(),
            'marked' => $marked,
            'actions' => $this->actions($user),
            'greeting' => $this->greeting(),
        ];
    }
}
