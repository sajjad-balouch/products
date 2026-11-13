<div>
    <x-page-header title="Day book" subtitle="Every entry of the day, with its debits and credits." />
    @include('livewire.biz.report-bar', ['title' => 'Day book'])

    <div class="card overflow-x-auto">
        <table class="tbl">
            <thead><tr><th>Date</th><th>Voucher</th><th>Account</th><th class="text-end">Debit</th><th class="text-end">Credit</th></tr></thead>
            <tbody>
            @forelse ($entries as $e)
                <tr class="bg-slate-50" wire:key="e-{{ $e->id }}">
                    <td class="whitespace-nowrap font-medium">{{ $e->date->format('j M Y') }}</td>
                    <td><a class="font-mono text-xs font-semibold hover:underline" href="{{ route('biz.print.voucher', $e) }}" target="_blank">{{ $e->number }}</a></td>
                    <td colspan="3" class="text-sm">{{ $e->memo }}@if ($e->reversed_at)<span class="badge badge-red ms-1">Reversed</span>@endif</td>
                </tr>
                @foreach ($e->lines as $l)
                    <tr wire:key="l-{{ $l->id }}">
                        <td></td><td></td>
                        <td @class(['ps-8' => $l->credit])>{{ $l->account->label() }}@if ($l->memo)<span class="text-xs text-slate-500"> · {{ $l->memo }}</span>@endif</td>
                        <td class="text-end">{{ $l->debit ? number_format($l->debit) : '' }}</td>
                        <td class="text-end">{{ $l->credit ? number_format($l->credit) : '' }}</td>
                    </tr>
                @endforeach
            @empty
                <tr><td colspan="5" class="p-6 text-center text-slate-500">No entries in these dates.</td></tr>
            @endforelse
            </tbody>
            @if ($entries->isNotEmpty())
                <tfoot><tr class="font-semibold"><td colspan="3">Total</td><td class="text-end">{{ number_format($total) }}</td><td class="text-end">{{ number_format($total) }}</td></tr></tfoot>
            @endif
        </table>
    </div>
</div>
