<?php

namespace App\Livewire\Staff;

use App\Models\AuditLog;
use App\Models\Payroll;
use App\Models\Staff;
use App\Services\StaffAttendanceService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Payroll')]
class PayrollRun extends Component
{
    public string $month = '';

    public function mount(): void
    {
        $this->month = now()->format('Y-m');
    }

    private function monthDate(): Carbon
    {
        return preg_match('/^\d{4}-\d{2}$/', $this->month) ? Carbon::parse($this->month.'-01') : now()->startOfMonth();
    }

    /**
     * Salary minus one day's pay (salary / 30) per day off pay: each absence, half a day per half day,
     * and a day per set number of lates if the school uses that rule. Paid slips are never recalculated.
     */
    public function generate(StaffAttendanceService $attendance): void
    {
        $month = $this->monthDate();
        $created = 0;
        $staff = Staff::where('status', 'active')->get();
        $totals = $attendance->month(Auth::user()->school, $month, $staff);

        foreach ($staff as $person) {
            $slip = Payroll::where('staff_id', $person->id)->whereDate('month', $month)->first();
            if ($slip?->paid_on) {
                continue;
            }

            $t = $totals[$person->id];
            $gross = $person->basic_salary + $person->allowance;
            $deduction = (int) round($gross / 30 * $t['deducted']);

            $values = ['basic' => $person->basic_salary, 'allowance' => $person->allowance, 'absent_days' => $t['A'], 'half_days' => $t['H'], 'late_days' => $t['L'], 'deducted_days' => $t['deducted'], 'deduction' => $deduction, 'net' => max(0, $gross - $deduction)];

            if ($slip) {
                $slip->update($values);
            } else {
                Payroll::create($values + ['staff_id' => $person->id, 'month' => $month]);
                $created++;
            }
        }

        $this->dispatch('toast', message: "Payroll ready. $created new salary slips.");
    }

    public function markPaid(int $id): void
    {
        $slip = Payroll::findOrFail($id);
        $slip->update(['paid_on' => $slip->paid_on ? null : now()]);
        AuditLog::record($slip->paid_on ? 'salary_paid' : 'salary_unpaid', $slip, null, null, 'Rs '.$slip->net);
    }

    public function render()
    {
        $slips = Payroll::with('staff')->whereDate('month', $this->monthDate())->get()->sortBy('staff.name');

        return view('livewire.staff.payroll-run', ['slips' => $slips]);
    }
}
