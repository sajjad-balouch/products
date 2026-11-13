<?php

namespace App\Services;

use App\Models\BiometricDevice;
use App\Models\BiometricPunch;
use App\Models\School;
use App\Models\Staff;
use App\Models\StaffAttendance;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\IpUtils;

/**
 * Turns machine punches into staff attendance. The first punch of a duty is the arrival and the last
 * is the departure. A night shift that ends after midnight belongs to the day it started.
 */
class BiometricService
{
    public function __construct(private StaffAttendanceService $attendance) {}

    /**
     * The machine or kiosk, if it is registered and switched on, its school is active and may use it,
     * and in strict mode the request comes from the school's network. Notes when it was last heard from.
     */
    public function accept(?BiometricDevice $device, ?string $ip): ?BiometricDevice
    {
        $school = $device?->school;

        if (! $device?->is_active || ! $school?->is_active || ! $school->hasFeature('biometric') || $school->isLocked()) {
            return null;
        }

        $networks = array_filter(array_map('trim', preg_split('/[\s,]+/', (string) $school->check_in_networks)));
        if ($school->strict_attendance && $networks && ! IpUtils::checkIp((string) $ip, $networks)) {
            Log::warning('Attendance device refused: not on the school network', ['serial' => $device->serial_number, 'ip' => $ip]);

            return null;
        }

        $device->forceFill(['last_seen_at' => now(), 'last_ip' => $ip])->saveQuietly();

        return $device;
    }

    /** A punch for someone already known, as when the kiosk recognises a finger. */
    public function recordStaff(Staff $staff, Carbon $at, ?BiometricDevice $device, string $source): ?BiometricPunch
    {
        return $this->record(School::find($staff->school_id), $staff->biometric_id ?: 'S'.$staff->id, $at, $device, $source, $staff);
    }

    /** Saves one punch, once, and updates that person's day. Returns null for a repeat of a punch already saved. */
    public function record(School $school, string $deviceUserId, Carbon $at, ?BiometricDevice $device, string $source, ?Staff $staff = null): ?BiometricPunch
    {
        $deviceUserId = trim($deviceUserId);
        if ($deviceUserId === '') {
            return null;
        }

        $staff ??= Staff::withoutGlobalScopes()->where('school_id', $school->id)->where('biometric_id', $deviceUserId)->where('status', 'active')->first();

        $punch = BiometricPunch::withoutGlobalScopes()->firstOrCreate(
            ['school_id' => $school->id, 'device_user_id' => $deviceUserId, 'punched_at' => $at->copy()->setMicrosecond(0)],
            ['biometric_device_id' => $device?->id, 'staff_id' => $staff?->id, 'source' => $source],
        );

        if (! $punch->wasRecentlyCreated) {
            return null;
        }

        if ($staff) {
            $this->rebuild($staff, $school, $this->dutyDay($staff, $school, $at));
        }

        return $punch;
    }

    /** The day a punch belongs to. For a night shift, an early morning punch closes the shift that began the evening before. */
    public function dutyDay(Staff $staff, School $school, Carbon $at): Carbon
    {
        $start = $staff->dutyStart($school);
        $end = $staff->dutyEnd($school);

        if ($end < $start && $at->format('H:i') < Carbon::createFromFormat('H:i', $end)->addHours(4)->format('H:i')) {
            return $at->copy()->subDay()->startOfDay();
        }

        return $at->copy()->startOfDay();
    }

    /** Works out a day from all of its punches. Leave and half days set by the office are kept. */
    public function rebuild(Staff $staff, School $school, Carbon $day): void
    {
        $punches = BiometricPunch::withoutGlobalScopes()->where('staff_id', $staff->id)
            ->whereBetween('punched_at', [$day->copy()->startOfDay(), $day->copy()->addDay()->setTime(14, 0)])
            ->orderBy('punched_at')->get()
            ->filter(fn ($p) => $this->dutyDay($staff, $school, $p->punched_at)->isSameDay($day))->values();

        if ($punches->isEmpty()) {
            return;
        }

        $in = $punches->first()->punched_at;
        $out = $punches->last()->punched_at;
        $record = StaffAttendance::withoutGlobalScopes()->firstOrNew(['staff_id' => $staff->id, 'date' => $day->toDateString()], ['school_id' => $staff->school_id]);
        $checkIn = $in->format('H:i');
        $status = $record->status === 'H' ? 'H' : ($this->attendance->isLate($staff, $school, $checkIn) ? 'L' : 'P');

        $record->fill([
            'status' => $status,
            'check_in' => $checkIn,
            // A second punch within 15 minutes is a double tap, not leaving.
            'check_out' => $out->diffInMinutes($in, true) >= 15 ? $out->format('H:i') : null,
            'source' => 'device',
        ])->save();
    }

    /** Links punches that were waiting for this person's machine id, and works out those days. */
    public function claim(Staff $staff): int
    {
        $school = School::find($staff->school_id);
        $punches = BiometricPunch::withoutGlobalScopes()->where('school_id', $staff->school_id)
            ->where('device_user_id', $staff->biometric_id)->whereNull('staff_id')->get();

        if ($punches->isEmpty()) {
            return 0;
        }

        BiometricPunch::withoutGlobalScopes()->whereIn('id', $punches->pluck('id'))->update(['staff_id' => $staff->id]);
        $punches->map(fn ($p) => $this->dutyDay($staff, $school, $p->punched_at)->toDateString())->unique()
            ->each(fn ($day) => $this->rebuild($staff, $school, Carbon::parse($day)));

        return $punches->count();
    }

    /**
     * Reads a punch log from any machine: ZKTeco attlog files, CSV or text exports.
     * Each line needs the person's id first and a date and time somewhere after it.
     *
     * @return Collection<int, array{id: string, at: Carbon}>
     */
    public function parse(string $text): Collection
    {
        $patterns = [
            '/(\d{4})-(\d{1,2})-(\d{1,2})[ T](\d{1,2}):(\d{2})(?::(\d{2}))?/' => fn ($m) => [$m[1], $m[2], $m[3]],
            '#(\d{1,2})/(\d{1,2})/(\d{4})[ ,]+(\d{1,2}):(\d{2})(?::(\d{2}))?#' => fn ($m) => [$m[3], $m[2], $m[1]],
        ];

        return collect(preg_split('/\R/', $text))->map(function ($line) use ($patterns) {
            $id = trim((string) strtok(trim($line), " \t,;"), " \"'");
            if ($id === '' || ! preg_match('/^[\w-]{1,20}$/', $id)) {
                return null;
            }

            foreach ($patterns as $pattern => $ymd) {
                if (preg_match($pattern, $line, $m)) {
                    [$y, $mo, $d] = $ymd($m);
                    if (checkdate((int) $mo, (int) $d, (int) $y)) {
                        return ['id' => $id, 'at' => Carbon::create((int) $y, (int) $mo, (int) $d, (int) $m[4], (int) $m[5], (int) ($m[6] ?? 0))];
                    }
                }
            }

            return null;
        })->filter()->values();
    }
}
