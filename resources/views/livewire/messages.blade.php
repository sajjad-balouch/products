<div>
    <x-page-header title="Messages" :subtitle="$smsOn
        ? $channelLabel.' is connected: automatic reminders go by themselves. The messages below can go with one tap, or all at once.'
        : 'Absent alerts, fee reminders and notices wait here. Tap Send to open WhatsApp with the message ready.'" />
    <form wire:submit="compose" class="card-pad mb-4 grid gap-3 sm:grid-cols-4">
        <x-field label="Send to" name="audience">
            <select id="audience" class="input" wire:model="audience"><option value="all">All parents</option>@foreach ($sections as $s)<option value="section:{{ $s->id }}">{{ $s->label }}</option>@endforeach</select>
        </x-field>
        <x-field label="Message" name="body" class="sm:col-span-3"><textarea id="body" class="input" rows="2" wire:model="body"></textarea></x-field>
        <div class="sm:col-span-4"><button class="btn btn-primary">Add to outbox</button></div>
    </form>
    <div class="mb-3 flex flex-wrap items-center gap-2">
        <button @class(['btn btn-sm', 'btn-primary' => $tab === 'queued']) wire:click="$set('tab', 'queued')">To send ({{ $queuedCount }})</button>
        <button @class(['btn btn-sm', 'btn-primary' => $tab === 'sent']) wire:click="$set('tab', 'sent')">Sent</button>
        @if ($failedCount)<button @class(['btn btn-sm', 'btn-primary' => $tab === 'failed', 'text-red-700' => $tab !== 'failed']) wire:click="$set('tab', 'failed')">Not sent ({{ $failedCount }})</button>@endif
        @if ($inLine)<span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-800 ring-1 ring-amber-200"><x-icon name="clock" class="h-3.5 w-3.5" /> {{ $inLine }} in line for the SMS phone</span>@endif
        @if ($smsOn && $queuedCount && $tab === "queued")<button class="btn btn-sm btn-primary ms-auto" wire:click="sendAllAutomatically" wire:confirm="Send all {{ $queuedCount }} by {{ $channelLabel }} now?"><x-icon name="message" class="h-4 w-4" /> Send all by {{ $channelLabel }}</button>@endif
    </div>
    <div class="card overflow-hidden">
        <ul class="divide-y divide-slate-100">
            @forelse ($rows as $message)
                <li class="flex flex-wrap items-center justify-between gap-3 p-3 text-sm" wire:key="ms-{{ $message->id }}">
                    <div class="min-w-0 flex-1 basis-64">
                        <div class="text-xs text-slate-500"><span @class(['mr-1 rounded px-1.5 py-0.5 font-semibold', 'bg-sky-100 text-sky-800' => $message->channel !== 'whatsapp', 'bg-emerald-100 text-emerald-800' => $message->channel === 'whatsapp'])>{{ \App\Services\Sms::label($message->channel) }}</span>
                            {{ $message->guardian?->name ?? $message->customer?->name }} · {{ $message->phone }} · {{ ucfirst($message->category) }}@if ($message->dedupe_key) · automatic @endif</div>
                        <p class="mt-0.5">{{ $message->body }}</p>
                        @if ($message->status === 'failed' && $message->error)<p class="mt-1 text-xs text-red-700">{{ $message->error }}</p>@endif
                    </div>
                    @if ($message->status === 'queued' && $message->channel !== 'whatsapp' || $message->status === 'sending')
                        <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800">In line</span>
                    @elseif ($message->status === 'queued')
                        <div class="flex gap-1">
                            <a class="btn btn-sm btn-primary" href="{{ $message->whatsapp_url }}" target="_blank" rel="noopener" wire:click="markSent({{ $message->id }})">Send</a>
                            <button class="btn btn-sm" wire:click="discard({{ $message->id }})">Discard</button>
                        </div>
                    @elseif ($message->status === 'failed')
                        <div class="flex gap-1">
                            @if ($smsOn)<button class="btn btn-sm btn-primary" wire:click="retry({{ $message->id }})">Try again</button>@endif
                            <a class="btn btn-sm" href="{{ $message->whatsapp_url }}" target="_blank" rel="noopener" wire:click="sentByWhatsapp({{ $message->id }})">WhatsApp</a>
                            <button class="btn btn-sm" wire:click="discard({{ $message->id }})">Discard</button>
                        </div>
                    @else
                        <span class="text-xs text-slate-500">{{ $message->sent_at?->format('j M, g:i a') }}</span>
                    @endif
                </li>
            @empty
                <li class="p-8 text-center text-sm text-slate-500">Nothing here.</li>
            @endforelse
        </ul>
    </div>
    <div class="mt-3">{{ $rows->links() }}</div>
</div>
