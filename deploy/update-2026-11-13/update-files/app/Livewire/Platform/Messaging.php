<?php

namespace App\Livewire\Platform;

use App\Livewire\Platform\Concerns\OwnerOnly;
use App\Models\GatewayDevice;
use App\Models\Message;
use App\Models\School;
use App\Services\Sms;
use App\Services\SmsGateway;
use Livewire\Attributes\Title;
use Livewire\Component;

/**
 * How schools' automatic messages go out: your own SMS gateway phone, a WhatsApp/SMS company's API,
 * or Meta's WhatsApp Business API. Each school uses it only when its plan has "Automatic SMS / WhatsApp".
 */
#[Title('Messaging')]
class Messaging extends Component
{
    use OwnerOnly;

    /** Settings shown in the form. The token is kept apart and never shown again. */
    public const FIELDS = ['kind', 'url', 'method', 'body_type', 'payload', 'headers', 'sender', 'number_format', 'success_contains', 'phone_number_id', 'api_version', 'template', 'language', 'sms_delay_ms'];

    /** Ready examples to start from; change them to match your company. */
    public const PRESETS = [
        'sms' => ['label' => 'SMS company (Pakistan)', 'kind' => 'sms', 'url' => 'https://SMS-COMPANY.pk/api/send?api_key={token}&sender={sender}&to={to}&message={message}', 'method' => 'GET', 'body_type' => 'json', 'payload' => '', 'number_format' => '92', 'success_contains' => ''],
        'ultramsg' => ['label' => 'UltraMsg (WhatsApp)', 'kind' => 'whatsapp', 'url' => 'https://api.ultramsg.com/INSTANCE_ID/messages/chat', 'method' => 'POST', 'body_type' => 'form', 'payload' => '{"token":"{token}","to":"{to}","body":"{message}"}', 'number_format' => '+92', 'success_contains' => '"sent":"true"'],
        'greenapi' => ['label' => 'GreenAPI (WhatsApp)', 'kind' => 'whatsapp', 'url' => 'https://api.green-api.com/waInstanceINSTANCE_ID/sendMessage/{token}', 'method' => 'POST', 'body_type' => 'json', 'payload' => '{"chatId":"{to}@c.us","message":"{message}"}', 'number_format' => '92', 'success_contains' => 'idMessage'],
    ];

    public string $driver = 'off';

    public array $config = [];

    public string $token = '';

    public bool $hasToken = false;

    public string $dailyLimit = '500';

    public string $testPhone = '';

    public string $testMessage = 'MSK Devs test message. If you got this, messaging works.';

    public ?array $testResult = null;

    // Adding a gateway phone
    public string $deviceName = '';

    public string $deviceSim = '';

    public string $deviceLimit = '300';

    public ?string $newDeviceToken = null;

    public function mount(): void
    {
        $s = Sms::settings();
        $this->driver = array_key_exists($s->driver, Sms::DRIVERS) ? $s->driver : 'off';
        $this->config = array_merge(
            array_fill_keys(self::FIELDS, ''),
            ['kind' => 'sms', 'method' => 'GET', 'body_type' => 'json', 'number_format' => '92', 'api_version' => 'v21.0', 'language' => 'en', 'sms_delay_ms' => '3000'],
            array_map('strval', array_intersect_key($s->config ?? [], array_flip(self::FIELDS)))
        );
        $this->hasToken = filled($s->get('token'));
        $this->dailyLimit = (string) $s->school_daily_limit;
    }

    public function choose(string $driver): void
    {
        if (array_key_exists($driver, Sms::DRIVERS)) {
            $this->driver = $driver;
            $this->resetErrorBag();
        }
    }

    public function applyPreset(string $preset): void
    {
        if (isset(self::PRESETS[$preset])) {
            $this->config = array_merge($this->config, array_diff_key(self::PRESETS[$preset], ['label' => 1]));
        }
    }

    public function save(): void
    {
        $this->validate([
            'driver' => 'required|in:'.implode(',', array_keys(Sms::DRIVERS)),
            'dailyLimit' => 'required|integer|min:0|max:100000',
            'config.kind' => 'in:sms,whatsapp',
            'config.url' => $this->driver === 'http' ? 'required|string|max:500|starts_with:https://,http://' : 'nullable|string|max:500',
            'config.method' => 'in:GET,POST',
            'config.body_type' => 'in:json,form',
            'config.number_format' => 'in:92,+92,0',
            'config.payload' => ['nullable', 'string', 'max:2000', function ($attr, $value, $fail) {
                if (trim((string) $value) !== '' && ! is_array(json_decode($value, true))) {
                    $fail('The body must be JSON, like {"to":"{to}","text":"{message}"}.');
                }
            }],
            'config.headers' => 'nullable|string|max:1000',
            'config.sender' => 'nullable|string|max:30',
            'config.success_contains' => 'nullable|string|max:100',
            'config.phone_number_id' => $this->driver === 'whatsapp_cloud' ? 'required|string|max:64' : 'nullable|string|max:64',
            'config.template' => 'nullable|string|max:100|regex:/^[a-z0-9_]*$/',
            'config.sms_delay_ms' => 'nullable|integer|min:0|max:60000',
            'token' => 'nullable|string|max:500',
        ], [], ['config.url' => 'API link', 'config.phone_number_id' => 'phone number ID', 'config.template' => 'template name']);

        if ($this->driver === 'whatsapp_cloud' && ! $this->hasToken && trim($this->token) === '') {
            $this->addError('token', 'Write the access token from Meta.');

            return;
        }

        $s = Sms::settings();
        $config = array_map(fn ($v) => is_string($v) ? trim($v) : $v, $this->config);
        $config['token'] = trim($this->token) !== '' ? trim($this->token) : (string) $s->get('token', ''); // a new token only when one is written
        $s->update(['driver' => $this->driver, 'config' => $config, 'school_daily_limit' => (int) $this->dailyLimit]);
        Sms::flush();
        $this->hasToken = $config['token'] !== '';
        $this->token = '';
        $this->dispatch('toast', message: 'Messaging saved: '.Sms::DRIVERS[$this->driver][0].'.');
    }

    public function forgetToken(): void
    {
        $s = Sms::settings();
        $s->update(['config' => array_merge($s->config ?? [], ['token' => ''])]);
        Sms::flush();
        $this->hasToken = false;
    }

    public function sendTest(Sms $sms): void
    {
        $this->validate(['testPhone' => 'required|string|min:10|max:20', 'testMessage' => 'required|string|max:500'], [], ['testPhone' => 'phone']);
        Sms::flush();
        $message = Message::withoutGlobalScopes()->create(['school_id' => null, 'phone' => $this->testPhone, 'category' => 'test', 'channel' => 'whatsapp', 'body' => $this->testMessage, 'status' => 'queued']);
        $r = $sms->deliver($message);
        $this->testResult = ['ok' => $r['ok'], 'text' => $r['ok']
            ? ($r['queued'] ? 'In line. The gateway phone sends it within a minute; watch "Latest messages" below.' : 'Sent. Check the phone.')
            : $r['error']];
    }

    public function addDevice(): void
    {
        $this->validate([
            'deviceName' => 'required|string|max:80',
            'deviceSim' => 'nullable|string|max:20',
            'deviceLimit' => 'required|integer|min:0|max:10000',
        ], [], ['deviceName' => 'phone name']);

        [, $this->newDeviceToken] = GatewayDevice::register(trim($this->deviceName), trim($this->deviceSim), (int) $this->deviceLimit);
        $this->reset('deviceName', 'deviceSim');
        $this->deviceLimit = '300';
    }

    public function toggleDevice(int $id): void
    {
        $d = GatewayDevice::findOrFail($id);
        $d->update(['is_active' => ! $d->is_active]);
    }

    public function setDeviceLimit(int $id, $limit): void
    {
        GatewayDevice::whereKey($id)->update(['daily_limit' => max(0, min(10000, (int) $limit))]);
    }

    public function deleteDevice(int $id): void
    {
        $d = GatewayDevice::findOrFail($id);
        // What this phone had taken goes back in line for another phone.
        Message::withoutGlobalScopes()->where('device_id', $d->id)->where('status', 'sending')->update(['status' => 'queued', 'device_id' => null, 'claimed_at' => null]);
        $d->delete();
    }

    public function cancelQueued(): void
    {
        $this->dispatch('toast', message: SmsGateway::cancelQueued().' messages taken out of the line.');
    }

    public function render()
    {
        $today = fn () => Message::withoutGlobalScopes()->where('channel', '!=', 'whatsapp')->where('created_at', '>=', now()->startOfDay());
        $devices = GatewayDevice::orderBy('id')->get();
        $planCount = School::count();

        return view('livewire.platform.messaging', [
            'drivers' => Sms::DRIVERS,
            'presets' => self::PRESETS,
            'ready' => Sms::enabled(),
            'devices' => $devices,
            'online' => $devices->filter->isOnline()->count(),
            'queued' => Message::withoutGlobalScopes()->where('channel', 'sms')->whereIn('status', ['queued', 'sending'])->count(),
            'stats' => $today()->selectRaw('status, COUNT(*) as n')->groupBy('status')->pluck('n', 'status'),
            'month' => Message::withoutGlobalScopes()->where('channel', '!=', 'whatsapp')->where('status', 'sent')->where('created_at', '>=', now()->startOfMonth())->count(),
            'logs' => Message::withoutGlobalScopes()->with('school:id,name')->where('channel', '!=', 'whatsapp')->latest('id')->limit(15)->get(),
            'withFeature' => School::get(['id', 'features_off'])->filter(fn ($s) => $s->hasFeature('sms'))->count(),
            'schoolCount' => $planCount,
            'gatewayUrl' => url('/sms-gateway'),
        ]);
    }
}
