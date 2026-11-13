<?php

namespace App\Services;

use App\Models\Message;
use App\Models\MessagingSetting;
use App\Models\School;
use App\Support\Phone;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Sends messages the way the software owner chose (owner panel → Messaging):
 *  - http:           any WhatsApp or SMS company's API (UltraMsg, GreenAPI, a Pakistani SMS company...)
 *  - whatsapp_cloud: the official WhatsApp Business API from Meta
 *  - gateway:        your own Android phone with a SIM; messages wait in line and the phone picks them up
 *  - off:            nothing automatic; messages wait in the WhatsApp outbox to send with one tap
 * A school or business uses it only when its plan has the "sms" feature.
 */
class Sms
{
    public const DRIVERS = [
        'off' => ['Off: WhatsApp outbox only', 'Messages wait in each school\'s outbox. Staff send each one with one tap.', 'x-circle'],
        'gateway' => ['Your own SMS gateway', 'Your Android phone and SIM send the SMS. Install the MSK SMS Gateway app on it.', 'phone'],
        'http' => ['WhatsApp / SMS company API', 'UltraMsg, GreenAPI, Wati, or any Pakistani SMS company that gives you a link.', 'link'],
        'whatsapp_cloud' => ['WhatsApp Business API (Meta)', 'The official API. Messages need a template approved by Meta.', 'message'],
    ];

    public static function settings(): MessagingSetting
    {
        if (! app()->bound('messaging.settings')) {
            app()->instance('messaging.settings', MessagingSetting::current());
        }

        return app('messaging.settings');
    }

    /** After the owner saves: read the settings again. */
    public static function flush(): void
    {
        app()->forgetInstance('messaging.settings');
    }

    public static function driver(): string
    {
        $driver = self::settings()->driver;

        return in_array($driver, ['http', 'whatsapp_cloud', 'gateway', 'log'], true) ? $driver : 'off';
    }

    /** Something automatic is set up and ready. */
    public static function enabled(): bool
    {
        $s = self::settings();

        return match (self::driver()) {
            'http' => filled($s->get('url')),
            'whatsapp_cloud' => filled($s->get('phone_number_id')) && filled($s->get('token')),
            'gateway', 'log' => true,
            default => false,
        };
    }

    /** Automatic sending is on and this school's plan includes it. */
    public static function enabledFor(?School $school): bool
    {
        return $school !== null && $school->hasFeature('sms') && self::enabled();
    }

    /** What the message is stored as: "sms", or "wa_api" for a WhatsApp API. */
    public static function channel(): string
    {
        return match (self::driver()) {
            'whatsapp_cloud' => 'wa_api',
            'http' => self::settings()->get('kind', 'sms') === 'whatsapp' ? 'wa_api' : 'sms',
            default => 'sms',
        };
    }

    public static function label(?string $channel = null): string
    {
        return match ($channel ?? self::channel()) {
            'sms' => 'SMS',
            'wa_api' => 'WhatsApp API',
            default => 'WhatsApp',
        };
    }

    /** Messages this school sent automatically today (for the daily limit). */
    public static function usedToday(int $schoolId): int
    {
        return Message::withoutGlobalScopes()->where('school_id', $schoolId)->where('channel', '!=', 'whatsapp')
            ->whereIn('status', ['queued', 'sending', 'sent'])->where('created_at', '>=', now()->startOfDay())->count();
    }

    /**
     * Sends a saved message (or puts it in line for the gateway phone) and records the result on it.
     *
     * @return array{ok: bool, queued: bool, error: ?string}
     */
    public function deliver(Message $message): array
    {
        $school = $message->school_id ? School::find($message->school_id) : null;
        $fail = function (string $error) use ($message) {
            $message->update(['status' => 'failed', 'error' => mb_substr($error, 0, 255)]);

            return ['ok' => false, 'queued' => false, 'error' => $error];
        };

        if ($school && ! self::enabledFor($school)) {
            return $fail('Automatic messages are not part of this plan, or not set up.');
        }
        if (! $school && ! self::enabled()) {
            return $fail('Automatic messages are not set up.');
        }
        $limit = self::settings()->school_daily_limit;
        if ($school && $limit > 0 && self::usedToday($school->id) >= $limit) {
            return $fail("Today's limit of {$limit} messages is used up. The rest go tomorrow.");
        }

        $message->forceFill(['channel' => self::channel(), 'error' => null]);
        if (self::driver() === 'gateway') {
            $message->forceFill(['status' => 'queued', 'device_id' => null, 'claimed_at' => null, 'attempts' => 0])->save();

            return ['ok' => true, 'queued' => true, 'error' => null];
        }

        $message->save();
        $r = $this->send($message->phone, $message->body);
        if (! $r['ok']) {
            return $fail($r['error']);
        }
        $message->update(['status' => 'sent', 'sent_at' => now()]);

        return ['ok' => true, 'queued' => false, 'error' => null];
    }

    /**
     * Sends one message straight away through an API (not the gateway phone, which picks messages up).
     *
     * @return array{ok: bool, error: ?string, reply: ?string}
     */
    public function send(string $phone, string $text): array
    {
        $to = Phone::international(Phone::normalize($phone)); // 923001234567
        if (strlen($to) < 11) {
            return ['ok' => false, 'error' => 'Phone number is not complete.', 'reply' => null];
        }

        return match (self::driver()) {
            'log' => $this->toLog($to, $text),
            'http' => $this->viaHttp($to, $text),
            'whatsapp_cloud' => $this->viaCloud($to, $text),
            'gateway' => ['ok' => false, 'error' => 'The gateway phone sends messages from the line, not directly.', 'reply' => null],
            default => ['ok' => false, 'error' => 'Automatic messages are switched off (owner panel → Messaging).', 'reply' => null],
        };
    }

    private function toLog(string $to, string $text): array
    {
        Log::info('Message (test mode, not sent)', ['to' => $to, 'text' => $text]);

        return ['ok' => true, 'error' => null, 'reply' => 'logged'];
    }

    /** Any company's API: {to} {message} {token} {sender} go in the link, the headers and the body. */
    private function viaHttp(string $to, string $text): array
    {
        $s = self::settings();
        $number = match ($s->get('number_format', '92')) {
            '+92' => '+'.$to,
            '0' => '0'.substr($to, 2),
            default => $to,
        };
        $vars = ['{to}' => $number, '{phone}' => $number, '{message}' => $text, '{token}' => (string) $s->get('token', ''), '{sender}' => (string) $s->get('sender', '')];

        $url = strtr((string) $s->get('url', ''), array_map('rawurlencode', $vars));
        if (! preg_match('#^https?://#i', $url)) {
            return ['ok' => false, 'error' => 'The API link is not right.', 'reply' => null];
        }
        $headers = [];
        foreach (preg_split('/\r?\n|\|/', (string) $s->get('headers', '')) as $line) {
            if (str_contains($line, ':')) {
                [$k, $v] = array_map('trim', explode(':', $line, 2));
                $headers[$k] = strtr($v, $vars);
            }
        }
        $data = [];
        if (($payload = trim((string) $s->get('payload', ''))) !== '') {
            $data = json_decode($payload, true);
            if (! is_array($data)) {
                return ['ok' => false, 'error' => 'The message body (JSON) is not right.', 'reply' => null];
            }
            array_walk_recursive($data, function (&$v) use ($vars) {
                $v = is_string($v) ? strtr($v, $vars) : $v;
            });
        }

        try {
            $req = Http::timeout(20)->withHeaders($headers);
            $res = match ($s->get('method', 'POST')) {
                'GET' => $data ? $req->get($url, $data) : $req->get($url), // an empty array would wipe the link's own ?to=..&msg=..
                default => $s->get('body_type', 'json') === 'form' ? $req->asForm()->post($url, $data) : $req->post($url, $data),
            };
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => 'Could not reach the API: '.mb_substr($e->getMessage(), 0, 150), 'reply' => null];
        }

        return $this->judge($res->successful(), $res->status(), $res->body(), (string) $s->get('success_contains', ''));
    }

    /** Meta's WhatsApp Business API. With a template name, the whole message goes in as its {{1}}. */
    private function viaCloud(string $to, string $text): array
    {
        $s = self::settings();
        $template = trim((string) $s->get('template', ''));
        $payload = $template !== ''
            ? ['messaging_product' => 'whatsapp', 'to' => $to, 'type' => 'template', 'template' => [
                'name' => $template, 'language' => ['code' => $s->get('language', 'en')],
                'components' => [['type' => 'body', 'parameters' => [['type' => 'text', 'text' => $text]]]],
            ]]
            : ['messaging_product' => 'whatsapp', 'to' => $to, 'type' => 'text', 'text' => ['body' => $text]];

        try {
            $res = Http::timeout(20)->withToken((string) $s->get('token'))
                ->post('https://graph.facebook.com/'.$s->get('api_version', 'v21.0').'/'.$s->get('phone_number_id').'/messages', $payload);
        } catch (Throwable $e) {
            return ['ok' => false, 'error' => 'Could not reach WhatsApp: '.mb_substr($e->getMessage(), 0, 150), 'reply' => null];
        }

        return $res->successful()
            ? ['ok' => true, 'error' => null, 'reply' => (string) data_get($res->json(), 'messages.0.id')]
            : ['ok' => false, 'error' => 'WhatsApp said: '.mb_substr((string) data_get($res->json(), 'error.message', $res->body()), 0, 200), 'reply' => null];
    }

    private function judge(bool $successful, int $status, string $body, string $needed): array
    {
        $reply = mb_substr(trim($body), 0, 250);
        $ok = $successful && (trim($needed) === '' || str_contains(strtolower($reply), strtolower(trim($needed))));

        return ['ok' => $ok, 'error' => $ok ? null : 'The API said: '.($reply !== '' ? $reply : 'HTTP '.$status), 'reply' => $reply];
    }
}
