<div>
    <x-page-header title="Ledger" subtitle="Every entry in one account, with the running balance. Choose Cash in hand for the cash book." />
    @include('livewire.biz.report-bar', ['title' => 'Ledger: '.($chosen?->label() ?? '')])

    <div class="no-print mb-4">
        <select class="input max-w-md" wire:model.live="account" aria-label="Account">
            @foreach ($accounts->groupBy('type') as $type => $group)
                <optgroup label="{{ \App\Models\Biz\Account::TYPES[$type] }}">@foreach ($group as $a)<option value="{{ $a->id }}" @selected($a->id === $chosen?->id)>{{ $a->label() }}</option>@endforeach</optgroup>
            @endforeach
        </select>
    </div>

    @if ($statement)
        <div class="card overflow-x-auto">
            <table class="tbl">
                <thead><tr><th>Date</th><th>Voucher</th><th>Description</th><th class="text-end">Debit</th><th class="text-end">Credit</th><th class="text-end">Balance</th></tr></thead>
                <tbody>
                    <tr class="bg-slate-50"><td colspan="5" class="font-medium">Balance brought forward</td><td class="text-end font-semibold">{{ rs($statement['opening']) }}</td></tr>
                    @forelse ($statement['lines'] as $l)
                        <tr wire:key="l-{{ $l->id }}">
                            <td class="whitespace-nowrap">{{ $l->entry->date->format('j M Y') }}</td>
                            <td><a class="font-mono text-xs hover:underline" href="{{ route('biz.print.voucher', $l->entry_id) }}" target="_blank">{{ $l->entry->number }}</a></td>
                            <td>{{ $l->memo ?: $l->entry->memo }}@if ($l->customer)<div class="text-xs text-slate-500">{{ $l->customer->name }}</div>@endif</td>
                            <td class="text-end">{{ $l->debit ? number_format($l->debit) : '' }}</td>
                            <td class="text-end">{{ $l->credit ? number_format($l->credit) : '' }}</td>
                            <td class="text-end">{{ rs($l->running) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="p-6 text-center text-slate-500">No entries in these dates.</td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="font-semibold"><td colspan="3">Total and closing balance</td><td class="text-end">{{ number_format($statement['lines']->sum('debit')) }}</td><td class="text-end">{{ number_format($statement['lines']->sum('credit')) }}</td><td class="text-end">{{ rs($statement['closing']) }}</td></tr>
                </tfoot>
            </table>
        </div>
    @endif
</div>
