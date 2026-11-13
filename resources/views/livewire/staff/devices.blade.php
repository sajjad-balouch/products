<div class="mx-auto max-w-5xl space-y-4">
    <x-page-header title="Attendance machines" subtitle="Fingerprint or face machines mark staff attendance by themselves. The first punch is the arrival time and the last is when they left.">
        <a class="btn" href="{{ route('staff.attendance') }}" wire:navigate>Staff attendance</a>
    </x-page-header>

    @if ($issued)
        <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
            @if ($issued['link'])
                <p class="font-semibold">Kiosk link, shown only once.</p>
                <p class="mt-1">Open this link once on the kiosk computer. It stays set up after that, even after a restart. A new link stops the old one.</p>
                <p class="mt-2 rounded bg-white p-2 font-mono text-xs break-all select-all">{{ $issued['link'] }}</p>
            @else
                <p class="font-semibold">Machine {{ $issued['serial'] }} is registered. This token is shown only once.</p>
                <p class="mt-1">Only needed for software that sends punches by API: <span class="font-mono break-all">{{ $issued['token'] }}</span></p>
            @endif
        </div>
    @endif

    @if ($school->strict_attendance)
        <div class="rounded-xl border border-brand-500 bg-brand-50 p-3 text-sm">
            <strong>Strict attendance is on.</strong> Times from machines cannot be changed on the attendance sheet{{ $school->check_in_networks ? ', and machines are only accepted from the school network' : '' }}. Change this in Settings.
        </div>
    @endif

    <div class="card overflow-x-auto">
        <table class="tbl">
            <thead><tr><th>Machine</th><th>Serial number</th><th>Last heard from</th><th>Punches today</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse ($devices as $device)
                <tr wire:key="dev-{{ $device->id }}">
                    <td class="font-medium">{{ $device->name }}@if ($device->type === 'kiosk')<span class="badge badge-blue ms-1">Fingerprint kiosk</span>@endif</td>
                    <td class="font-mono text-xs">{{ $device->serial_number }}</td>
                    <td class="text-xs">{{ $device->last_seen_at ? $device->last_seen_at->diffForHumans().' from '.$device->last_ip : 'Not yet' }}</td>
                    <td>{{ $device->today_count }}</td>
                    <td><span @class(['badge', 'badge-green' => $device->is_active, 'badge-slate' => ! $device->is_active])>{{ $device->is_active ? 'On' : 'Off' }}</span></td>
                    <td class="text-end whitespace-nowrap">
                        <button class="btn btn-sm" wire:click="newToken({{ $device->id }})" wire:confirm="{{ $device->type === 'kiosk' ? 'Make a new kiosk link? The kiosk computer stops working until the new link is opened on it.' : 'Make a new API token? The old one stops working.' }}">{{ $device->type === 'kiosk' ? 'New link' : 'New token' }}</button>
                        <button class="btn btn-sm" wire:click="toggle({{ $device->id }})">{{ $device->is_active ? 'Turn off' : 'Turn on' }}</button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="p-6 text-center text-slate-500">No machine added yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        <form wire:submit="add" class="card-pad space-y-3">
            <h2 class="font-semibold">Add a machine</h2>
            <x-field label="Name" name="name" hint="For example: Main gate"><input id="name" class="input" wire:model="name"></x-field>
            <x-field label="Serial number" name="serial" hint="On the machine: Menu, System info, Device info. Also on the sticker at the back."><input id="serial" class="input font-mono" wire:model="serial"></x-field>
            <div class="flex justify-end"><button class="btn btn-primary" wire:loading.attr="disabled">Add machine</button></div>
        </form>

        <div class="card-pad space-y-2 text-sm">
            <h2 class="font-semibold">Connect the machine</h2>
            <p><strong>ZKTeco and similar machines</strong> (they call it ADMS, Cloud server or Push): on the machine open Menu, Comm., Cloud Server Setting and enter:</p>
            <dl class="grid grid-cols-[8rem_1fr] gap-x-3 gap-y-1 rounded-lg bg-slate-50 p-3 font-mono text-xs">
                <dt class="text-slate-500">Server address</dt><dd>{{ $host }}</dd>
                <dt class="text-slate-500">Server port</dt><dd>{{ $port }}</dd>
                <dt class="text-slate-500">Domain name</dt><dd>On, if you use the address above</dd>
                <dt class="text-slate-500">Proxy</dt><dd>Off</dd>
            </dl>
            <p>Then enrol each person on the machine and put their machine user number in their staff record as "Machine id". Punches show up here within a minute.</p>
            <p class="text-slate-600"><strong>Other machines or software</strong> can send punches as JSON to <span class="font-mono text-xs break-all">{{ route('biometric.api') }}</span> with the header <span class="font-mono text-xs">X-Device-Token</span>, like <span class="font-mono text-xs">{"punches":[{"user_id":"15","time":"2026-11-03 08:01:00"}]}</span>.</p>
        </div>
    </div>

    <div class="card-pad space-y-3">
        <h2 class="font-semibold">USB fingerprint reader (U.are.U 5100 and similar)</h2>
        <p class="text-sm text-slate-600">A reader plugged into a school computer works as a kiosk: the computer shows the attendance page, staff press a finger, and the system marks them in or out.</p>
        <ol class="list-decimal space-y-1 ps-5 text-sm text-slate-600">
            <li>On that computer install <strong>HID DigitalPersona Lite Client</strong> from HID's website, then plug in the reader. Any HID DigitalPersona reader (U.are.U 4500, 5100, 5300 and others) is found by itself.</li>
            @if ($helperLocal)
                <li>This website uses the helper on each school computer. <a class="text-brand-600 underline" href="{{ route('staff.fingerprint-helper') }}">Download the fingerprint helper</a> on that computer, unzip it to a folder that stays (for example C:\FingerprintHelper) and double-click <span class="font-mono">install-startup.bat</span>.</li>
            @endif
            <li>Add the kiosk below and open the link it shows, once, on that computer. Keep the page open full screen (press F11).</li>
            <li>Register each person's fingers on <a class="text-brand-600 underline" href="{{ route('staff.fingerprints') }}" wire:navigate>Staff fingerprints</a>, on a computer with the reader.</li>
        </ol>
        <form wire:submit="addKiosk" class="flex flex-wrap items-end gap-2">
            <x-field label="Kiosk name" name="kioskName" hint="For example: Staff room"><input id="kioskName" class="input" wire:model="kioskName"></x-field>
            <button class="btn btn-primary" wire:loading.attr="disabled">Add kiosk</button>
        </form>
    </div>

    <form wire:submit="import" class="card-pad space-y-3">
        <h2 class="font-semibold">Bring in a file from a machine</h2>
        <p class="text-sm text-slate-600">For a machine that is not online: save its attendance log to a USB stick (often called attlog.dat) or export it as CSV, then upload it here. Punches already received are skipped.</p>
        <div class="flex flex-wrap items-center gap-3">
            <input type="file" accept=".dat,.txt,.csv" wire:model="file" aria-label="Punch file">
            <button class="btn" wire:loading.attr="disabled" wire:target="file,import">Upload</button>
        </div>
        @error('file')<p class="err">{{ $message }}</p>@enderror
    </form>

    @if ($unlinked->isNotEmpty())
        <div class="card overflow-x-auto">
            <div class="border-b border-slate-200 px-4 py-3 text-sm"><span class="font-semibold">Machine ids not linked to anyone.</span> Their punches are kept and counted as soon as you link them.</div>
            <table class="tbl">
                <thead><tr><th>Machine id</th><th>Punches</th><th>Last punch</th><th>Link to</th></tr></thead>
                <tbody>
                @foreach ($unlinked as $row)
                    <tr wire:key="un-{{ $row->device_user_id }}">
                        <td class="font-mono">{{ $row->device_user_id }}</td>
                        <td>{{ $row->total }}</td>
                        <td class="text-xs">{{ \Illuminate\Support\Carbon::parse($row->last)->format('j M Y, H:i') }}</td>
                        <td class="flex gap-2">
                            <select class="input w-56" aria-label="Staff for machine id {{ $row->device_user_id }}" wire:model="links.{{ $row->device_user_id }}">
                                <option value="">Choose staff</option>
                                @foreach ($staff->whereNull('biometric_id') as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                            </select>
                            <button class="btn btn-sm" wire:click="link('{{ $row->device_user_id }}')">Link</button>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="card overflow-x-auto">
        <div class="border-b border-slate-200 px-4 py-3 text-sm font-semibold">Latest punches</div>
        <table class="tbl">
            <thead><tr><th>Time</th><th>Who</th><th>Machine id</th><th>From</th></tr></thead>
            <tbody>
            @forelse ($recent as $punch)
                <tr wire:key="pu-{{ $punch->id }}">
                    <td>{{ $punch->punched_at->format('j M, H:i:s') }}</td>
                    <td>{{ $punch->staff?->name ?? 'Not linked' }}</td>
                    <td class="font-mono text-xs">{{ $punch->device_user_id }}</td>
                    <td class="text-xs">{{ $punch->device?->name ?? ['import' => 'File upload', 'api' => 'API', 'push' => 'Machine'][$punch->source] }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="p-6 text-center text-slate-500">No punches yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
