<div class="space-y-5">
    <x-page-header title="Messaging" subtitle="How schools and businesses send automatic SMS and WhatsApp: fee and installment reminders, alerts and notices. A school uses it only when its plan has &quot;Automatic SMS / WhatsApp&quot;." />

    {{-- At a glance --}}
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach ([
            ['Method', $drivers[$driver][0], $drivers[$driver][2], $ready ? 'green' : 'slate', $ready ? 'Ready' : 'Not set up'],
            ['Sent today', number_format($stats['sent'] ?? 0), 'check-circle', 'brand', ($stats['failed'] ?? 0).' failed today'],
            ['Waiting in line', number_format($queued), 'clock', $queued ? 'amber' : 'slate', $driver === 'gateway' ? $online.' of '.$devices->count().' phones online' : 'For the gateway phone'],
            ['Schools with the feature', $withFeature.' / '.$schoolCount, 'school', 'violet', number_format($month).' sent this month'],
        ] as [$label, $value, $icon, $tone, $note])
            <div class="card-pad flex items-start gap-3">
                <div class="icon-chip tone-{{ $tone }}"><x-icon :name="$icon" /></div>
                <div class="min-w-0">
                    <div class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ $label }}</div>
                    <div class="truncate text-lg font-semibold text-slate-900">{{ $value }}</div>
                    <div class="text-xs text-slate-500">{{ $note }}</div>
                </div>
            </div>
        @endforeach
    </div>

    <form wire:submit="save" class="card-pad space-y-5">
        <div>
            <h2 class="panel-title"><x-icon name="settings" class="h-5 w-5 text-brand-600" /> How messages go out · Paigham kaise jayen</h2>
            <div class="mt-3 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($drivers as $key => [$title, $about, $icon])
                    <button type="button" wire:click="choose('{{ $key }}')" wire:key="drv-{{ $key }}"
                            @class(['relative rounded-xl p-4 text-left ring-1 transition', 'bg-brand-50 ring-2 ring-brand-500' => $driver === $key, 'bg-white ring-slate-200 hover:ring-brand-300' => $driver !== $key])>
                        @if ($driver === $key)<x-icon name="check-circle" class="absolute right-3 top-3 h-5 w-5 text-brand-600" />@endif
                        <div @class(['icon-chip mb-2', 'tone-brand' => $driver === $key, 'tone-slate' => $driver !== $key])><x-icon :name="$icon" /></div>
                        <div class="font-semibold text-slate-900">{{ $title }}</div>
                        <p class="mt-1 text-xs text-slate-500">{{ $about }}</p>
                    </button>
                @endforeach
            </div>
        </div>

        @if ($driver === 'http')
            <div class="space-y-4 rounded-xl bg-slate-50 p-4 ring-1 ring-slate-200">
                <div class="flex flex-wrap items-center gap-2 text-sm">
                    <span class="font-medium text-slate-700">Start from:</span>
                    @foreach ($presets as $key => $p)
                        <button type="button" class="btn btn-sm" wire:click="applyPreset('{{ $key }}')">{{ $p['label'] }}</button>
                    @endforeach
                </div>
                <div class="grid gap-4 sm:grid-cols-3">
                    <x-field label="It sends" name="config.kind" hint="Shown to schools as the message type.">
                        <select id="config.kind" class="input" wire:model="config.kind"><option value="sms">SMS</option><option value="whatsapp">WhatsApp</option></select>
                    </x-field>
                    <x-field label="Method" name="config.method" hint="Written in your company's API guide.">
                        <select id="config.method" class="input" wire:model.live="config.method"><option>GET</option><option>POST</option></select>
                    </x-field>
                    @if ($config['method'] === 'POST')
                        <x-field label="Body type" name="config.body_type" hint="JSON, or a form (x-www-form-urlencoded).">
                            <select id="config.body_type" class="input" wire:model="config.body_type"><option value="json">JSON</option><option value="form">Form</option></select>
                        </x-field>
                    @endif
                </div>
                <x-field label="API link" name="config.url" hint="The send link from your company. {to} {message} {token} {sender} are filled in for each message.">
                    <input id="config.url" class="input font-mono text-sm" wire:model="config.url" placeholder="https://sms-company.pk/api/send?api_key={token}&to={to}&message={message}">
                </x-field>
                <x-field label="Body (JSON)" name="config.payload" hint='Leave empty when everything is in the link. Example: {"to":"{to}","text":"{message}"}'>
                    <textarea id="config.payload" rows="3" class="input font-mono text-sm" wire:model="config.payload"></textarea>
                </x-field>
                <div class="grid gap-4 sm:grid-cols-2">
                    <x-field label="API key / token" name="token" :hint="$hasToken ? 'A token is saved and hidden. Write a new one only to change it.' : 'Goes where you write {token}.'">
                        <div class="flex gap-2">
                            <input id="token" type="password" class="input font-mono" wire:model="token" autocomplete="new-password" placeholder="{{ $hasToken ? '•••••••• saved' : '' }}">
                            @if ($hasToken)<button type="button" class="btn btn-sm" wire:click="forgetToken" wire:confirm="Remove the saved token?">Remove</button>@endif
                        </div>
                    </x-field>
                    <x-field label="Sender name / mask" name="config.sender" hint="Goes where you write {sender}, e.g. MSKDEVS."><input id="config.sender" class="input" wire:model="config.sender"></x-field>
                    <x-field label="Phone number style" name="config.number_format" hint="How your company wants {to}.">
                        <select id="config.number_format" class="input" wire:model="config.number_format"><option value="92">923001234567</option><option value="+92">+923001234567</option><option value="0">03001234567</option></select>
                    </x-field>
                    <x-field label="Sent when the reply contains" name="config.success_contains" hint="Optional, e.g. success or &quot;sent&quot;:&quot;true&quot;. Empty = any OK reply.">
                        <input id="config.success_contains" class="input font-mono" wire:model="config.success_contains">
                    </x-field>
                </div>
                <x-field label="Extra headers" name="config.headers" hint="Optional, one per line: Authorization: Bearer {token}">
                    <textarea id="config.headers" rows="2" class="input font-mono text-sm" wire:model="config.headers"></textarea>
                </x-field>
            </div>
        @elseif ($driver === 'whatsapp_cloud')
            <div class="grid gap-4 rounded-xl bg-slate-50 p-4 ring-1 ring-slate-200 sm:grid-cols-2">
                <x-field label="Phone number ID" name="config.phone_number_id" hint="Meta Business → WhatsApp → API setup."><input id="config.phone_number_id" class="input font-mono" wire:model="config.phone_number_id"></x-field>
                <x-field label="Access token" name="token" :hint="$hasToken ? 'Saved and hidden. Write a new one only to change it.' : 'A permanent token from a system user.'">
                    <input id="token" type="password" class="input font-mono" wire:model="token" autocomplete="new-password" placeholder="{{ $hasToken ? '•••••••• saved' : '' }}">
                </x-field>
                <x-field label="Template name" name="config.template" hint="An approved template with one {{1}} in its text. The whole message goes there. Empty = plain text (works only within 24 hours of the parent's last message).">
                    <input id="config.template" class="input font-mono" wire:model="config.template" placeholder="school_notice">
                </x-field>
                <div class="grid grid-cols-2 gap-4">
                    <x-field label="Template language" name="config.language" hint="en, ur ..."><input id="config.language" class="input" wire:model="config.language"></x-field>
                    <x-field label="API version" name="config.api_version" hint="Leave as it is."><input id="config.api_version" class="input" wire:model="config.api_version"></x-field>
                </div>
            </div>
        @elseif ($driver === 'gateway')
            <div class="space-y-3 rounded-xl bg-slate-50 p-4 ring-1 ring-slate-200">
                <div class="grid gap-3 sm:grid-cols-3">
                    <div class="flex gap-3"><span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-brand-600 text-sm font-bold text-white">1</span><p class="text-sm text-slate-600"><a class="font-semibold text-brand-700 underline" href="{{ route('platform.gateway-app') }}">Download the gateway app</a> and install it on an Android phone with a SIM and an SMS package.</p></div>
                    <div class="flex gap-3"><span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-brand-600 text-sm font-bold text-white">2</span><p class="text-sm text-slate-600">Add the phone below. Copy the token it shows (shown only once).</p></div>
                    <div class="flex gap-3"><span class="grid h-7 w-7 shrink-0 place-items-center rounded-full bg-brand-600 text-sm font-bold text-white">3</span><p class="text-sm text-slate-600">In the app, write the server link and token, press Start, and allow SMS.</p></div>
                </div>
                <div class="flex flex-wrap items-center gap-2 rounded-lg bg-white p-2.5 text-sm ring-1 ring-slate-200" x-data="{ copied: false }">
                    <span class="text-slate-500">Server link:</span>
                    <code class="font-mono font-semibold text-slate-900">{{ $gatewayUrl }}</code>
                    <button type="button" class="btn btn-sm ms-auto" @click="navigator.clipboard.writeText(@js($gatewayUrl)); copied = true; setTimeout(() => copied = false, 1500)"><span x-text="copied ? 'Copied' : 'Copy'"></span></button>
                </div>
                <x-field label="Pause between two SMS (milliseconds)" name="config.sms_delay_ms" hint="3000 = 3 seconds. Many SMS very fast can get a SIM blocked." class="max-w-xs">
                    <input id="config.sms_delay_ms" type="number" min="0" class="input" wire:model="config.sms_delay_ms">
                </x-field>
            </div>
        @else
            <p class="rounded-xl bg-slate-50 p-4 text-sm text-slate-600 ring-1 ring-slate-200">Nothing is sent automatically. Reminders and alerts wait in each school's WhatsApp outbox, and staff send them with one tap.</p>
        @endif

        <div class="flex flex-wrap items-end justify-between gap-4 border-t border-slate-100 pt-4">
            <x-field label="Most messages per school per day" name="dailyLimit" hint="0 = no limit. Protects your SMS balance." class="w-60">
                <input id="dailyLimit" type="number" min="0" class="input" wire:model="dailyLimit">
            </x-field>
            <button class="btn btn-primary" wire:loading.attr="disabled"><x-icon name="check-circle" class="h-4 w-4" /> Save messaging</button>
        </div>
    </form>

    @if ($driver === 'gateway')
        <section class="card-pad space-y-4">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="panel-title"><x-icon name="phone" class="h-5 w-5 text-brand-600" /> Gateway phones</h2>
                @if ($queued)<button type="button" class="btn btn-sm text-red-700" wire:click="cancelQueued" wire:confirm="Take all {{ $queued }} waiting messages out of the line? They will not be sent.">Stop the waiting messages</button>@endif
            </div>

            @if ($newDeviceToken)
                @php($setupCode = 'MSK1|'.$gatewayUrl.'|'.$newDeviceToken)
                <div class="rounded-xl bg-emerald-50 p-4 text-sm text-emerald-900 ring-1 ring-emerald-200" x-data="{ copied: '' }">
                    <div class="flex items-center gap-2 font-semibold"><x-icon name="check-circle" class="h-5 w-5" /> Phone added. Set it up now: this is shown only once.</div>
                    <p class="mt-1">Easiest: copy the setup code, send it to the phone (WhatsApp to yourself), copy it there, and press <strong>Setup code paste karein</strong> in the app.</p>
                    <div class="mt-3 flex flex-wrap items-center gap-2">
                        <button type="button" class="btn btn-primary btn-sm" @click="navigator.clipboard.writeText(@js($setupCode)); copied = 'code'"><x-icon name="key" class="h-4 w-4" /> <span x-text="copied === 'code' ? 'Setup code copied' : 'Copy setup code'"></span></button>
                        <button type="button" class="btn btn-sm" @click="navigator.clipboard.writeText(@js($newDeviceToken)); copied = 'token'"><span x-text="copied === 'token' ? 'Token copied' : 'Copy token only'"></span></button>
                    </div>
                    <code class="mt-2 block break-all rounded bg-white px-2 py-1 font-mono text-xs">{{ $newDeviceToken }}</code>
                </div>
            @endif

            <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($devices as $d)
                    @php($status = $d->last_status ?? [])
                    <div class="rounded-xl p-4 ring-1 ring-slate-200" wire:key="dev-{{ $d->id }}">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-3">
                                <div @class(['icon-chip', 'tone-green' => $d->is_active && $d->isOnline(), 'tone-amber' => $d->is_active && ! $d->isOnline(), 'tone-slate' => ! $d->is_active])><x-icon name="phone" /></div>
                                <div>
                                    <div class="font-semibold text-slate-900">{{ $d->name }}</div>
                                    <div class="text-xs text-slate-500">{{ $d->sim_number ?: 'No SIM number' }}</div>
                                </div>
                            </div>
                            <span @class(['rounded-full px-2 py-0.5 text-xs font-semibold', 'bg-emerald-100 text-emerald-800' => $d->is_active && $d->isOnline(), 'bg-amber-100 text-amber-800' => $d->is_active && ! $d->isOnline(), 'bg-slate-100 text-slate-600' => ! $d->is_active])>
                                {{ ! $d->is_active ? 'Paused' : ($d->isOnline() ? 'Online' : 'Offline') }}
                            </span>
                        </div>
                        <dl class="mt-3 grid grid-cols-3 gap-2 text-center text-xs">
                            <div class="rounded-lg bg-slate-50 p-2"><dt class="text-slate-500">Today</dt><dd class="font-semibold">{{ $d->sentToday() }} / {{ $d->daily_limit ?: '∞' }}</dd></div>
                            <div class="rounded-lg bg-slate-50 p-2"><dt class="text-slate-500">Battery</dt><dd class="font-semibold">{{ isset($status['battery']) ? $status['battery'].'%' : '—' }}</dd></div>
                            <div class="rounded-lg bg-slate-50 p-2"><dt class="text-slate-500">Last seen</dt><dd class="font-semibold">{{ $d->last_seen_at?->diffForHumans(short: true) ?? 'never' }}</dd></div>
                        </dl>
                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            <label class="flex items-center gap-1 text-xs text-slate-600">Daily limit
                                <input type="number" min="0" class="input w-20 py-1 text-xs" value="{{ $d->daily_limit }}" wire:change="setDeviceLimit({{ $d->id }}, $event.target.value)">
                            </label>
                            <button type="button" class="btn btn-sm ms-auto" wire:click="toggleDevice({{ $d->id }})">{{ $d->is_active ? 'Pause' : 'Resume' }}</button>
                            <button type="button" class="btn btn-sm text-red-700" wire:click="deleteDevice({{ $d->id }})" wire:confirm="Remove {{ $d->name }}? Its token stops working.">Remove</button>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500 md:col-span-2 xl:col-span-3">No phone yet. Add one below.</p>
                @endforelse
            </div>

            <form wire:submit="addDevice" class="grid gap-3 rounded-xl border border-dashed border-slate-300 p-4 sm:grid-cols-4">
                <x-field label="Phone name" name="deviceName" hint="e.g. Office Samsung"><input id="deviceName" class="input" wire:model="deviceName"></x-field>
                <x-field label="SIM number" name="deviceSim" hint="Optional, for your record."><input id="deviceSim" class="input" wire:model="deviceSim" placeholder="0300 1234567"></x-field>
                <x-field label="SMS per day" name="deviceLimit" hint="Your SIM package limit."><input id="deviceLimit" type="number" min="0" class="input" wire:model="deviceLimit"></x-field>
                <div class="sm:pt-6"><button class="btn btn-primary w-full"><x-icon name="plus" class="h-4 w-4" /> Add phone</button></div>
            </form>
        </section>
    @endif

    <div class="grid gap-5 lg:grid-cols-5">
        <section class="card-pad space-y-3 lg:col-span-2">
            <h2 class="panel-title"><x-icon name="message" class="h-5 w-5 text-brand-600" /> Send a test</h2>
            <x-field label="Phone" name="testPhone" hint="Your own number."><input id="testPhone" class="input" wire:model="testPhone" placeholder="0300 1234567"></x-field>
            <x-field label="Message" name="testMessage"><textarea id="testMessage" rows="3" class="input" wire:model="testMessage"></textarea></x-field>
            <button type="button" class="btn btn-primary" wire:click="sendTest" @disabled(! $ready)><x-icon name="message" class="h-4 w-4" /> Send test</button>
            @unless ($ready)<p class="text-xs text-slate-500">Choose a method and save it first.</p>@endunless
            @if ($testResult)
                <p @class(['rounded-lg p-2.5 text-sm ring-1', 'bg-emerald-50 text-emerald-900 ring-emerald-200' => $testResult['ok'], 'bg-red-50 text-red-800 ring-red-200' => ! $testResult['ok']])>{{ $testResult['text'] }}</p>
            @endif
        </section>

        <section class="card overflow-hidden lg:col-span-3">
            <h2 class="panel-title border-b border-slate-100 p-4"><x-icon name="list" class="h-5 w-5 text-brand-600" /> Latest messages</h2>
            <ul class="max-h-96 divide-y divide-slate-100 overflow-y-auto text-sm">
                @forelse ($logs as $m)
                    <li class="flex items-start gap-3 p-3" wire:key="log-{{ $m->id }}">
                        <span @class(['mt-0.5 shrink-0 rounded-full px-2 py-0.5 text-xs font-semibold', 'bg-emerald-100 text-emerald-800' => $m->status === 'sent', 'bg-red-100 text-red-800' => $m->status === 'failed', 'bg-amber-100 text-amber-800' => in_array($m->status, ['queued', 'sending'])])>{{ $m->status === 'queued' ? 'in line' : $m->status }}</span>
                        <div class="min-w-0 flex-1">
                            <div class="text-xs text-slate-500">{{ $m->school?->name ?? 'Owner test' }} · {{ $m->phone }} · {{ \App\Services\Sms::label($m->channel) }} · {{ $m->created_at->diffForHumans(short: true) }}</div>
                            <p class="truncate">{{ $m->body }}</p>
                            @if ($m->error)<p class="text-xs text-red-700">{{ $m->error }}</p>@endif
                        </div>
                    </li>
                @empty
                    <li class="p-6 text-center text-slate-500">No automatic messages yet.</li>
                @endforelse
            </ul>
        </section>
    </div>
</div>
