<?php

namespace App\Livewire\Staff;

use App\Models\StaffAttendance;
use App\Services\StaffAttendanceService;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use Livewire\Component;

/** A dashboard card where staff with a login mark their own arrival and departure, when the school allows it. */
class CheckIn extends Component
{
    private function staff()
    {
        $user = Auth::user();

        return $user->school?->staff_self_check_in && $user->school->hasFeature('staff') ? $user->staffProfile : null;
    }

    public function checkIn(StaffAttendanceService $service, ?float $lat = null, ?float $lng = null): void
    {
        $this->run($lat, $lng, fn ($staff) => $service->checkIn($staff, Auth::user()->school));
    }

    public function checkOut(StaffAttendanceService $service, ?float $lat = null, ?float $lng = null): void
    {
        $this->run($lat, $lng, fn ($staff) => $service->checkOut($staff));
    }

    /** In strict mode the school's network or grounds are checked first, on the server, every time. */
    private function run(?float $lat, ?float $lng, callable $action): void
    {
        $staff = $this->staff();
        abort_unless($staff, 403);

        $refusal = app(StaffAttendanceService::class)->refusal(Auth::user()->school, request()->ip(), $lat, $lng);
        if ($refusal) {
            $this->dispatch('toast', message: $refusal, error: true);

            return;
        }

        try {
            $action($staff);
        } catch (InvalidArgumentException $e) {
            $this->dispatch('toast', message: $e->getMessage(), error: true);
        }
    }

    public function render()
    {
        $staff = $this->staff();
        $school = Auth::user()->school;

        return view('livewire.staff.check-in', [
            'staff' => $staff,
            'today' => $staff ? StaffAttendance::where('staff_id', $staff->id)->whereDate('date', today())->first() : null,
            'school' => $school,
            // Ask the phone for its location only when the school checks it.
            'needsLocation' => $school?->strict_attendance && $school->check_in_lat !== null,
        ]);
    }
}
