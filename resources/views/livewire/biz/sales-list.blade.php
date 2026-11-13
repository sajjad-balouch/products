<div>
    <x-page-header title="Sales" subtitle="Cash, bank and credit (udhaar) sales, retail and wholesale.">
        <a class="btn btn-primary" href="{{ route('biz.sales.new') }}" wire:navigate><x-icon name="plus" class="h-4 w-4" /> New sale</a>
    </x-page-header>

    <div class="no-print mb-4 flex flex-wrap items-center gap-2">
        @unless ($unpaid)
            <input type="date" class="input w-auto" wire:model.live="from" aria-label="From"><span class="text-sm text-slate-500">to</span><input type="date" class="input w-auto" wire:model.live="to" aria-label="To">
        @endunless
        <input class="input max-w-xs" type="search" placeholder="Customer or INV-00001" wire:model.live.debounce.300ms="search" aria-label="Search">
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model.live="unpaid"> Only bills with udhaar left</label>
    </div>

    <div class="mb-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat icon="receipt" tone="blue" label="Sales" :value="rs($sum['total'])" :note="$invoices->whereNull('voided_at')->count().' bills'" />
        <x-stat icon="cash" tone="green" label="Received" :value="rs($sum['paid'])" />
        <x-stat icon="wallet" :tone="$sum['owed'] ? 'amber' : 'green'" label="Udhaar left" :value="rs($sum['owed'])" />
        <x-stat icon="trend" :tone="$sum['profit'] >= 0 ? 'green' : 'red'" label="Profit" :value="rs($sum['profit'])" note="Each piece at its own cost" />
    </div>

    <div class="card overflow-x-auto">
        <table class="tbl">
            <thead><tr><th>Bill</th><th>Customer</th><th class="text-end">Total</th><th class="text-end">Paid</th><th class="text-end">Udhaar</th><th class="text-end">Profit</th><th></th></tr></thead>
            <tbody>
            @forelse ($invoices as $inv)
                <tr wire:key="i-{{ $inv->id }}" @class(['opacity-50' => $inv->voided_at])>
                    <td><a class="font-medium text-brand-600 hover:underline" href="{{ route('biz.sale', $inv) }}" wire:navigate>{{ $inv->number }}</a><div class="text-xs text-slate-500">{{ $inv->date->format('j M Y') }}</div></td>
                    <td>{{ $inv->buyer() }}@if ($inv->voided_at)<span class="badge badge-red ms-1">Cancelled</span>@endif</td>
                    <td class="text-end">{{ rs($inv->total) }}</td>
                    <td class="text-end">{{ rs($inv->paid) }}</td>
                    <td class="text-end">@if ($inv->balance())<span class="font-semibold text-amber-700">{{ rs($inv->balance()) }}</span>@else — @endif</td>
                    <td class="text-end whitespace-nowrap">{{ $inv->voided_at ? '—' : rs($inv->profit()) }}</td>
                    <td class="text-end"><a class="btn btn-sm" href="{{ route('biz.print.invoice', $inv) }}" target="_blank" title="Print"><x-icon name="file-plus" class="h-3.5 w-3.5" /></a></td>
                </tr>
            @empty
                <tr><td colspan="7" class="p-6 text-center text-slate-500">No sales here.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
