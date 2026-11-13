<?php

namespace App\Livewire\Staff;

use App\Models\Staff;
use App\Models\StaffAttendance;
use App\Services\StaffAttendanceService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/** The month at a glance: every staff member by day, with what comes off their pay. */
#[Title('Staff attendance register')]
class AttendanceRegister extends Component
{
    #[Url]
    public string $month = '';

    #[Url]
    public string $category = '';

    public function mount(): void
    {
        $this->month = preg_match('/^\d{4}-\d{2}$/', $this->month) ? $this->month : now()->format('Y-m');
    }

    public function render(StaffAttendanceService $service)
    {
        $month = Carbon::parse(preg_match('/^\d{4}-\d{2}$/', $this->month) ? $this->month.'-01' : now()->startOfMonth());
        $school = Auth::user()->school;
        $staff = Staff::where('status', 'active')->when($this->category !== '', fn ($q) => $q->where('category', $this->category))->orderBy('category')->orderBy('name')->get();

        return view('livewire.staff.attendance-register', [
            'monthDate' => $month,
            'days' => collect(range(1, $month->daysInMonth))->map(fn ($d) => $month->copy()->day($d)),
            'staff' => $staff,
            'marks' => StaffAttendance::whereIn('staff_id', $staff->pluck('id'))->whereBetween('date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
                ->get()->keyBy(fn ($r) => $r->staff_id.'-'.$r->date->day),
            'totals' => $service->month($school, $month, $staff),
            'school' => $school,
            'months' => collect(range(0, 11))->mapWithKeys(fn ($i) => [now()->subMonthsNoOverflow($i)->format('Y-m') => now()->subMonthsNoOverflow($i)->format('F Y')]),
        ]);
    }
}
