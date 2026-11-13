<div>
    {{-- One screen: products on the left, customer, bill and "what will change" on the right. --}}
    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
        <h1 class="text-lg font-semibold text-slate-900">New sale <span class="font-normal text-slate-500">· Nayi sale (cash, bank, udhaar)</span></h1>
        <div class="flex flex-wrap items-center gap-2">
            <div class="flex rounded-lg border border-slate-300 bg-white p-0.5 text-sm">
                @foreach (['retail' => 'Retail', 'wholesale' => 'Wholesale'] as $key => $label)
                    <button type="button" wire:click="$set('priceList', '{{ $key }}')" @class(['rounded-md px-3 py-1', 'bg-brand-600 text-white' => $priceList === $key, 'text-slate-600' => $priceList !== $key])>{{ $label }}</button>
                @endforeach
            </div>
            <x-biz.guide key="sale" compact />
            <a class="btn btn-sm" href="{{ route('biz.sales') }}" wire:navigate>All sales</a>
        </div>
    </div>

    @if ($order)
        <p class="-mt-1 mb-2 rounded-lg bg-sky-50 px-3 py-1.5 text-sm text-sky-900 ring-1 ring-sky-200">Making the sale from order <strong>{{ $order->number }}</strong>{{ $order->salesman ? ' by '.$order->salesman->name : '' }}. Check the pieces and what was paid, then save. The order is then marked delivered.</p>
    @endif
    <form wire:submit="save" class="one-screen grid gap-3 xl:grid-cols-[minmax(0,1fr)_22rem]">
        <section class="card-pad flex flex-col">
            <div class="relative">
                <input class="input py-2!" type="search" placeholder="Search products by name, brand, variant or code · Product dhoondein" wire:model.live.debounce.250ms="productSearch" aria-label="Find product" autofocus>
                @if ($matches->isNotEmpty())
                    <ul class="absolute inset-x-0 top-full z-20 mt-1 max-h-72 divide-y divide-slate-100 overflow-y-auto rounded-xl border border-slate-200 bg-white shadow-lg">
                        @foreach ($matches as $m)
                            <li><button type="button" class="flex w-full items-center justify-between gap-2 px-3 py-2 text-start hover:bg-slate-50" wire:click="addProduct({{ $m->id }})" @disabled(! $m->in_stock)>
                                <span><span class="font-medium">{{ $m->label() }}</span> <span class="text-xs text-slate-500">{{ rs($m->sale_price) }}{{ $m->wholesale_price ? ' · wholesale '.rs($m->wholesale_price) : '' }}</span></span>
                                <span @class(['badge', 'badge-green' => $m->in_stock, 'badge-red' => ! $m->in_stock])>{{ number_format($m->in_stock) }} in stock</span>
                            </button></li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <div class="mt-3 grid grid-cols-[minmax(0,1fr)_4.5rem_7rem_6.5rem_2rem] gap-2 border-b border-slate-200 pb-1 text-xs font-semibold text-slate-500 uppercase">
                <span>Product</span><span>Qty</span><span>Price each</span><span class="text-end">Amount</span><span></span>
            </div>
            <div class="min-h-0 flex-1 divide-y divide-slate-100 overflow-y-auto">
                @forelse ($lines as $i => $line)
                    @php($idField = $identifiers[$line['product_id']] ?? null)
                    <div class="py-2" wire:key="ln-{{ $i }}-{{ $line['product_id'] }}">
                        <div class="grid grid-cols-[minmax(0,1fr)_4.5rem_7rem_6.5rem_2rem] items-center gap-2">
                            <div class="min-w-0"><div class="truncate font-medium">{{ $line['label'] }}</div><div class="text-xs text-slate-500">{{ number_format($stock[$line['product_id']] ?? 0) }} in stock</div></div>
                            <input type="number" min="1" class="input" wire:model.live.debounce.400ms="lines.{{ $i }}.quantity" aria-label="Quantity">
                            <input type="number" min="0" class="input" wire:model.live.debounce.400ms="lines.{{ $i }}.unit_price" aria-label="Price each">
                            <div class="text-end font-semibold">{{ rs((int) $line['quantity'] * (int) $line['unit_price']) }}</div>
                            <button type="button" class="btn btn-sm px-1.5" wire:click="removeLine({{ $i }})" aria-label="Remove"><x-icon name="x-circle" class="h-3.5 w-3.5 text-red-600" /></button>
                        </div>
                        @if ($idField)
                            <textarea class="input mt-1.5 font-mono text-xs" rows="1" placeholder="Scan or type the {{ $idField['label'] }} (optional, one per line). Empty = oldest pieces first." wire:model.live.debounce.500ms="lines.{{ $i }}.imeis" aria-label="{{ $idField['label'] }}"></textarea>
                        @endif
                        @error('lines.'.$i.'.quantity')<p class="err">{{ $message }}</p>@enderror
                        @error('lines.'.$i.'.unit_price')<p class="err">{{ $message }}</p>@enderror
                        @error('lines.'.$i.'.imeis')<p class="err">{{ $message }}</p>@enderror
                    </div>
                @empty
                    <p class="m-3 rounded-xl border border-dashed border-slate-300 p-8 text-center text-sm text-slate-500">Search above and click a product to add it. · Upar product dhoond kar click karein.</p>
                @endforelse
            </div>
            @error('lines')<p class="err">{{ $message }}</p>@enderror

            {{-- The bill, under the products. --}}
            <div class="mt-2 grid grid-cols-2 gap-2 border-t border-slate-200 pt-2.5 sm:grid-cols-[1fr_1fr_1fr_minmax(0,1.4fr)]">
                <x-field label="Discount (Rs)" name="discount"><input id="discount" type="number" min="0" class="input" wire:model.live.debounce.400ms="discount"></x-field>
                <x-field label="Paid now (Rs)" name="paid"><input id="paid" type="number" min="0" class="input" wire:model.live.debounce.400ms="paid" placeholder="{{ $t['total'] }}"></x-field>
                <x-field label="Paid by" name="method"><select id="method" class="input" wire:model.live="method">@foreach (\App\Models\Biz\Receipt::METHODS as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></x-field>
                <div class="rounded-xl bg-brand-50 px-3 py-1.5 ring-1 ring-brand-100">
                    <div class="flex justify-between text-xs text-slate-600"><span>Subtotal {{ rs($t['subtotal']) }}</span><span>{{ $t['paid'] < $t['total'] ? 'Udhaar '.rs($t['total'] - $t['paid']) : 'Paid in full' }}</span></div>
                    <div class="flex justify-between text-lg font-bold text-brand-800"><span>Total</span><span>{{ rs($t['total']) }}</span></div>
                </div>
            </div>
        </section>

        <aside class="card-pad flex flex-col gap-2.5">
            {{-- Customer --}}
            <div>
                <div class="label">Customer · Gahak</div>
                @if ($customer)
                    <div class="flex items-center justify-between gap-2 rounded-lg border border-slate-200 px-3 py-1.5">
                        <div class="min-w-0"><div class="truncate font-semibold">{{ $customer->name }}</div><div class="text-xs text-slate-500">{{ $customer->phone }}</div></div>
                        <button type="button" class="btn btn-sm" wire:click="$set('customerId', null)">Change</button>
                    </div>
                @else
                    <div class="relative">
                        <input class="input" type="search" placeholder="Saved customer: name, phone, CNIC" wire:model.live.debounce.300ms="customerSearch" aria-label="Find customer">
                        @if ($customerMatches->isNotEmpty())
                            <ul class="absolute inset-x-0 top-full z-20 mt-1 rounded-xl border border-slate-200 bg-white shadow-lg">
                                @foreach ($customerMatches as $m)
                                    <li><button type="button" class="block w-full px-3 py-1.5 text-start text-sm hover:bg-slate-50" wire:click="pickCustomer({{ $m->id }})">{{ $m->name }} <span class="text-xs text-slate-500">{{ $m->phone }}</span></button></li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                    <input class="input mt-1.5" placeholder="Or walk-in buyer's name (pays in full)" wire:model="walkIn" aria-label="Walk-in buyer">
                @endif
                @error('customerId')<p class="err">{{ $message }}</p>@enderror
            </div>

            <div class="grid grid-cols-2 gap-2">
                <x-field label="Salesman" name="salesmanId">
                    <select id="salesmanId" class="input" wire:model="salesmanId"><option value="">—</option>@foreach ($salesmen as $sm)<option value="{{ $sm->id }}">{{ $sm->name }}</option>@endforeach</select>
                </x-field>
                <x-field label="Date" name="date"><input id="date" type="date" class="input" wire:model.live="date"></x-field>
                <x-field label="Note" name="notes" class="col-span-2"><input id="notes" class="input" wire:model="notes"></x-field>
            </div>

            <x-biz.impact :impact="$impact" compact />

            <button class="btn btn-primary sticky bottom-0 mt-auto w-full py-2.5 text-base" wire:loading.attr="disabled" wire:target="save" @disabled(! $lines)><x-icon name="check-circle" class="h-5 w-5" /> Save sale · Save karein</button>
        </aside>
    </form>
</div>
