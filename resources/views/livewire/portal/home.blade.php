<div>
    @if ($children->count() > 1)
        <div class="mb-3 flex gap-2 overflow-x-auto pb-1" role="group" aria-label="{{ __('Choose a child') }}">
            @foreach ($children as $c)
                <button wire:click="$set('childId', {{ $c->id }})" @class(['flex shrink-0 items-center gap-2 rounded-full border px-3.5 py-2 text-sm font-semibold shadow-soft', 'border-brand-600 bg-brand-600 text-white' => $child?->id === $c->id, 'border-slate-300 bg-white text-slate-700' => $child?->id !== $c->id]) aria-pressed="{{ $child?->id === $c->id ? 'true' : 'false' }}">
                    <x-icon name="users" class="h-4 w-4" /> {{ $c->name }}
                </button>
            @endforeach
        </div>
    @endif

    @if ($child)
        <div class="relative mb-4 flex items-center gap-4 overflow-hidden rounded-3xl bg-brand-900 p-5 text-white shadow-lift">
            <div class="bg-dots pointer-events-none absolute inset-0 opacity-60"></div>
            <div class="pointer-events-none absolute -end-10 -top-12 h-40 w-40 rounded-full bg-accent-500/25 blur-2xl"></div>
            <div class="relative grid h-14 w-14 shrink-0 place-items-center rounded-2xl bg-gradient-to-br from-accent-400 to-accent-600 text-lg font-bold shadow-lg ring-2 ring-white/20">{{ collect(explode(' ', $child->name))->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('') }}</div>
            <div class="relative min-w-0">
                <div class="font-display truncate text-xl font-extrabold" dir="auto">{{ $child->name }}</div>
                <div class="truncate text-sm text-white/80">{{ $child->section->label }}</div>
            </div>
        </div>
    @endif

    <nav class="sticky top-[68px] z-20 mb-4 grid grid-cols-5 gap-1 rounded-2xl border border-slate-200/80 bg-white/90 p-1 shadow-soft backdrop-blur" aria-label="{{ __('Sections') }}">
        @foreach (['today' => ['Today', 'home'], 'fees' => ['Fees', 'wallet'], 'attendance' => ['Attendance', 'check-square'], 'results' => ['Results', 'trophy'], 'diary' => ['Diary', 'book-open']] as $key => [$label, $icon])
            <button wire:click="$set('tab', '{{ $key }}')" @class(['flex flex-col items-center gap-0.5 rounded-xl px-1 py-2 text-[11px] font-medium transition sm:text-xs', 'bg-brand-600 font-semibold text-white shadow-soft' => $tab === $key, 'text-slate-500 hover:bg-slate-50' => $tab !== $key]) @if ($tab === $key) aria-current="page" @endif>
                <x-icon :name="$icon" class="h-5 w-5" />{{ __($label) }}
            </button>
        @endforeach
    </nav>

    @if ($tab === 'today')
        @php($status = $todayStatus ? ['P' => ['Present', 'check-circle', 'green'], 'A' => ['Absent', 'x-circle', 'red'], 'L' => ['Late', 'clock', 'amber'], 'V' => ['On leave', 'calendar', 'blue']][$todayStatus] : ['Not marked yet', 'clock', 'slate'])
        <div class="grid grid-cols-2 gap-3">
            <div class="card-pad flex items-center gap-3 p-4!">
                <div class="icon-chip tone-{{ $status[2] }}"><x-icon :name="$status[1]" /></div>
                <div class="min-w-0"><div class="text-xs text-slate-500">{{ __('Today') }}</div><div class="font-semibold leading-tight">{{ __($status[0]) }}</div></div>
            </div>
            <button class="card-pad flex items-center gap-3 p-4! text-start" wire:click="$set('tab', 'fees')">
                <div @class(['icon-chip', 'tone-red' => $balance > 0, 'tone-green' => $balance === 0])><x-icon name="wallet" /></div>
                <div class="min-w-0"><div class="text-xs text-slate-500">{{ __('Fee due') }}</div><div @class(['truncate font-semibold', 'text-red-700' => $balance > 0, 'text-emerald-700' => $balance === 0])>{{ __('Rs') }} {{ number_format($balance) }}</div></div>
            </button>
            <button class="card-pad flex items-center gap-3 p-4! text-start" wire:click="$set('tab', 'attendance')">
                <div class="icon-chip tone-brand"><x-icon name="percent" /></div>
                <div class="min-w-0"><div class="text-xs text-slate-500">{{ __('Last 30 days') }}</div><div class="font-semibold">{{ $attendanceStats['percent'] ?? '-' }}%</div></div>
            </button>
            <button class="card-pad flex items-center gap-3 p-4! text-start" wire:click="$set('tab', 'results')">
                <div class="icon-chip tone-violet"><x-icon name="trophy" /></div>
                <div class="min-w-0"><div class="text-xs text-slate-500">{{ __('Results') }}</div><div class="truncate font-semibold">{{ $results->isEmpty() ? '-' : __('View') }}</div></div>
            </button>
        </div>

        @if ($timetable->isNotEmpty())
            <div class="card mt-4 overflow-hidden">
                <h2 class="flex items-center gap-2 border-b border-slate-200 px-4 py-2.5 text-sm font-semibold"><x-icon name="clock" class="h-4 w-4 text-violet-600" /> {{ __('Periods today') }}</h2>
                <ul class="divide-y divide-slate-100 text-sm">
                    @foreach ($timetable as $entry)<li class="flex items-center justify-between px-4 py-2.5"><span class="font-medium">{{ $entry->subject->name }}</span><span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600" dir="ltr">{{ $entry->period->starts_at }}</span></li>@endforeach
                </ul>
            </div>
        @endif

        <h2 class="mt-5 mb-2 flex items-center gap-2 text-sm font-semibold"><x-icon name="book-open" class="h-4 w-4 text-sky-600" /> {{ __('Latest homework') }}</h2>
        @forelse ($diary->take(2) as $entry)
            <div class="card-pad mb-2 p-4! text-sm"><div class="text-xs text-slate-500"><bdi>{{ $entry->date->format('j M') }}</bdi> · {{ $entry->subject?->name ?? __('General') }}</div><p class="mt-1 whitespace-pre-line" dir="auto">{{ $entry->body }}</p></div>
        @empty
            <p class="text-sm text-slate-500">{{ __('Nothing posted yet.') }}</p>
        @endforelse

        <h2 class="mt-5 mb-2 flex items-center gap-2 text-sm font-semibold"><x-icon name="megaphone" class="h-4 w-4 text-accent-500" /> {{ __('School notices') }}</h2>
        @forelse ($announcements as $notice)
            <div class="card-pad mb-2 text-sm"><div class="font-medium" dir="auto">{{ $notice->title }}</div><div class="text-xs text-slate-500"><bdi>{{ $notice->published_on->format('j M Y') }}</bdi></div><p class="mt-1 whitespace-pre-line" dir="auto">{{ $notice->body }}</p></div>
        @empty
            <p class="text-sm text-slate-500">{{ __('No notices.') }}</p>
        @endforelse
    @endif

    @if ($tab === 'fees')
        @if ($school->jazzcash_number || $school->easypaisa_number || $school->bank_details)
            <div class="card-pad mb-3 text-sm">
                <div class="font-semibold">{{ __('How to pay') }}</div>
                @if ($school->jazzcash_number)<div class="mt-1">JazzCash: <span class="font-mono" dir="ltr">{{ $school->jazzcash_number }}</span></div>@endif
                @if ($school->easypaisa_number)<div>Easypaisa: <span class="font-mono" dir="ltr">{{ $school->easypaisa_number }}</span></div>@endif
                @if ($school->bank_details)<div class="mt-1 whitespace-pre-line" dir="ltr">{{ $school->bank_details }}</div>@endif
                <p class="mt-2 text-xs text-slate-500">{{ __('After paying, tap Upload proof on the voucher.') }}</p>
            </div>
        @endif
        <div class="space-y-3">
            @forelse ($vouchers as $voucher)
                <div class="card-pad text-sm" wire:key="pv-{{ $voucher->id }}">
                    <div class="flex items-start justify-between gap-2">
                        <div><div class="font-semibold"><bdi>{{ $voucher->month->format('F Y') }}</bdi></div><div class="text-xs text-slate-500" dir="ltr">{{ $voucher->voucher_no }}</div></div>
                        @include('partials.voucher-status', ['status' => $voucher->status])
                    </div>
                    <table class="mt-2 w-full text-xs text-slate-600">
                        @foreach ($voucher->lines as $line)<tr><td class="py-0.5">{{ $line->student?->name }} · {{ $line->label }}</td><td class="text-end">{{ number_format($line->amount) }}</td></tr>@endforeach
                        @if ($voucher->late_fee)<tr><td class="py-0.5">{{ __('Late fee') }}</td><td class="text-end">{{ number_format($voucher->late_fee) }}</td></tr>@endif
                    </table>
                    <div class="mt-2 flex justify-between border-t border-slate-100 pt-2 font-semibold"><span>{{ __('Balance') }}</span><span>{{ __('Rs') }} {{ number_format($voucher->balance) }}</span></div>
                    @if ($voucher->balance > 0)<div class="mt-1 text-xs text-slate-500">{{ __('Due date') }}: <bdi>{{ $voucher->due_on->format('j M Y') }}</bdi></div>@endif

                    @foreach ($voucher->proofs->sortByDesc('id')->take(2) as $proof)
                        <div @class(['mt-2 rounded-md p-2 text-xs', 'bg-sky-50 text-sky-900' => $proof->status === 'pending', 'bg-emerald-50 text-emerald-900' => $proof->status === 'approved', 'bg-red-50 text-red-900' => $proof->status === 'rejected'])>
                            {{ __('Proof') }}: {{ __('Rs') }} {{ number_format($proof->amount) }} · {{ __(ucfirst($proof->status)) }}{{ $proof->review_note ? ' · '.$proof->review_note : '' }}
                        </div>
                    @endforeach

                    <div class="mt-3 flex flex-wrap gap-2">
                        @if ($voucher->balance > 0)<button class="btn btn-sm btn-primary" wire:click="startPay({{ $voucher->id }})">{{ __('Upload proof') }}</button>@endif
                        <a class="btn btn-sm" href="{{ route('print.voucher', $voucher->id) }}" target="_blank">{{ __('View voucher') }}</a>
                        @foreach ($voucher->payments->whereNull('voided_at') as $payment)
                            <a class="btn btn-sm" href="{{ route('print.receipt', $payment->id) }}" target="_blank">{{ __('Receipt') }} {{ number_format($payment->amount) }}</a>
                        @endforeach
                    </div>
                </div>
            @empty
                <p class="text-sm text-slate-500">{{ __('No vouchers yet.') }}</p>
            @endforelse
        </div>
    @endif

    @if ($tab === 'attendance')
        <div class="mb-3 grid grid-cols-2 gap-3">
            <div class="card-pad"><div class="text-xs text-slate-500">{{ __('Last 30 days') }}</div><div class="mt-1 text-2xl font-semibold">{{ $attendanceStats['percent'] ?? '-' }}%</div></div>
            <div class="card-pad"><div class="text-xs text-slate-500">{{ __('Absent days') }}</div><div class="mt-1 text-2xl font-semibold">{{ $attendanceStats['absent'] }}</div></div>
        </div>
        <div class="card overflow-hidden">
            <ul class="divide-y divide-slate-100 text-sm">
                @forelse ($attendance as $day)
                    <li class="flex justify-between px-4 py-2"><span><bdi>{{ $day->date->format('D, j M') }}</bdi></span>
                        <span @class(['badge', 'badge-green' => $day->status === 'P', 'badge-red' => $day->status === 'A', 'badge-amber' => $day->status === 'L', 'badge-blue' => $day->status === 'V'])>{{ __(['P' => 'Present', 'A' => 'Absent', 'L' => 'Late', 'V' => 'On leave'][$day->status]) }}</span></li>
                @empty
                    <li class="p-6 text-center text-slate-500">{{ __('Nothing posted yet.') }}</li>
                @endforelse
            </ul>
        </div>
    @endif

    @if ($tab === 'results')
        <div class="space-y-3">
            @forelse ($results as $item)
                <div class="card-pad text-sm">
                    <div class="flex items-start justify-between gap-2">
                        <div class="font-semibold">{{ $item['exam']->name }}</div>
                        <span @class(['badge', 'badge-green' => $item['row']['passed'], 'badge-red' => ! $item['row']['passed']])>{{ $item['row']['grade'] }}</span>
                    </div>
                    <div class="mt-1 text-xs text-slate-500">{{ $item['row']['percent'] }}% · {{ __('Position') }} {{ $item['row']['position'] }}</div>
                    <table class="mt-2 w-full text-xs">
                        @foreach ($item['row']['papers'] as $paper)
                            <tr class="border-t border-slate-100"><td class="py-1">{{ $paper['subject'] }}</td><td @class(['py-1 text-end', 'font-medium text-red-700' => $paper['failed']]) dir="ltr">{{ $paper['obtained'] === null ? __('Absent') : (float) $paper['obtained'] }} / {{ $paper['total'] }}</td></tr>
                        @endforeach
                    </table>
                    <a class="btn btn-sm mt-2" href="{{ route('print.report-card', [$item['exam']->id, $child->id]) }}" target="_blank">{{ __('Report card') }}</a>
                </div>
            @empty
                <p class="text-sm text-slate-500">{{ __('No results published yet.') }}</p>
            @endforelse
        </div>
    @endif

    @if ($tab === 'diary')
        @forelse ($diary as $entry)
            <div class="card-pad mb-2 text-sm"><div class="text-xs text-slate-500"><bdi>{{ $entry->date->format('j M') }}</bdi> · {{ $entry->subject?->name ?? __('General') }}</div><p class="mt-1 whitespace-pre-line" dir="auto">{{ $entry->body }}</p></div>
        @empty
            <p class="text-sm text-slate-500">{{ __('Nothing posted yet.') }}</p>
        @endforelse
    @endif

    @if ($payVoucher)
        <x-modal :title="__('Upload payment proof')" close="$set('payVoucherId', null)">
            <form wire:submit="submitProof" class="space-y-3">
                <x-field :label="__('Amount paid').' ('.__('Rs').')'" name="amount"><input id="amount" class="input" type="number" min="1" max="{{ $payVoucher->balance }}" wire:model="amount"></x-field>
                <x-field :label="__('Paid through')" name="method">
                    <select id="method" class="input" wire:model="method"><option value="jazzcash">JazzCash</option><option value="easypaisa">Easypaisa</option><option value="raast">Raast</option><option value="bank">{{ __('Bank') }}</option></select>
                </x-field>
                <x-field :label="__('Transaction ID')" name="reference"><input id="reference" class="input" dir="ltr" wire:model="reference"></x-field>
                <x-field :label="__('Screenshot')" name="screenshot"><input id="screenshot" class="input" type="file" accept="image/*" wire:model="screenshot"></x-field>
                <div wire:loading wire:target="screenshot" class="text-xs text-slate-500">{{ __('Uploading...') }}</div>
                <button class="btn btn-primary w-full" wire:loading.attr="disabled">{{ __('Send to school') }}</button>
            </form>
        </x-modal>
    @endif
</div>
