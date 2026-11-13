<?php

namespace App\Services;

use App\Models\School;
use App\Models\Staff;
use App\Models\StaffAttendance;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\IpUtils;

/** Staff attendance rules: who counts as late, what a month adds up to, and staff checking themselves in. */
class StaffAttendanceService
{
    public const STATUSES = ['P' => 'Present', 'L' => 'Late', 'H' => 'Half day', 'A' => 'Absent', 'V' => 'Leave'];

    /** Late means arriving after the duty start plus the school's grace minutes. */
    public function isLate(Staff $staff, School $school, ?string $checkIn): bool
    {
        if (! $checkIn) {
            return false;
        }

        $limit = Carbon::createFromFormat('H:i', $staff->dutyStart($school))->addMinutes($school->staff_late_minutes);

        return Carbon::createFromFormat('H:i', $checkIn)->gt($limit);
    }

    /**
     * Counts per person for a month, and the days to take off pay: each absence, half a day per half day,
     * and one day for every set number of lates when the school uses that rule.
     *
     * @return Collection<int, array{P: int, L: int, H: int, A: int, V: int, marked: int, deducted: float}>
     */
    public function month(School $school, CarbonInterface $month, Collection $staff): Collection
    {
        $rows = StaffAttendance::whereIn('staff_id', $staff->pluck('id'))
            ->whereBetween('date', [$month->copy()->startOfMonth(), $month->copy()->endOfMonth()])
            ->get(['staff_id', 'status'])->groupBy('staff_id');

        return $staff->mapWithKeys(function ($person) use ($rows, $school) {
            $counts = array_merge(['P' => 0, 'L' => 0, 'H' => 0, 'A' => 0, 'V' => 0], $rows->get($person->id)?->countBy('status')->all() ?? []);
            $lateDays = $school->lates_per_absent > 0 ? intdiv($counts['L'], $school->lates_per_absent) : 0;

            return [$person->id => $counts + [
                'marked' => array_sum($counts),
                'deducted' => $counts['A'] + $counts['H'] / 2 + $lateDays,
            ]];
        });
    }

    /**
     * In strict mode staff can mark themselves only from the school: its internet address, or within
     * its radius by the phone's location. Returns why not, or null when allowed.
     */
    public function refusal(School $school, ?string $ip, ?float $lat, ?float $lng): ?string
    {
        if (! $school->strict_attendance) {
            return null;
        }

        $networks = array_filter(array_map('trim', preg_split('/[\s,]+/', (string) $school->check_in_networks)));
        $hasPlace = $school->check_in_lat !== null && $school->check_in_lng !== null;

        if (! $networks && ! $hasPlace) {
            return 'Your school has not set its network or location yet, so checking in from a phone is off. Please use the attendance machine or the office.';
        }
        if ($networks && $ip && IpUtils::checkIp($ip, $networks)) {
            return null;
        }
        if ($hasPlace && $lat !== null && $lng !== null && $this->metres($lat, $lng, (float) $school->check_in_lat, (float) $school->check_in_lng) <= $school->check_in_radius) {
            return null;
        }

        return $hasPlace && ($lat === null || $lng === null)
            ? 'Allow location so the system can see you are at school, then try again.'
            : 'You can only check in at school. Connect to the school Wi-Fi or come inside the school grounds.';
    }

    /** Distance between two points on the earth, in metres. */
    public function metres(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $r = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * $r * asin(min(1, sqrt($a)));
    }

    /** A staff member marking themselves in. Only once a day, and only for today. */
    public function checkIn(Staff $staff, School $school, ?Carbon $at = null): StaffAttendance
    {
        $at ??= now();
        $record = StaffAttendance::firstOrNew(['staff_id' => $staff->id, 'date' => $at->toDateString()], ['school_id' => $staff->school_id]);

        if ($record->check_in) {
            throw new InvalidArgumentException('You already checked in today at '.$record->check_in.'.');
        }

        $time = $at->format('H:i');
        $record->fill(['check_in' => $time, 'status' => $this->isLate($staff, $school, $time) ? 'L' : 'P', 'source' => 'self'])->save();

        return $record;
    }

    public function checkOut(Staff $staff, ?Carbon $at = null): StaffAttendance
    {
        $at ??= now();
        $record = StaffAttendance::where(['staff_id' => $staff->id])->whereDate('date', $at->toDateString())->first();

        if (! $record?->check_in) {
            throw new InvalidArgumentException('Check in first.');
        }

        $record->update(['check_out' => $at->format('H:i')]);

        return $record;
    }
}
