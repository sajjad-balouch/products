<div>
    <x-page-header title="Vouchers" subtitle="Every entry in your books. Pay an expense, record money coming in, or make a journal entry. A wrong voucher is reversed, never deleted.">
        <button class="btn" wire:click="open('receipt')"><x-icon name="arrow-right" class="h-4 w-4 rotate-180" /> Money in</button>
        <button class="btn" wire:click="open('journal')"><x-icon name="book" class="h-4 w-4" /> Journal</button>
        <button class="btn" wire:click="open('payment')"><x-icon name="arrow-right" class="h-4 w-4" /> Pay expense</button>
        <a class="btn btn-primary" href="{{ route('biz.entry') }}" wire:navigate><x-icon name="plus" class="h-4 w-4" /> New entry · Kya hua?</a>
    </x-page-header>

    <p class="mb-4 rounded-xl border border-sky-200 bg-sky-50/70 p-3 text-sm text-slate-700">Not sure which voucher to use? Click <strong>New entry · Kya hua?</strong> and just choose what happened, like "paid the rent" or "owner put money in". The system does the debit and credit. · Samajh na aaye to <strong>Kya hua?</strong> dabayein aur bas chunein ke kya hua.</p>

    <div class="card">
        <div class="flex flex-wrap items-center gap-2 border-b border-slate-200 p-3">
            <select class="input w-auto" wire:model.live="kind" aria-label="Kind">
                <option value="">All vouchers</option>
                @foreach (\App\Models\Biz\JournalEntry::KINDS as $key => [$label])<option value="{{ $key }}">{{ $label }}</option>@endforeach
            </select>
            <input type="date" class="input w-auto" wire:model.live="from" aria-label="From">
            <span class="text-sm text-slate-500">to</span>
            <input type="date" class="input w-auto" wire:model.live="to" aria-label="To">
        </div>
        <div class="overflow-x-auto">
            <table class="tbl">
                <thead><tr><th>Voucher</th><th>Date</th><th>Description</th><th>Accounts</th><th class="text-end">Amount</th><th></th></tr></thead>
                <tbody>
                @forelse ($entries as $e)
                    <tr wire:key="e-{{ $e->id }}" @class(['opacity-60' => $e->reversed_at])>
                        <td><span class="font-mono text-xs font-semibold">{{ $e->number }}</span><div class="text-xs text-slate-500">{{ $e->kindLabel() }}</div></td>
                        <td class="whitespace-nowrap">{{ $e->date->format('j M Y') }}</td>
                        <td>{{ $e->memo }}@if ($e->reversed_at)<span class="badge badge-red ms-1">Reversed</span>@endif</td>
                        <td class="text-xs">
                            @foreach ($e->lines as $l)
                                <div class="flex justify-between gap-3"><span>{{ $l->credit ? '→ ' : '' }}{{ $l->account->name }}</span><span class="text-slate-500">{{ $l->debit ? 'Dr '.number_format($l->debit) : 'Cr '.number_format($l->credit) }}</span></div>
                            @endforeach
                        </td>
                        <td class="text-end font-medium">{{ rs($e->total()) }}</td>
                        <td class="text-end whitespace-nowrap">
                            <a class="btn btn-sm" href="{{ route('biz.print.voucher', $e) }}" target="_blank" title="Print"><x-icon name="file-plus" class="h-3.5 w-3.5" /></a>
                            @if (! $e->reversed_at && ! in_array($e->kind, ['reversal', 'sale', 'collection'], true))
                                <button class="btn btn-sm" wire:click="$set('reverseId', {{ $e->id }})" title="Reverse"><x-icon name="repeat" class="h-3.5 w-3.5" /></button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-6 text-center text-slate-500">No vouchers in these dates.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3">{{ $entries->links() }}</div>
    </div>

    @if ($newKind)
        @php($titles = ['payment' => 'Pay an expense (payment voucher)', 'receipt' => 'Money in (receipt voucher)', 'journal' => 'Journal voucher'])
        <x-modal :title="$titles[$newKind]" close="$set('newKind', null)" size="lg">
            {{-- Leaving any box refreshes the preview below. --}}
            <form wire:submit="post" x-on:change="$wire.$refresh()" class="space-y-3">
                <div class="grid gap-3 sm:grid-cols-3">
                    <x-field label="Date" name="date"><input id="date" type="date" class="input" wire:model="date"></x-field>
                    @if ($newKind !== 'journal')
                        <x-field :label="$newKind === 'payment' ? 'Paid from' : 'Received into'" name="moneyAccount" class="sm:col-span-2">
                            <select id="moneyAccount" class="input" wire:model="moneyAccount">@foreach ($moneyAccounts as $a)<option value="{{ $a->id }}">{{ $a->label() }}</option>@endforeach</select>
                        </x-field>
                    @endif
                    <x-field label="Description" name="memo" class="{{ $newKind === 'journal' ? 'sm:col-span-2' : 'sm:col-span-3' }}"><input id="memo" class="input" wire:model="memo" placeholder="{{ $newKind === 'payment' ? 'Shop rent for November' : ($newKind === 'receipt' ? 'Capital put in by the owner' : 'Opening balances') }}"></x-field>
                </div>

                <div class="space-y-2">
                    <div class="label">{{ $newKind === 'payment' ? 'What was it for' : ($newKind === 'receipt' ? 'Where did it come from' : 'Lines') }}</div>
                    @foreach ($lines as $i => $line)
                        <div class="grid grid-cols-12 items-start gap-2" wire:key="ln-{{ $i }}">
                            <div class="col-span-12 sm:col-span-5">
                                <select class="input" wire:model="lines.{{ $i }}.account" aria-label="Account">
                                    <option value="">Account…</option>
                                    @foreach ($accounts->groupBy('type') as $type => $group)
                                        <optgroup label="{{ \App\Models\Biz\Account::TYPES[$type] }}">@foreach ($group as $a)<option value="{{ $a->id }}">{{ $a->label() }}</option>@endforeach</optgroup>
                                    @endforeach
                                </select>
                                @error('lines.'.$i.'.account')<p class="err">{{ $message }}</p>@enderror
                            </div>
                            @if ($newKind === 'journal')
                                {{-- Plain words instead of debit and credit; the system works out which one it is. --}}
                                <div class="col-span-6 sm:col-span-2">
                                    <select class="input" wire:model="lines.{{ $i }}.side" aria-label="Goes up or down">
                                        <option value="up">Goes up · Barhega</option>
                                        <option value="down">Goes down · Ghatega</option>
                                    </select>
                                    @if ($line['account'] !== '' && isset($accountTypes[(int) $line['account']]))
                                        <p class="mt-0.5 text-[11px] text-slate-500">= {{ \App\Livewire\Biz\Vouchers::debitSide($accountTypes[(int) $line['account']], $line['side'] ?? 'up') ? 'Debit' : 'Credit' }}</p>
                                    @endif
                                </div>
                                <div class="col-span-6 sm:col-span-2"><input class="input" type="number" min="1" placeholder="Amount" wire:model="lines.{{ $i }}.amount" aria-label="Amount">@error('lines.'.$i.'.amount')<p class="err">{{ $message }}</p>@enderror</div>
                            @else
                                <div class="col-span-6 sm:col-span-4"><input class="input" type="number" min="1" placeholder="Amount" wire:model="lines.{{ $i }}.amount" aria-label="Amount">@error('lines.'.$i.'.amount')<p class="err">{{ $message }}</p>@enderror</div>
                            @endif
                            <input class="input col-span-5 {{ $newKind === 'journal' ? 'sm:col-span-2' : 'sm:col-span-2' }}" placeholder="Note" wire:model="lines.{{ $i }}.memo" aria-label="Note">
                            <button type="button" class="btn col-span-1 px-2" wire:click="removeLine({{ $i }})" @disabled(count($lines) <= ($newKind === 'journal' ? 2 : 1)) aria-label="Remove line"><x-icon name="x-circle" class="h-4 w-4" /></button>
                        </div>
                    @endforeach
                    <button type="button" class="btn btn-sm" wire:click="addLine"><x-icon name="plus" class="h-3.5 w-3.5" /> Add line</button>
                    @if ($newKind === 'journal')
                        @php($dr = collect($lines)->filter(fn ($l) => isset($accountTypes[(int) $l['account']]) && \App\Livewire\Biz\Vouchers::debitSide($accountTypes[(int) $l['account']], $l['side'] ?? 'up'))->sum(fn ($l) => (int) ($l['amount'] ?: 0)))
                        @php($cr = collect($lines)->filter(fn ($l) => isset($accountTypes[(int) $l['account']]) && ! \App\Livewire\Biz\Vouchers::debitSide($accountTypes[(int) $l['account']], $l['side'] ?? 'up'))->sum(fn ($l) => (int) ($l['amount'] ?: 0)))
                        <p @class(['text-sm', 'text-emerald-700' => $dr === $cr && $dr > 0, 'text-red-700' => $dr !== $cr])>
                            @if ($dr === $cr && $dr > 0)
                                Both sides match ({{ rs($dr) }}). · Dono taraf barabar hain.
                            @else
                                The two sides must match: debit {{ rs($dr) }}, credit {{ rs($cr) }}, difference {{ rs(abs($dr - $cr)) }}. · Dono taraf barabar honi chahiyein.
                                <span class="block text-xs text-slate-500">Example: cash goes up 50,000 and owner's capital goes up 50,000. · Misal: cash 50,000 barha aur capital 50,000 barha.</span>
                            @endif
                        </p>
                    @else
                        <p class="text-sm text-slate-600">Total {{ rs(collect($lines)->sum(fn ($l) => (int) ($l['amount'] ?: 0))) }}</p>
                    @endif
                    @error('lines')<p class="err">{{ $message }}</p>@enderror
                </div>
                <x-biz.impact :impact="$impact" />
                <x-biz.guide :key="$newKind" />
                <div class="flex justify-end gap-2">
                    <button type="button" class="btn" wire:click="$set('newKind', null)">Cancel</button>
                    <button class="btn btn-primary" wire:loading.attr="disabled">Post voucher</button>
                </div>
            </form>
        </x-modal>
    @endif

    @if ($reverseId)
        <x-modal title="Reverse voucher" close="$set('reverseId', null)" size="sm">
            <form wire:submit="reverse" class="space-y-3">
                <p class="text-sm text-slate-600">A new entry with the opposite amounts is posted today, so the books show both. The original stays on record.</p>
                <x-field label="Reason" name="reverseReason"><input id="reverseReason" class="input" wire:model="reverseReason"></x-field>
                <x-biz.impact :impact="$impact" />
                <x-biz.guide key="reverse" />
                <div class="flex justify-end gap-2">
                    <button type="button" class="btn" wire:click="$set('reverseId', null)">Cancel</button>
                    <button class="btn btn-danger">Reverse</button>
                </div>
            </form>
        </x-modal>
    @endif
</div>
