<div>
    <x-page-header title="Trial balance" subtitle="Every account's balance. Total debits always equal total credits, which shows the books are in order." />
    @include('livewire.biz.report-bar', ['title' => 'Trial balance', 'single' => true])

    <div class="card overflow-x-auto">
        <table class="tbl">
            <thead><tr><th>Code</th><th>Account</th><th>Type</th><th class="text-end">Debit</th><th class="text-end">Credit</th></tr></thead>
            <tbody>
            @forelse ($rows as $a)
                <tr wire:key="t-{{ $a->id }}">
                    <td class="font-mono text-xs">{{ $a->code }}</td>
                    <td><a class="hover:underline" href="{{ route('biz.ledger', ['account' => $a->id]) }}" wire:navigate>{{ $a->name }}</a></td>
                    <td class="text-xs text-slate-500">{{ \App\Models\Biz\Account::TYPES[$a->type] }}</td>
                    <td class="text-end">{{ $a->dr ? number_format($a->dr) : '' }}</td>
                    <td class="text-end">{{ $a->cr ? number_format($a->cr) : '' }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="p-6 text-center text-slate-500">No entries yet.</td></tr>
            @endforelse
            </tbody>
            <tfoot>
                <tr class="font-semibold"><td colspan="3">Total @if ($dr === $cr)<span class="badge badge-green ms-1">Balanced</span>@else<span class="badge badge-red ms-1">Not balanced</span>@endif</td><td class="text-end">{{ number_format($dr) }}</td><td class="text-end">{{ number_format($cr) }}</td></tr>
            </tfoot>
        </table>
    </div>
</div>
