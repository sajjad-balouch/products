<div>
    <x-page-header :title="'Sale '.$inv->number" :subtitle="$inv->buyer().' · '.$inv->date->format('j M Y').($inv->creator ? ' · by '.$inv->creator->name : '')">
        <a class="btn" href="{{ route('biz.sales') }}" wire:navigate>All sales</a>
        <a class="btn" href="{{ route('biz.print.invoice', $inv) }}" target="_blank"><x-icon name="file-plus" class="h-4 w-4" /> Print</a>
        @if (! $inv->voided_at && $inv->balance())<button class="btn btn-primary" wire:click="openPay"><x-icon name="cash" class="h-4 w-4" /> Receive udhaar</button>@endif
    </x-page-header>

    @if ($inv->voided_at)
        <div class="mb-4 rounded-xl bg-red-50 p-3 text-sm text-red-800 ring-1 ring-red-200">Cancelled {{ $inv->voided_at->format('j M Y') }}: {{ $inv->void_reason }}. Its entries were reversed and the pieces went back into stock.</div>
    @endif

    <div class="mb-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat icon="receipt" tone="blue" label="Total" :value="rs($inv->total)" :note="$inv->discount ? 'after discount '.rs($inv->discount) : null" />
        <x-stat icon="cash" tone="green" label="Paid" :value="rs($inv->paid)" />
        <x-stat icon="wallet" :tone="$inv->balance() ? 'amber' : 'green'" label="Udhaar left" :value="rs($inv->balance())" />
        <x-stat icon="trend" :tone="$inv->profit() >= 0 ? 'green' : 'red'" label="Profit" :value="rs($inv->profit())" :note="'Cost of pieces '.rs($inv->cost)" />
    </div>

    <div class="grid gap-4 xl:grid-cols-3">
        <div class="card overflow-x-auto xl:col-span-2">
            <table class="tbl">
                <thead><tr><th>Product</th><th class="text-end">Qty</th><th class="text-end">Price</th><th class="text-end">Amount</th><th class="text-end">Cost</th><th class="text-end">Profit</th></tr></thead>
                <tbody>
                @foreach ($inv->lines as $l)
                    <tr wire:key="l-{{ $l->id }}">
                        <td><a class="font-medium text-brand-600 hover:underline" href="{{ route('biz.product', $l->product_id) }}" wire:navigate>{{ $l->product->label() }}</a>@if ($l->details)<div class="font-mono text-xs break-all text-slate-500">{{ $l->details }}</div>@endif</td>
                        <td class="text-end">{{ number_format($l->quantity) }}</td>
                        <td class="text-end">{{ number_format($l->unit_price) }}</td>
                        <td class="text-end">{{ number_format($l->total) }}</td>
                        <td class="text-end">{{ number_format($l->cost) }}</td>
                        <td class="text-end font-semibold">{{ number_format($l->total - $l->cost) }}</td>
                    </tr>
                @endforeach
                </tbody>
                <tfoot>
                    @if ($inv->discount)<tr><td colspan="3">Discount</td><td class="text-end">− {{ number_format($inv->discount) }}</td><td colspan="2"></td></tr>@endif
                    <tr class="font-semibold"><td colspan="3">Total</td><td class="text-end">{{ rs($inv->total) }}</td><td class="text-end">{{ number_format($inv->cost) }}</td><td class="text-end">{{ rs($inv->profit()) }}</td></tr>
                </tfoot>
            </table>
        </div>
        <div class="space-y-4">
            <div class="card-pad text-sm">
                <h2 class="panel-title"><x-icon name="users" class="h-5 w-5 text-brand-600" /> Customer</h2>
                @if ($inv->customer)
                    <a class="mt-2 block font-semibold text-brand-600 hover:underline" href="{{ route('biz.customer', $inv->customer) }}" wire:navigate>{{ $inv->customer->name }}</a>
                    <div class="text-slate-600">{{ $inv->customer->phone }}</div>
                @else
                    <div class="mt-2">{{ $inv->buyer() }}</div>
                @endif
                @if ($inv->notes)<p class="mt-2 text-slate-600">{{ $inv->notes }}</p>@endif
            </div>
            <div class="card-pad text-sm">
                <h2 class="panel-title"><x-icon name="cash" class="h-5 w-5 text-emerald-600" /> Payments</h2>
                <ul class="mt-2 divide-y divide-slate-100">
                    @forelse ($inv->payments as $p)
                        <li class="flex justify-between py-2"><span>{{ $p->date->format('j M Y') }} · {{ \App\Models\Biz\Receipt::METHODS[$p->method] ?? $p->method }}<span class="block text-xs text-slate-500">{{ collect([$p->receiver?->name, $p->reference])->filter()->implode(' · ') }}</span></span><span class="font-semibold">{{ rs($p->amount) }}</span></li>
                    @empty
                        <li class="py-2 text-slate-500">Nothing paid yet.</li>
                    @endforelse
                </ul>
            </div>
            @if ($isAdmin && ! $inv->voided_at)
                <button class="text-xs text-red-600 hover:underline" wire:click="$set('showVoid', true)">Cancel this sale…</button>
            @endif
        </div>
    </div>

    @if ($showPay)
        <x-modal title="Receive udhaar" close="$set('showPay', false)" size="sm">
            <form wire:submit="receive" class="space-y-3">
                <p class="text-sm text-slate-600">{{ rs($inv->balance()) }} still owed on {{ $inv->number }}.</p>
                <x-field label="Amount (Rs)" name="amount"><input id="amount" type="number" min="1" class="input" wire:model.live.debounce.400ms="amount"></x-field>
                <div class="grid grid-cols-2 gap-3">
                    <x-field label="Date" name="date"><input id="date" type="date" class="input" wire:model.live="date"></x-field>
                    <x-field label="Paid by" name="method"><select id="method" class="input" wire:model.live="method">@foreach (\App\Models\Biz\Receipt::METHODS as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></x-field>
                </div>
                <x-field label="Reference" name="reference"><input id="reference" class="input" wire:model="reference"></x-field>
                <x-biz.impact :impact="$impact" />
                <x-biz.guide key="sale_receive" />
                <div class="flex justify-end gap-2"><button type="button" class="btn" wire:click="$set('showPay', false)">Cancel</button><button class="btn btn-primary">Save payment</button></div>
            </form>
        </x-modal>
    @endif

    @if ($showVoid)
        <x-modal title="Cancel this sale" close="$set('showVoid', false)" size="sm">
            <form wire:submit="cancel" class="space-y-3">
                <p class="text-sm text-slate-600">Its accounting entries are reversed and every piece goes back into stock. The bill stays on record, marked cancelled.</p>
                <x-field label="Reason" name="voidReason"><input id="voidReason" class="input" wire:model="voidReason" placeholder="Returned, wrong entry…"></x-field>
                <x-biz.impact :impact="$impact" />
                <x-biz.guide key="sale_cancel" />
                <div class="flex justify-end gap-2"><button type="button" class="btn" wire:click="$set('showVoid', false)">Keep it</button><button class="btn btn-danger">Cancel sale</button></div>
            </form>
        </x-modal>
    @endif
</div>
