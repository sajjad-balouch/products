<div>
    <x-page-header title="Profit on sales" subtitle="Every sale against what those very pieces cost: installment sales and cash or wholesale sales. The same product bought at different prices gives different profits, as it should." />
    @include('livewire.biz.report-bar', ['title' => 'Profit on sales'])

    <div class="mb-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat icon="tag" tone="blue" label="Sales (before markup)" :value="rs($totals['cash'])" :note="$sales->count().' on installments, '.$lines->pluck('invoice_id')->unique()->count().' cash / wholesale bills'" />
        <x-stat icon="package" tone="slate" label="Cost of the pieces sold" :value="rs($totals['cost'])" />
        <x-stat icon="percent" tone="violet" label="Markup on installments" :value="rs($totals['markup'])" note="Earned as installments are paid" />
        <x-stat icon="trend" :tone="$totals['profit'] >= 0 ? 'green' : 'red'" label="Total profit" :value="rs($totals['profit'])" :note="($totals['cash'] ? round(($totals['cash'] - $totals['cost']) / max(1, $totals['cash']) * 100, 1).'% trading margin' : '').($discounts ? ' · after '.rs($discounts).' discounts' : '')" />
    </div>
    @if ($unknown)
        <p class="mb-3 rounded-lg bg-amber-50 p-2.5 text-sm text-amber-900 ring-1 ring-amber-200">{{ $unknown }} installment {{ str('sale')->plural($unknown) }} had no cost price, so {{ $unknown === 1 ? 'it is' : 'they are' }} left out of the totals. Sell from your products and stock to always have the cost.</p>
    @endif

    <div class="grid gap-4 xl:grid-cols-3">
        <div class="space-y-4 xl:col-span-2">
            <div class="card overflow-x-auto">
                <div class="border-b border-slate-200 px-4 py-3 text-sm font-semibold">Installment sales</div>
                <table class="tbl">
                    <thead><tr><th>Sale</th><th>Item</th><th class="text-end">Cash price</th><th class="text-end">Piece cost</th><th class="text-end">Trading profit</th><th class="text-end">Markup</th><th class="text-end">Total profit</th></tr></thead>
                    <tbody>
                    @forelse ($sales as $c)
                        @php($trading = $c->cost_price !== null ? $c->cash_price - $c->cost_price : null)
                        <tr wire:key="s-{{ $c->id }}">
                            <td><a class="font-medium text-brand-600 hover:underline" href="{{ route('biz.contract', $c) }}" wire:navigate>{{ $c->number }}</a><div class="text-xs text-slate-500">{{ $c->sale_date->format('j M Y') }} · {{ $c->customer->name }}</div></td>
                            <td class="min-w-52">{{ $c->item }}@if ($c->stockUnit)<div class="text-xs text-slate-500">bought {{ $c->stockUnit->purchase->date->format('j M Y') }} · {{ $c->stockUnit->purchase->number }}</div>@endif</td>
                            <td class="text-end">{{ number_format($c->cash_price) }}</td>
                            <td class="text-end">{{ $c->cost_price !== null ? number_format($c->cost_price) : '—' }}</td>
                            <td @class(['text-end', 'text-red-700' => ($trading ?? 0) < 0])>{{ $trading !== null ? number_format($trading) : '—' }}</td>
                            <td class="text-end">{{ number_format($c->markup) }}</td>
                            <td @class(['text-end font-semibold whitespace-nowrap', 'text-emerald-700' => ($c->grossProfit() ?? 0) >= 0, 'text-red-700' => ($c->grossProfit() ?? 0) < 0])>{{ $c->grossProfit() !== null ? rs($c->grossProfit()) : '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="p-6 text-center text-slate-500">No installment sales in these dates.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>

            <div class="card overflow-x-auto">
                <div class="border-b border-slate-200 px-4 py-3 text-sm font-semibold">Cash and wholesale sales</div>
                <table class="tbl">
                    <thead><tr><th>Bill</th><th>Product</th><th class="text-end">Qty</th><th class="text-end">Sold for</th><th class="text-end">Cost of pieces</th><th class="text-end">Profit</th></tr></thead>
                    <tbody>
                    @forelse ($lines as $l)
                        <tr wire:key="l-{{ $l->id }}">
                            <td><a class="font-medium text-brand-600 hover:underline" href="{{ route('biz.sale', $l->invoice_id) }}" wire:navigate>{{ $l->invoice->number }}</a><div class="text-xs text-slate-500">{{ $l->invoice->date->format('j M Y') }} · {{ $l->invoice->buyer() }}</div></td>
                            <td class="min-w-52">{{ $l->product->label() }}</td>
                            <td class="text-end">{{ number_format($l->quantity) }}</td>
                            <td class="text-end">{{ number_format($l->total) }}</td>
                            <td class="text-end">{{ number_format($l->cost) }}</td>
                            <td @class(['text-end font-semibold whitespace-nowrap', 'text-emerald-700' => $l->total >= $l->cost, 'text-red-700' => $l->total < $l->cost])>{{ rs($l->total - $l->cost) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="p-6 text-center text-slate-500">No cash or wholesale sales in these dates.</td></tr>
                    @endforelse
                    </tbody>
                    @if ($discounts)
                        <tfoot><tr><td colspan="5">Discounts given on bills</td><td class="text-end text-red-700">− {{ rs($discounts) }}</td></tr></tfoot>
                    @endif
                </table>
            </div>
        </div>
        <div class="card-pad">
            <h2 class="panel-title"><x-icon name="package" class="h-5 w-5 text-brand-600" /> By product</h2>
            <table class="mt-3 w-full text-sm">
                @forelse ($byProduct as $name => $row)
                    <tr class="border-b border-slate-100 last:border-0">
                        <td class="py-2">{{ $name }}<div class="text-xs text-slate-500">{{ number_format($row['count']) }} sold · cost {{ rs($row['cost']) }}</div></td>
                        <td @class(['py-2 text-end font-semibold whitespace-nowrap', 'text-emerald-700' => $row['profit'] >= 0, 'text-red-700' => $row['profit'] < 0])>{{ rs($row['profit']) }}</td>
                    </tr>
                @empty
                    <tr><td class="py-2 text-slate-500">Nothing yet.</td></tr>
                @endforelse
            </table>
        </div>
    </div>
</div>
