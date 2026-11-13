<?php

namespace App\Http\Controllers;

use App\Models\GatewayDevice;
use App\Services\Sms;
use App\Services\SmsGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The API of your own SMS gateway phone (the MSK SMS Gateway app). Server link: {APP_URL}/sms-gateway
 * Every request has the header "Authorization: Bearer gw_..." (given when the phone is added in owner panel → Messaging).
 *
 * POST /sms-gateway/pull    the next SMS to send (and the phone's battery and signal)
 * POST /sms-gateway/report  which SMS went and which did not
 */
class GatewayDeviceController extends Controller
{
    public function pull(Request $request): JsonResponse
    {
        $device = $this->device($request);
        $data = $request->validate([
            'max' => 'nullable|integer|min:1|max:50',
            'battery' => 'nullable|integer|min:0|max:100',
            'signal' => 'nullable|string|max:30',
            'app_version' => 'nullable|string|max:20',
        ]);
        $device->update([
            'last_seen_at' => now(),
            'last_status' => array_merge($device->last_status ?? [], array_filter(array_intersect_key($data, array_flip(['battery', 'signal', 'app_version'])), fn ($v) => $v !== null)),
        ]);

        $messages = Sms::driver() === 'gateway' ? SmsGateway::claim($device, (int) ($data['max'] ?? 10)) : collect();

        return response()->json([
            'success' => true,
            'active' => $device->is_active,
            'poll_seconds' => 20,
            'delay_ms' => (int) Sms::settings()->get('sms_delay_ms', 3000), // a pause between SMS so the SIM is not blocked
            'messages' => $messages->map(fn ($m) => ['id' => $m->id, 'to' => '+'.\App\Support\Phone::international($m->phone), 'body' => $m->body])->values(),
        ]);
    }

    public function report(Request $request): JsonResponse
    {
        $device = $this->device($request);
        $data = $request->validate([
            'results' => 'required|array|max:100',
            'results.*.id' => 'required|integer',
            'results.*.status' => 'required|in:sent,failed',
            'results.*.error' => 'nullable|string|max:300',
        ]);
        $device->update(['last_seen_at' => now()]);

        $accepted = 0;
        foreach ($data['results'] as $r) {
            $accepted += SmsGateway::report($device, (int) $r['id'], $r['status'] === 'sent', $r['error'] ?? null) ? 1 : 0;
        }

        return response()->json(['success' => true, 'accepted' => $accepted]);
    }

    private function device(Request $request): GatewayDevice
    {
        $device = GatewayDevice::findByToken($request->bearerToken());
        abort_unless($device, 401, 'The gateway token is not right.');

        return $device;
    }
}
