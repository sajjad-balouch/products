<div wire:poll.30s>
    <x-page-header title="Orders from salesmen" subtitle="Orders taken on the sales app, even offline. Accept them, then make the sale. · Salesmen ke orders.">
        <a class="btn" href="{{ route('biz.employees') }}" wire:navigate><x-icon name="user-cog" class="h-4 w-4" /> Give a salesman the app</a>
        <a class="btn btn-primary" href="{{ route('app') }}" target="_blank"><x-icon name="device" class="h-4 w-4" /> Open the sales app</a>
    </x-page-header>

    <div class="mb-4 flex flex-wrap gap-2">
        @foreach (['open' => ['Open', ($counts['new'] ?? 0) + ($counts['accepted'] ?? 0)], 'new' => ['New', $counts['new'] ?? 0], 'accepted' => ['Accepted', $counts['accepted'] ?? 0], 'delivered' => ['Sale made', $counts['delivered'] ?? 0], 'cancelled' => ['Cancelled', $counts['cancelled'] ?? 0], 'all' => ['All', $counts->sum()]] as $key => [$label, $n])
            <button wire:click="$set('status', '{{ $key }}')" @class(['rounded-full px-3.5 py-1.5 text-sm font-medium ring-1', 'bg-brand-600 text-white ring-brand-600' => $status === $key, 'bg-white text-slate-600 ring-slate-200' => $status !== $key])>{{ $label }} <span class="opacity-75">{{ $n }}</span></button>
        @endforeach
    </div>

    <div class="card overflow-x-auto">
        <table class="tbl">
            <thead><tr><th>Order</th><th>Customer</th><th>Salesman</th><th>Taken</th><th class="text-end">Total</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse ($orders as $o)
                <tr wire:key="o-{{ $o->id }}" @class(['bg-sky-50/60' => ! $o->seen_at])>
                    <td><span class="font-mono text-xs font-semibold">{{ $o->number }}</span><div class="text-xs text-slate-500">{{ $o->lines_count }} {{ str('item')->plural($o->lines_count) }} · {{ ucfirst($o->price_list) }}</div></td>
                    <td>{{ $o->customer?->name ?? '—' }}<div class="text-xs text-slate-500">{{ $o->customer?->phone }}</div></td>
                    <td>{{ $o->salesman?->name ?? $o->user?->name ?? '—' }}</td>
                    <td class="text-sm whitespace-nowrap">{{ $o->ordered_at->format('j M, g:i a') }}</td>
                    <td class="text-end font-semibold">{{ rs($o->total) }}</td>
                    <td><span @class(['badge', 'badge-blue' => $o->status === 'new', 'badge-amber' => $o->status === 'accepted', 'badge-green' => $o->status === 'delivered', 'badge-slate' => $o->status === 'cancelled'])>{{ \App\Models\Biz\Order::STATUSES[$o->status] }}</span></td>
                    <td class="text-end whitespace-nowrap">
                        <button class="btn btn-sm" wire:click="open({{ $o->id }})">Open</button>
                        @if ($o->isOpen())<a class="btn btn-sm btn-primary" href="{{ route('biz.sales.new', ['order' => $o->id]) }}" wire:navigate>Make sale</a>@endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="p-8 text-center text-slate-500">No orders here. Orders from the sales app show up here by themselves.</td></tr>
            @endforelse
            </tbody>
        </table>
        <div class="p-3">{{ $orders->links() }}</div>
    </div>

    @if ($order)
        <x-modal :title="'Order '.$order->number" close="$set('openId', null)" size="lg">
            <div class="space-y-3">
                <div class="grid gap-3 text-sm sm:grid-cols-3">
                    <div><div class="text-xs text-slate-500">Customer</div><div class="font-semibold">{{ $order->customer?->name ?? '—' }}</div><div class="text-slate-500">{{ $order->customer?->phone }} {{ $order->customer?->address ? '· '.$order->customer->address : '' }}</div></div>
                    <div><div class="text-xs text-slate-500">Salesman</div><div class="font-semibold">{{ $order->salesman?->name ?? $order->user?->name ?? '—' }}</div><div class="text-slate-500">{{ $order->ordered_at->format('j M Y, g:i a') }}</div></div>
                    <div><div class="text-xs text-slate-500">Status</div><div class="font-semibold">{{ \App\Models\Biz\Order::STATUSES[$order->status] }}</div>
                        @if ($order->lat && $order->lng)<a class="text-brand-600 underline" href="https://www.google.com/maps?q={{ $order->lat }},{{ $order->lng }}" target="_blank" rel="noopener">Where it was taken (map)</a>@endif
                    </div>
                </div>
                <table class="tbl">
                    <thead><tr><th>Product</th><th class="text-end">Qty</th><th class="text-end">Price</th><th class="text-end">Amount</th></tr></thead>
                    <tbody>
                        @foreach ($order->lines as $l)
                            <tr><td>{{ $l->label }}</td><td class="text-end">{{ number_format($l->quantity) }}</td><td class="text-end">{{ rs($l->unit_price) }}</td><td class="text-end font-medium">{{ rs($l->quantity * $l->unit_price) }}</td></tr>
                        @endforeach
                    </tbody>
                    <tfoot><tr class="font-bold"><td colspan="3">Total ({{ $order->price_list }})</td><td class="text-end">{{ rs($order->total) }}</td></tr></tfoot>
                </table>
                @if ($order->notes)<p class="rounded-lg bg-amber-50 p-2.5 text-sm text-amber-900">Note: {{ $order->notes }}</p>@endif
                @if ($order->invoice)<p class="rounded-lg bg-emerald-50 p-2.5 text-sm text-emerald-900">Sale made: <a class="font-semibold underline" href="{{ route('biz.sale', $order->invoice) }}" wire:navigate>{{ $order->invoice->number }}</a></p>@endif
                @if ($order->cancel_reason)<p class="rounded-lg bg-slate-100 p-2.5 text-sm">Cancelled: {{ $order->cancel_reason }}</p>@endif

                @if ($order->isOpen())
                    <div class="flex flex-wrap items-end justify-between gap-2 border-t border-slate-200 pt-3">
                        <div class="flex items-end gap-2">
                            <x-field label="Cancel because" name="cancelReason"><input id="cancelReason" class="input" wire:model="cancelReason" placeholder="Out of stock, customer said no…"></x-field>
                            <button class="btn btn-danger" wire:click="cancel">Cancel order</button>
                        </div>
                        <div class="flex gap-2">
                            @if ($order->status === 'new')<button class="btn" wire:click="accept">Accept</button>@endif
                            <a class="btn btn-primary" href="{{ route('biz.sales.new', ['order' => $order->id]) }}" wire:navigate><x-icon name="receipt" class="h-4 w-4" /> Make sale</a>
                        </div>
                    </div>
                @endif
            </div>
        </x-modal>
    @endif
</div>
