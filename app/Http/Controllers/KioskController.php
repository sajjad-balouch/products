<?php

namespace App\Http\Controllers;

use App\Models\BiometricDevice;
use App\Models\BiometricPunch;
use App\Models\FingerprintTemplate;
use App\Models\Staff;
use App\Models\StaffAttendance;
use App\Services\BiometricService;
use App\Services\FingerprintMatcher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use RuntimeException;

/**
 * The fingerprint kiosk: a school computer with a USB reader (such as the U.are.U 5100) showing this page.
 * Nobody signs in on it. The computer is recognised by a cookie set from the one-time kiosk link, and
 * making a new link for the kiosk stops the old computer at once.
 */
class KioskController extends Controller
{
    private const COOKIE = 'attendance_kiosk';

    public function __construct(private BiometricService $biometric) {}

    private function kiosk(Request $request): ?BiometricDevice
    {
        [$id, $hash] = array_pad(explode('|', (string) $request->cookie(self::COOKIE), 2), 2, null);
        $device = $id ? BiometricDevice::withoutGlobalScopes()->where('type', 'kiosk')->find((int) $id) : null;

        if (! $device || ! $device->token_hash || ! hash_equals($device->token_hash, (string) $hash)) {
            return null;
        }

        return $this->biometric->accept($device, $request->ip());
    }

    /** Opened once on the kiosk computer, from the link shown when the kiosk was added. */
    public function open(Request $request, string $serial)
    {
        $device = BiometricDevice::withoutGlobalScopes()->where('type', 'kiosk')->where('serial_number', $serial)->first();
        $hash = hash('sha256', (string) $request->query('key', ''));
        abort_unless($device && $device->token_hash && hash_equals($device->token_hash, $hash), 403, 'This kiosk link is not valid any more. Make a new one on the Attendance machines page.');

        Cookie::queue(Cookie::make(self::COOKIE, $device->id.'|'.$hash, 60 * 24 * 365 * 5, null, null, $request->isSecure(), true, false, 'Strict'));

        return redirect()->route('kiosk');
    }

    public function show(Request $request)
    {
        $device = $this->kiosk($request);
        abort_unless($device, 403, 'This computer is not set up as an attendance kiosk, or it is not on the school network.');

        return view('kiosk', ['device' => $device, 'school' => $device->school, 'helper' => FingerprintMatcher::browserSettings()]);
    }

    /** Registered fingers of the kiosk school's active staff. */
    private function templates(BiometricDevice $device)
    {
        return FingerprintTemplate::withoutGlobalScopes()->where('school_id', $device->school_id)
            ->whereHas('staff', fn ($q) => $q->withoutGlobalScopes()->where('status', 'active'))->get(['staff_id', 'template']);
    }

    /** With the helper on the kiosk computer, the page fetches the templates to compare against. */
    public function candidates(Request $request)
    {
        abort_unless($request->header('X-Kiosk') === '1' && FingerprintMatcher::local(), 400);
        $device = $this->kiosk($request);
        abort_unless($device, 403);

        return response()->json($this->templates($device)->map(fn ($t) => ['id' => (string) $t->staff_id, 'template' => $t->template])->values())
            ->header('Cache-Control', 'no-store');
    }

    /** A finger was pressed. Finds who it is and marks them in, or out if they are already in. */
    public function punch(Request $request, FingerprintMatcher $matcher)
    {
        // Sent only by the kiosk page. Another website cannot add this header, so it cannot post here through the kiosk's cookie.
        abort_unless($request->header('X-Kiosk') === '1', 400);
        $device = $this->kiosk($request);
        abort_unless($device, 403);

        if (FingerprintMatcher::local()) {
            // The helper on the kiosk computer already compared the finger. The school's own kiosk, on its
            // network in strict mode, is trusted to say who it was, but only one of its staff with a finger
            // registered, and only with a score that reaches the threshold.
            $data = $request->validate(['staff_id' => 'required|integer', 'score' => 'required|numeric|min:'.config('services.fingerprint.threshold')]);
            $match = ['staff_id' => $this->templates($device)->contains('staff_id', (int) $data['staff_id']) ? (int) $data['staff_id'] : null];
        } else {
            $data = $request->validate(['image' => 'required|string|max:4000000']);

            try {
                $match = $matcher->identify($data['image'], $this->templates($device));
            } catch (RuntimeException $e) {
                return response()->json(['message' => $e->getMessage()], 503);
            }
        }

        $staff = $match['staff_id'] ? Staff::withoutGlobalScopes()->where('school_id', $device->school_id)->find($match['staff_id']) : null;
        if (! $staff) {
            return response()->json(['message' => 'Not recognised. Press the same finger again, or see the office.'], 422);
        }

        // A second press within two minutes is the same arrival, not leaving.
        $recent = BiometricPunch::withoutGlobalScopes()->where('staff_id', $staff->id)->where('punched_at', '>=', now()->subMinutes(2))->latest('punched_at')->first();
        if (! $recent) {
            $this->biometric->recordStaff($staff, now(), $device, 'kiosk');
        }

        $day = StaffAttendance::withoutGlobalScopes()->where('staff_id', $staff->id)->whereDate('date', $this->biometric->dutyDay($staff, $device->school, now()))->first();
        $leaving = (bool) $day?->check_out;

        return response()->json([
            'name' => $staff->name,
            'time' => now()->format('H:i'),
            'action' => $recent ? 'Already marked' : ($leaving ? 'Goodbye' : 'Welcome'),
            'detail' => $leaving ? 'Left at '.$day->check_out : 'Arrived at '.($day?->check_in ?? now()->format('H:i')).($day?->status === 'L' ? ', late' : ''),
        ]);
    }
}
