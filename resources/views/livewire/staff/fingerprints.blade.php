<div class="mx-auto max-w-4xl space-y-4">
    <x-page-header title="Staff fingerprints" subtitle="Register fingers here for the fingerprint kiosk. Do this on a computer with the USB reader plugged in.">
        <a class="btn" href="{{ route('staff.devices') }}" wire:navigate>Attendance machines</a>
        <a class="btn btn-primary" href="{{ route('staff.fingerprint-helper') }}" download><x-icon name="download" class="h-4 w-4" /> Download fingerprint helper</a>
    </x-page-header>

    @unless ($ready)
        <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
            Fingerprints are not set up on the server yet. Run <span class="font-mono">php artisan fingerprint:setup</span> (see tools/fingerprint-matcher/README.md).
        </div>
    @endunless
    @if ($helper['local'])
        <div class="flex flex-col gap-3 rounded-xl border border-brand-200 bg-brand-50 p-4 text-sm text-slate-700 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="font-semibold text-slate-900">Each computer with a fingerprint reader needs the fingerprint helper (once).</p>
                <ol class="mt-1 list-decimal space-y-0.5 ps-5">
                    <li>Install "HID DigitalPersona Lite Client" and plug in the reader.</li>
                    <li>Download the helper, unzip it to a folder that stays, for example <span class="font-mono">C:\FingerprintHelper</span>.</li>
                    <li>Double-click <span class="font-mono">install-startup.bat</span>. It then starts by itself every day.</li>
                </ol>
            </div>
            <a class="btn btn-primary shrink-0" href="{{ route('staff.fingerprint-helper') }}" download><x-icon name="download" class="h-4 w-4" /> Download helper</a>
        </div>
    @endif

    <div class="grid gap-4 lg:grid-cols-2">
        <div class="card-pad space-y-3"
             x-data="{
                 mode: 'enrol', status: 'Looking for the reader...', tone: 'wait',
                 helper: window.fingerHelper(@js($helper)),
                 async press(png) {
                     if (! @js($helper['local'])) return this.mode === 'enrol' ? $wire.enrol(png) : $wire.check(png);
                     try {
                         if (this.mode === 'enrol') return $wire.enrolTemplate(await this.helper.extract(png));
                         const result = await this.helper.identify(png, await $wire.candidates());
                         return $wire.showCheck(result.id ? Number(result.id) : null, result.score);
                     } catch (e) { this.status = e.message; this.tone = 'error'; }
                 },
             }"
             x-init="window.fingerReader(png => press(png), (state, message) => { status = message; tone = state })">
            <h2 class="font-semibold">Register a finger</h2>
            <x-field label="Staff member" name="staffId">
                <select id="staffId" class="input" wire:model.live="staffId">
                    <option value="">Choose</option>
                    @foreach ($staff as $s)<option value="{{ $s->id }}">{{ $s->name }}{{ $s->designation ? ' · '.$s->designation : '' }}</option>@endforeach
                </select>
            </x-field>
            <x-field label="Finger" name="finger">
                <select id="finger" class="input" wire:model.live="finger">@foreach ($fingers as $k => $label)<option value="{{ $k }}">{{ $label }}</option>@endforeach</select>
            </x-field>
            <div class="flex gap-2 text-sm">
                <label class="flex items-center gap-1.5"><input type="radio" value="enrol" x-model="mode"> Register presses</label>
                <label class="flex items-center gap-1.5"><input type="radio" value="check" x-model="mode"> Only check who it is</label>
            </div>
            <div class="rounded-xl border-2 border-dashed p-5 text-center" :class="tone === 'error' ? 'border-red-300 bg-red-50 text-red-800' : 'border-slate-300'">
                <div class="text-4xl" aria-hidden="true">☝</div>
                <div class="mt-1 text-sm" x-text="status" role="status"></div>
                <div class="mt-2 text-sm font-semibold" x-show="mode === 'enrol'">Presses saved: {{ $presses }} of 3</div>
                @if ($test)
                    <div class="mt-2 text-sm font-semibold" x-show="mode === 'check'">{{ $test['name'] ? 'Recognised as '.$test['name'] : 'Not recognised' }} (score {{ $test['score'] }})</div>
                @endif
            </div>
            <p class="text-xs text-slate-500">Press the same finger three times, lifting it each time. A second finger is a good spare for cuts or dry skin.</p>
        </div>

        <div class="card overflow-x-auto">
            <table class="tbl">
                <thead><tr><th>Staff</th><th>Fingers registered</th></tr></thead>
                <tbody>
                @foreach ($staff as $s)
                    <tr wire:key="fp-{{ $s->id }}">
                        <td class="font-medium">{{ $s->name }}</td>
                        <td>
                            <div class="flex flex-wrap gap-1">
                                @forelse ($enrolled->get($s->id, collect()) as $row)
                                    <span class="badge badge-green">{{ $fingers[$row->finger] ?? $row->finger }} ({{ $row->presses }})
                                        <button class="ms-1" wire:click="forget({{ $s->id }}, '{{ $row->finger }}')" wire:confirm="Remove this finger for {{ $s->name }}?" aria-label="Remove {{ $fingers[$row->finger] ?? $row->finger }} for {{ $s->name }}">✕</button>
                                    </span>
                                @empty
                                    <span class="text-xs text-slate-400">None</span>
                                @endforelse
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @assets
        @include('fingerprint-reader')
    @endassets
</div>
