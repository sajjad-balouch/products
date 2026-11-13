<div>
    <x-page-header :title="$p->label()" :subtitle="($p->type?->name ?? 'No type').($p->code ? ' · '.$p->code : '').' · cash price '.rs($p->sale_price)">
        <a class="btn" href="{{ route('biz.products') }}" wire:navigate>All products</a>
        <a class="btn btn-primary" href="{{ route('biz.contracts.new', ['product' => $p->id]) }}" wire:navigate><x-icon name="cash" class="h-4 w-4" /> Sell on installments</a>
    </x-page-header>

    <div class="mb-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat icon="layers" tone="green" label="In stock" :value="$inStock->count()" :note="$inStock->count() ? 'Cost '.rs($inStock->sum('unit_cost')) : 'Add stock to sell'" />
        <x-stat icon="tag" tone="blue" label="Cost per piece" :value="$inStock->count() ? rs($inStock->min('unit_cost')).($inStock->min('unit_cost') !== $inStock->max('unit_cost') ? ' – '.number_format($inStock->max('unit_cost')) : '') : '—'" note="Pieces bought at different prices keep their own cost" />
        <x-stat icon="check-circle" tone="violet" label="Sold" :value="number_format($sold->count())" :note="$sales->count().' on installments, '.$lines->sum('quantity').' cash / wholesale'" />
        <x-stat icon="trend" :tone="$profit >= 0 ? 'green' : 'red'" label="Profit on these sales" :value="rs($profit)" note="Total price less each piece's own cost" />
    </div>

    <div class="grid gap-4 xl:grid-cols-2">
        <div class="card overflow-x-auto">
            <div class="border-b border-slate-200 px-4 py-3 text-sm font-semibold">In stock ({{ $inStock->count() }})</div>
            <table class="tbl">
                <thead><tr><th>#</th><th>Bought</th><th class="text-end">Cost</th><th>Details</th><th></th></tr></thead>
                <tbody>
                @forelse ($inStock as $u)
                    <tr wire:key="u-{{ $u->id }}">
                        <td class="text-xs text-slate-500">{{ $u->id }}</td>
                        <td class="text-xs">{{ $u->purchase->date->format('j M Y') }}<div class="text-slate-500">{{ $u->purchase->number }}{{ $u->purchase->supplier ? ' · '.$u->purchase->supplier : '' }}</div></td>
                        <td class="text-end font-medium">{{ rs($u->unit_cost) }}</td>
                        <td class="text-xs">{{ $u->summary($p->fields()) ?: '—' }}</td>
                        <td class="text-end">@if ($p->fields())<button class="btn btn-sm" wire:click="openUnit({{ $u->id }})" title="Write details"><x-icon name="pencil" class="h-3.5 w-3.5" /></button>@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="p-6 text-center text-slate-500">Nothing in stock.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="card overflow-x-auto">
            <div class="border-b border-slate-200 px-4 py-3 text-sm font-semibold">Sales and profit</div>
            <table class="tbl">
                <thead><tr><th>Plan</th><th class="text-end">Sold for</th><th class="text-end">Piece cost</th><th class="text-end">Profit</th></tr></thead>
                <tbody>
                @forelse ($sales as $c)
                    <tr wire:key="s-{{ $c->id }}">
                        <td><a class="font-medium text-brand-600 hover:underline" href="{{ route('biz.contract', $c) }}" wire:navigate>{{ $c->number }}</a><div class="text-xs text-slate-500">{{ $c->customer->name }} · {{ $c->sale_date->format('j M Y') }}</div></td>
                        <td class="text-end">{{ rs($c->total_price) }}<div class="text-xs text-slate-500">cash {{ number_format($c->cash_price) }} + markup {{ number_format($c->markup) }}</div></td>
                        <td class="text-end">{{ $c->cost_price !== null ? rs($c->cost_price) : '—' }}</td>
                        <td @class(['text-end font-semibold whitespace-nowrap', 'text-emerald-700' => ($c->grossProfit() ?? 0) >= 0, 'text-red-700' => ($c->grossProfit() ?? 0) < 0])>{{ $c->grossProfit() !== null ? rs($c->grossProfit()) : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="p-6 text-center text-slate-500">Not sold yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($lines->isNotEmpty())
            <div class="card overflow-x-auto xl:col-span-2">
                <div class="border-b border-slate-200 px-4 py-3 text-sm font-semibold">Cash and wholesale sales</div>
                <table class="tbl">
                    <thead><tr><th>Invoice</th><th class="text-end">Qty</th><th class="text-end">Price each</th><th class="text-end">Sold for</th><th class="text-end">Cost of pieces</th><th class="text-end">Profit</th><th>IMEI / serial</th></tr></thead>
                    <tbody>
                    @foreach ($lines as $l)
                        <tr wire:key="il-{{ $l->id }}">
                            <td><a class="font-medium text-brand-600 hover:underline" href="{{ route('biz.sale', $l->invoice_id) }}" wire:navigate>{{ $l->invoice->number }}</a><div class="text-xs text-slate-500">{{ $l->invoice->buyer() }} · {{ $l->invoice->date->format('j M Y') }}</div></td>
                            <td class="text-end">{{ number_format($l->quantity) }}</td>
                            <td class="text-end">{{ rs($l->unit_price) }}</td>
                            <td class="text-end">{{ rs($l->total) }}</td>
                            <td class="text-end">{{ rs($l->cost) }}</td>
                            <td @class(['text-end font-semibold whitespace-nowrap', 'text-emerald-700' => $l->total >= $l->cost, 'text-red-700' => $l->total < $l->cost])>{{ rs($l->total - $l->cost) }}</td>
                            <td class="max-w-xs truncate font-mono text-xs" title="{{ $l->details }}">{{ $l->details }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <div class="card overflow-x-auto xl:col-span-2">
            <div class="border-b border-slate-200 px-4 py-3 text-sm font-semibold">Stock received</div>
            <table class="tbl">
                <thead><tr><th>Purchase</th><th>Date</th><th>Supplier</th><th class="text-end">Pieces</th><th class="text-end">Cost each</th><th class="text-end">Total</th><th>Paid by</th></tr></thead>
                <tbody>
                @forelse ($purchases as $po)
                    <tr wire:key="po-{{ $po->id }}">
                        <td class="font-mono text-xs">{{ $po->number }}</td>
                        <td>{{ $po->date->format('j M Y') }}</td>
                        <td>{{ $po->supplier }}</td>
                        <td class="text-end">{{ $po->quantity }}</td>
                        <td class="text-end">{{ rs($po->unit_cost) }}</td>
                        <td class="text-end font-medium">{{ rs($po->total()) }}</td>
                        <td class="text-xs">{{ $po->paymentLabel() }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="p-6 text-center text-slate-500">No stock received yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    @if ($editUnit)
        <x-modal title="Details of this piece" close="$set('editUnit', null)" size="sm">
            <form wire:submit="saveUnit" class="space-y-3">
                @foreach ($p->fields() as $f)
                    <x-field :label="$f['label']" :name="'unitDetails.'.$f['key']"><input class="input" wire:model="unitDetails.{{ $f['key'] }}"></x-field>
                @endforeach
                <div class="flex justify-end gap-2"><button type="button" class="btn" wire:click="$set('editUnit', null)">Cancel</button><button class="btn btn-primary">Save</button></div>
            </form>
        </x-modal>
    @endif
</div>
