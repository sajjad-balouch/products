<?php

namespace App\Livewire\Staff;

use App\Models\AuditLog;
use App\Models\BiometricDevice;
use App\Models\BiometricPunch;
use App\Models\Staff;
use App\Services\BiometricService;
use App\Services\FingerprintMatcher;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

/** Attendance machines: add them, see that they report in, bring in a file from one, and link machine ids to staff. */
#[Title('Attendance machines')]
class Devices extends Component
{
    use WithFileUploads;

    public string $name = '';

    public string $serial = '';

    public ?array $issued = null;

    public string $kioskName = '';

    public $file = null;

    /** By machine user id: the staff id chosen to link to it. */
    public array $links = [];

    public function add(): void
    {
        $this->validate([
            'name' => 'required|string|max:80',
            'serial' => ['required', 'string', 'max:60', 'regex:/^[\w-]+$/', Rule::unique('biometric_devices', 'serial_number')],
        ], ['serial.unique' => 'This serial number is already registered.', 'serial.regex' => 'Use the serial number exactly as the machine shows it.'], ['serial' => 'serial number']);

        $token = Str::random(40);
        $device = BiometricDevice::create(['name' => $this->name, 'serial_number' => $this->serial, 'token_hash' => hash('sha256', $token)]);
        $this->issued = $this->issue($device, $token);
        $this->reset('name', 'serial');
    }

    /** A school computer with a USB fingerprint reader. It is set up by opening the link shown once. */
    public function addKiosk(): void
    {
        $this->validate(['kioskName' => 'required|string|max:80'], [], ['kioskName' => 'name']);

        $token = Str::random(40);
        $device = BiometricDevice::create(['name' => $this->kioskName, 'type' => 'kiosk', 'serial_number' => 'KIOSK-'.Str::upper(Str::random(8)), 'token_hash' => hash('sha256', $token)]);
        $this->issued = $this->issue($device, $token);
        $this->reset('kioskName');
    }

    private function issue(BiometricDevice $device, string $token): array
    {
        return ['serial' => $device->serial_number, 'token' => $token, 'link' => $device->type === 'kiosk' ? route('kiosk.open', [$device->serial_number, 'key' => $token]) : null];
    }

    public function newToken(int $id): void
    {
        $device = BiometricDevice::findOrFail($id);
        $token = Str::random(40);
        $device->update(['token_hash' => hash('sha256', $token)]);
        $this->issued = $this->issue($device, $token);
    }

    public function toggle(int $id): void
    {
        $device = BiometricDevice::findOrFail($id);
        $device->update(['is_active' => ! $device->is_active]);
    }

    public function import(BiometricService $biometric): void
    {
        // Machine logs are plain text whatever their name, so the extension is checked and the contents only ever read as text.
        $this->validate(['file' => 'required|file|extensions:txt,csv,dat|max:5120'], [], ['file' => 'file']);

        $school = Auth::user()->school;
        $punches = $biometric->parse((string) file_get_contents($this->file->getRealPath()));
        $saved = $punches->filter(fn ($p) => $biometric->record($school, $p['id'], $p['at'], null, 'import'))->count();

        AuditLog::record('punches_imported', $school, null, null, "$saved of {$punches->count()} punches from {$this->file->getClientOriginalName()}");
        $this->reset('file');
        $this->dispatch('toast', message: $punches->isEmpty()
            ? 'No punches found. Each line needs the person\'s machine id and a date and time.'
            : "{$punches->count()} punches read, $saved new. Repeats are skipped.", error: $punches->isEmpty());
    }

    public function link(string $deviceUserId, BiometricService $biometric): void
    {
        $staffId = (int) ($this->links[$deviceUserId] ?? 0);
        $staff = Staff::where('status', 'active')->findOrFail($staffId);

        if (Staff::where('biometric_id', $deviceUserId)->where('id', '!=', $staff->id)->exists()) {
            $this->dispatch('toast', message: 'Another staff member already has this machine id.', error: true);

            return;
        }

        // Saving the id links the waiting punches and works out those days.
        $staff->update(['biometric_id' => $deviceUserId]);
        $this->dispatch('toast', message: "Machine id $deviceUserId is now {$staff->name}.");
    }

    public function render()
    {
        $unlinked = BiometricPunch::whereNull('staff_id')->selectRaw('device_user_id, count(*) as total, max(punched_at) as last')
            ->groupBy('device_user_id')->orderBy('device_user_id')->get();

        return view('livewire.staff.devices', [
            'devices' => BiometricDevice::withCount(['punches as today_count' => fn ($q) => $q->whereDate('punched_at', today())])->orderBy('name')->get(),
            'unlinked' => $unlinked,
            'staff' => Staff::where('status', 'active')->orderBy('name')->get(['id', 'name', 'biometric_id']),
            'recent' => BiometricPunch::with(['staff', 'device'])->latest('punched_at')->take(20)->get(),
            'host' => request()->getHost(),
            'port' => request()->getPort(),
            'school' => Auth::user()->school,
            'helperLocal' => FingerprintMatcher::local(),
        ]);
    }
}
