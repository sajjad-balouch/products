<div>
    <x-page-header title="Balance sheet" subtitle="What the business owns, what it owes, and the owner's share, on one day. Assets always equal liabilities plus equity." />
    @include('livewire.biz.report-bar', ['title' => 'Balance sheet', 'single' => true])

    <div class="grid gap-4 lg:grid-cols-2">
        <div class="card-pad">
            <h2 class="mb-2 text-sm font-semibold tracking-wide text-slate-500 uppercase">Assets</h2>
            <table class="w-full text-sm">
                @foreach ($assets as $a)
                    <tr class="border-b border-slate-100"><td class="py-1.5"><a class="hover:underline" href="{{ route('biz.ledger', ['account' => $a->id]) }}" wire:navigate>{{ $a->name }}</a></td><td class="py-1.5 text-end">{{ rs($a->balance) }}</td></tr>
                @endforeach
                <tr class="font-bold"><td class="pt-3">Total assets</td><td class="pt-3 text-end">{{ rs($total_assets) }}</td></tr>
            </table>
        </div>
        <div class="card-pad">
            <h2 class="mb-2 text-sm font-semibold tracking-wide text-slate-500 uppercase">Liabilities</h2>
            <table class="mb-4 w-full text-sm">
                @forelse ($liabilities as $a)
                    <tr class="border-b border-slate-100"><td class="py-1.5"><a class="hover:underline" href="{{ route('biz.ledger', ['account' => $a->id]) }}" wire:navigate>{{ $a->name }}</a></td><td class="py-1.5 text-end">{{ rs($a->balance) }}</td></tr>
                @empty
                    <tr><td class="py-1.5 text-slate-500">None</td><td></td></tr>
                @endforelse
                <tr class="font-semibold"><td class="py-1.5">Total liabilities</td><td class="py-1.5 text-end">{{ rs($total_liabilities) }}</td></tr>
            </table>
            <h2 class="mb-2 text-sm font-semibold tracking-wide text-slate-500 uppercase">Equity</h2>
            <table class="w-full text-sm">
                @foreach ($equity as $a)
                    <tr class="border-b border-slate-100"><td class="py-1.5"><a class="hover:underline" href="{{ route('biz.ledger', ['account' => $a->id]) }}" wire:navigate>{{ $a->name }}</a></td><td class="py-1.5 text-end">{{ rs($a->balance) }}</td></tr>
                @endforeach
                <tr class="border-b border-slate-100"><td class="py-1.5">Profit to date (not yet closed)</td><td class="py-1.5 text-end">{{ rs($earnings) }}</td></tr>
                <tr class="font-semibold"><td class="py-1.5">Total equity</td><td class="py-1.5 text-end">{{ rs($total_equity) }}</td></tr>
                <tr class="font-bold"><td class="pt-3">Liabilities and equity</td><td class="pt-3 text-end">{{ rs($total_liabilities + $total_equity) }}</td></tr>
            </table>
        </div>
    </div>
    <p @class(['mt-3 text-sm', 'text-emerald-700' => $total_assets === $total_liabilities + $total_equity, 'text-red-700' => $total_assets !== $total_liabilities + $total_equity])>
        {{ $total_assets === $total_liabilities + $total_equity ? 'Balanced: assets equal liabilities plus equity.' : 'Not balanced. Please check the vouchers.' }}
    </p>
</div>
