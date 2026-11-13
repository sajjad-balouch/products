<?php

namespace App\Http\Controllers;

use App\Models\BiometricDevice;
use App\Services\BiometricService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Where attendance machines send punches. ZKTeco and the many machines that copy it use the
 * "ADMS" push protocol (/iclock/...): set the machine's server address to this website. Other
 * machines or attendance software can send JSON to /api/biometric/punches with the device token.
 */
class BiometricController extends Controller
{
    public function __construct(private BiometricService $biometric) {}

    /** The machine, if it is registered, active, its school may use it, and in strict mode it is on the school's network. */
    private function device(?BiometricDevice $device, Request $request): ?BiometricDevice
    {
        // A kiosk sends fingerprint pictures, not punches, so it may not use these addresses.
        return $device?->type === 'kiosk' ? null : $this->biometric->accept($device, $request->ip());
    }

    private function text(string $body, int $status = 200)
    {
        return response($body, $status)->header('Content-Type', 'text/plain');
    }

    private function bySerial(Request $request): ?BiometricDevice
    {
        $serial = (string) $request->query('SN', '');

        return $this->device($serial !== '' ? BiometricDevice::withoutGlobalScopes()->where('serial_number', $serial)->first() : null, $request);
    }

    /** GET: the machine asks for its settings. POST: it sends punches (table=ATTLOG) or other records, which are acknowledged. */
    public function cdata(Request $request)
    {
        $device = $this->bySerial($request);
        if (! $device) {
            return $this->text('Unknown device', 404);
        }

        if ($request->isMethod('get')) {
            return $this->text(implode("\n", [
                'GET OPTION FROM: '.$device->serial_number,
                'ATTLOGStamp=None', 'OPERLOGStamp=9999', 'ATTPHOTOStamp=None',
                'ErrorDelay=30', 'Delay=10', 'TransTimes=00:00;14:05', 'TransInterval=1',
                'TransFlag=TransData AttLog', 'TimeZone=5', 'Realtime=1', 'Encrypt=None',
            ])."\n");
        }

        if (strtoupper((string) $request->query('table')) !== 'ATTLOG') {
            return $this->text('OK');
        }

        $saved = 0;
        foreach ($this->biometric->parse($request->getContent()) as $punch) {
            $saved += $this->biometric->record($device->school, $punch['id'], $punch['at'], $device, 'push') ? 1 : 0;
        }

        return $this->text('OK: '.$saved);
    }

    /** The machine asks if there are commands for it. There never are. */
    public function getRequest(Request $request)
    {
        return $this->bySerial($request) ? $this->text('OK') : $this->text('Unknown device', 404);
    }

    public function deviceCommand(Request $request)
    {
        return $this->bySerial($request) ? $this->text('OK') : $this->text('Unknown device', 404);
    }

    /** JSON from any other machine or software: {"punches": [{"user_id": "15", "time": "2026-11-03 08:01:00"}]}. */
    public function api(Request $request)
    {
        $token = (string) $request->header('X-Device-Token', '');
        $device = $this->device($token !== '' ? BiometricDevice::withoutGlobalScopes()->where('token_hash', hash('sha256', $token))->first() : null, $request);
        abort_unless($device, 401, 'Unknown or inactive device');

        $data = $request->validate([
            'punches' => 'required|array|max:1000',
            'punches.*.user_id' => 'required|string|max:20',
            'punches.*.time' => 'required|date',
        ]);

        $saved = 0;
        foreach ($data['punches'] as $punch) {
            $saved += $this->biometric->record($device->school, $punch['user_id'], Carbon::parse($punch['time']), $device, 'api') ? 1 : 0;
        }

        return response()->json(['received' => count($data['punches']), 'saved' => $saved]);
    }
}
