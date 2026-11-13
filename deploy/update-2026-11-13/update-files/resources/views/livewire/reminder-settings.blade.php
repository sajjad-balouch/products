<section class="card-pad space-y-4" id="reminders">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div class="flex items-center gap-3">
            <div class="icon-chip tone-brand"><x-icon name="bell" /></div>
            <div>
                <h2 class="text-lg font-semibold text-slate-900">Automatic reminders · Khud-ba-khud yaad-dehani</h2>
                <p class="text-sm text-slate-500">{{ $business ? 'An SMS to customers before each installment is due, and while it is late.' : 'An SMS to parents before the fee due date, and while it is late.' }}</p>
            </div>
        </div>
        <label class="flex items-center gap-2 rounded-full px-3 py-1.5 text-sm font-semibold ring-1 {{ $s['enabled'] ? 'bg-emerald-50 text-emerald-800 ring-emerald-200' : 'bg-slate-50 text-slate-600 ring-slate-200' }}">
            <input type="checkbox" wire:model.live="s.enabled"> {{ $s['enabled'] ? 'On · Chalu' : 'Off · Band' }}
        </label>
    </div>

    <div @class(['rounded-lg p-2.5 text-sm ring-1', 'bg-emerald-50 text-emerald-900 ring-emerald-200' => $smsOn, 'bg-amber-50 text-amber-900 ring-amber-200' => ! $smsOn])>
        @if ($smsOn && $smsDriver === 'log')
            Messages are in <strong>test mode</strong>: written to the log, not sent.
        @elseif ($smsOn)
            <strong>{{ $channelLabel }}</strong> is connected. Reminders go out by themselves every morning at {{ config('services.sms.reminder_time') }}.
        @elseif (! $inPlan)
            Automatic SMS / WhatsApp is not in your plan, so reminders wait in the <a class="underline" href="{{ route('messages.index') }}" wire:navigate>outbox</a> to send with one tap. Ask MSK Devs to add it to your plan.
        @else
            Automatic sending is not connected yet, so reminders wait in the <a class="underline" href="{{ route('messages.index') }}" wire:navigate>outbox</a> to send with one tap.
        @endif
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <div>
            <div class="label">Days before the due date · Kitne din pehle</div>
            <div class="flex flex-wrap gap-2">
                @foreach (['7', '5', '3', '2', '1'] as $d)
                    <label class="flex items-center gap-1.5 rounded-lg px-2.5 py-1.5 text-sm ring-1 ring-slate-200"><input type="checkbox" value="{{ $d }}" wire:model.live="s.days_before"> {{ $d }} {{ $d === '1' ? 'day' : 'days' }}</label>
                @endforeach
            </div>
            <p class="mt-1 text-xs text-slate-500">Tick one or more. Nothing ticked = no early reminder.</p>
        </div>
        <div>
            <label class="label flex items-center gap-2"><input type="checkbox" wire:model.live="s.on_due"> On the due date too · Usi din bhi</label>
            <p class="text-xs text-slate-500">One reminder on the last day itself.</p>
        </div>
        <div class="grid grid-cols-2 gap-2">
            <x-field label="While late, every" name="s.late_every">
                <select id="s.late_every" class="input" wire:model.live="s.late_every">
                    <option value="0">Never</option>@foreach ([1, 2, 3, 5, 7] as $d)<option value="{{ $d }}">{{ $d }} {{ $d === 1 ? 'day' : 'days' }}</option>@endforeach
                </select>
            </x-field>
            <x-field label="At most" name="s.late_times">
                <select id="s.late_times" class="input" wire:model.live="s.late_times">@foreach (range(1, 10) as $n)<option value="{{ $n }}">{{ $n }} {{ $n === 1 ? 'time' : 'times' }}</option>@endforeach</select>
            </x-field>
            <p class="col-span-2 -mt-1 text-xs text-slate-500">Late reminders stop when paid, or after this many.</p>
        </div>
    </div>

    <div class="grid gap-4 lg:grid-cols-2">
        @foreach ($keys as $i => $key)
            <div>
                <label class="label" for="s.{{ $key }}">{{ $i === 0 ? 'Message before / on the due date' : 'Message when late' }}</label>
                <textarea id="s.{{ $key }}" rows="4" class="input" wire:model.live.debounce.500ms="s.{{ $key }}"></textarea>
                @error('s.'.$key)<p class="err">{{ $message }}</p>@enderror
                <p class="mt-1 text-xs text-slate-500">{{ mb_strlen($s[$key] ?? '') }} letters. Words in { } are filled in for each person:
                    @foreach ($placeholders as $tag => $what)<code class="rounded bg-slate-100 px-1">{{ $tag }}</code> {{ $what }}{{ $loop->last ? '.' : ',' }} @endforeach
                </p>
            </div>
        @endforeach
    </div>

    <div class="rounded-xl border border-slate-200">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-100 p-3">
            <h3 class="flex items-center gap-2 font-semibold"><x-icon name="message" class="h-4 w-4 text-brand-600" /> Today's reminders · Aaj kis ko jayega ({{ $preview->count() }})</h3>
            @if ($sentToday->isNotEmpty())
                <span class="text-xs text-slate-500">Already today: {{ $sentToday->map(fn ($n, $st) => $n.' '.$st)->join(', ') }}</span>
            @endif
        </div>
        <ul class="max-h-56 divide-y divide-slate-100 overflow-y-auto text-sm">
            @forelse ($preview->take(20) as $r)
                <li class="p-3"><div class="font-mono text-xs text-slate-500">{{ $r['phone'] }}</div><div>{{ $r['body'] }}</div></li>
            @empty
                <li class="p-4 text-slate-500">Nobody needs a reminder today with these settings.</li>
            @endforelse
        </ul>
        <p class="border-t border-slate-100 p-3 text-xs text-slate-500">A reminder already sent is never sent again. The list changes by itself each day.</p>
    </div>

    @if ($result)
        <p class="rounded-lg bg-slate-50 p-2.5 text-sm">Done: {{ $result['sent'] }} sent, {{ $result['failed'] }} failed, {{ $result['waiting'] }} waiting in the outbox, {{ $result['skipped'] }} already sent before.</p>
    @endif

    <div class="flex flex-wrap items-end justify-between gap-3 border-t border-slate-100 pt-4">
        <div>
            <div class="flex items-end gap-2">
                <x-field label="Send a test message to" name="testPhone"><input id="testPhone" class="input" wire:model="testPhone" placeholder="0300 1234567"></x-field>
                <button type="button" class="btn" wire:click="sendTest" @disabled(! $smsOn)><x-icon name="message" class="h-4 w-4" /> Test</button>
            </div>
            <p class="mt-1 text-xs text-slate-500">Check that a message reaches your phone before switching reminders on.</p>
        </div>
        <div class="flex gap-2">
            <button type="button" class="btn" wire:click="runNow" wire:confirm="Send today's reminders now?" @disabled(! $s['enabled'])>Send today's now</button>
            <button type="button" class="btn btn-primary" wire:click="save">Save reminders</button>
        </div>
    </div>
</section>
