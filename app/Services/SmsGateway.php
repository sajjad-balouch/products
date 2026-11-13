<?php

namespace App\Services;

use App\Models\GatewayDevice;
use App\Models\Message;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Your own SMS gateway: an Android phone with a SIM.
 *  1. Sms::deliver() puts a message in line (channel sms, status queued).
 *  2. claim(): every few seconds the phone takes the next messages (status sending).
 *  3. report(): the phone says sent or failed. A failed one is tried again, up to 3 times.
 */
class SmsGateway
{
    public const MAX_ATTEMPTS = 3;

    /** A phone that took messages and went quiet this long gives them back to the line. */
    public const CLAIM_TIMEOUT_MINUTES = 10;

    private static function line()
    {
        return Message::withoutGlobalScopes()->where('channel', 'sms');
    }

    /** @return Collection<int, Message> */
    public static function claim(GatewayDevice $device, int $max = 10): Collection
    {
        self::releaseStale();
        $room = $device->daily_limit > 0 ? max(0, $device->daily_limit - $device->sentToday()) : $max;
        $take = min($max, $room);
        if (! $device->is_active || $take <= 0) {
            return collect();
        }

        $claimed = [];
        foreach (self::line()->where('status', 'queued')->orderBy('id')->limit($take * 3)->pluck('id') as $id) {
            // Only one phone can win a message, even when two ask at once.
            $won = self::line()->whereKey($id)->where('status', 'queued')->update([
                'status' => 'sending', 'device_id' => $device->id, 'claimed_at' => now(), 'attempts' => DB::raw('attempts + 1'), 'updated_at' => now(),
            ]);
            if ($won) {
                $claimed[] = $id;
            }
            if (count($claimed) >= $take) {
                break;
            }
        }

        return self::line()->whereIn('id', $claimed)->orderBy('id')->get();
    }

    /** The phone's answer. Only the phone that took the message can answer for it. */
    public static function report(GatewayDevice $device, int $id, bool $sent, ?string $error = null): ?Message
    {
        $message = self::line()->whereKey($id)->where('device_id', $device->id)->where('status', 'sending')->first();
        if (! $message) {
            return null;
        }
        if ($sent) {
            $message->update(['status' => 'sent', 'sent_at' => now(), 'error' => null]);

            return $message;
        }
        $error = mb_substr(trim((string) $error) ?: 'The phone could not send it', 0, 255);
        $message->update($message->attempts < self::MAX_ATTEMPTS
            ? ['status' => 'queued', 'device_id' => null, 'claimed_at' => null, 'error' => $error]
            : ['status' => 'failed', 'error' => $error]);

        return $message;
    }

    /** The phone switched off or lost internet: what it took goes back in line. */
    public static function releaseStale(): void
    {
        foreach (self::line()->where('status', 'sending')->where('claimed_at', '<', now()->subMinutes(self::CLAIM_TIMEOUT_MINUTES))->get() as $m) {
            $m->update($m->attempts >= self::MAX_ATTEMPTS
                ? ['status' => 'failed', 'error' => 'No answer from the gateway phone']
                : ['status' => 'queued', 'device_id' => null, 'claimed_at' => null, 'error' => 'No answer from the phone, trying again']);
        }
    }

    /** The owner stops everything waiting in line. */
    public static function cancelQueued(): int
    {
        return self::line()->where('status', 'queued')->update(['status' => 'failed', 'error' => 'Stopped by the software owner', 'updated_at' => now()]);
    }
}
