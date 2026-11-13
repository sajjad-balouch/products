<div>
    {{-- One screen in three columns: customer, item and plan, then the plan itself with "what will change". --}}
    <div class="mb-3 flex flex-wrap items-center justify-between gap-2">
        <h1 class="text-lg font-semibold text-slate-900">New installment sale <span class="font-normal text-slate-500">· Qiston par sale</span></h1>
        <div class="flex items-center gap-2">
            <x-biz.guide key="contract" compact />
            <a class="btn btn-sm" href="{{ route('biz.contracts') }}" wire:navigate>All plans</a>
        </div>
    </div>

    {{-- Leaving any box refreshes the "what saving will change" preview. --}}
    <form wire:submit="save" x-on:change="$wire.$refresh()" class="one-screen grid gap-3 xl:grid-cols-[minmax(0,0.9fr)_minmax(0,1.2fr)_21rem]">
        {{-- 1. Customer --}}
        <section class="card-pad flex flex-col gap-2.5">
            <h2 class="panel-title text-sm"><span class="icon-chip tone-blue h-6! w-6! text-xs font-bold">1</span> Customer · Gahak</h2>
            @if ($customer && ! $newCustomer)
                <div class="rounded-lg border border-slate-200 px-3 py-2">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <div class="truncate font-semibold">{{ $customer->name }} <span class="text-xs font-normal text-slate-500">{{ $customer->code }}</span></div>
                            <div class="text-xs text-slate-500">{{ $customer->phone }}{{ $customer->cnic ? ' · '.$customer->cnic : '' }}</div>
                        </div>
                        <button type="button" class="btn btn-sm" wire:click="$set('customerId', null)">Change</button>
                    </div>
                    @if ($customer->contracts->isNotEmpty())
                        <div class="mt-1 text-xs text-amber-700">Already has {{ $customer->contracts->count() }} running {{ str('plan')->plural($customer->contracts->count()) }}, owing {{ rs($customer->contracts->sum(fn ($c) => $c->outstanding())) }}.</div>
                    @endif
                </div>
                @if ($customer->is_blacklisted)
                    <label class="flex items-center gap-2 rounded-lg bg-red-50 p-2 text-xs text-red-800"><input type="checkbox" wire:model="confirmBlacklisted"> Blacklisted. Sell anyway.</label>
                @endif
            @elseif ($newCustomer)
                <div class="grid grid-cols-2 gap-2">
                    <x-field label="Full name" name="form.name" class="col-span-2"><input id="form.name" class="input" wire:model="form.name"></x-field>
                    <x-field label="Father / husband" name="form.father_name"><input id="form.father_name" class="input" wire:model="form.father_name"></x-field>
                    <x-field label="Mobile" name="form.phone"><input id="form.phone" class="input" type="tel" wire:model="form.phone" placeholder="0300-1234567"></x-field>
                    <x-field label="CNIC" name="form.cnic"><input id="form.cnic" class="input" wire:model="form.cnic" placeholder="36302-1234567-1" inputmode="numeric"></x-field>
                    <x-field label="Second phone" name="form.phone2"><input id="form.phone2" class="input" type="tel" wire:model="form.phone2"></x-field>
                    <x-field label="Home address" name="form.address" class="col-span-2"><input id="form.address" class="input" wire:model="form.address"></x-field>
                </div>
                <button type="button" class="self-start text-xs text-brand-600 hover:underline" wire:click="$set('newCustomer', false)">Pick an existing customer instead</button>
            @else
                <div class="relative">
                    <input class="input" type="search" placeholder="Search name, phone or CNIC" wire:model.live.debounce.300ms="customerSearch" aria-label="Find customer">
                    @if ($matches->isNotEmpty())
                        <ul class="absolute inset-x-0 top-full z-20 mt-1 divide-y divide-slate-100 rounded-xl border border-slate-200 bg-white shadow-lg">
                            @foreach ($matches as $match)
                                <li><button type="button" class="flex w-full items-center justify-between gap-2 px-3 py-1.5 text-start text-sm hover:bg-slate-50" wire:click="pickCustomer({{ $match->id }})">
                                    <span><span class="font-medium">{{ $match->name }}</span> <span class="text-xs text-slate-500">{{ $match->phone }}</span></span>
                                    @if ($match->is_blacklisted)<span class="badge badge-red">Blacklisted</span>@endif
                                </button></li>
                            @endforeach
                        </ul>
                    @elseif (strlen(trim($customerSearch)) >= 2)
                        <p class="mt-1 text-xs text-slate-500">No customer found. Add them as new.</p>
                    @endif
                </div>
                <button type="button" class="btn btn-sm self-start" wire:click="$set('newCustomer', true)"><x-icon name="user-plus" class="h-3.5 w-3.5" /> New customer</button>
            @endif
            @error('customerId')<p class="err">{{ $message }}</p>@enderror

            @php($gCount = collect($guarantors)->filter(fn ($g) => trim($g['name'] ?? '') !== '')->count())
            <div class="mt-auto space-y-2 border-t border-slate-100 pt-2.5">
                <button type="button" wire:click="$set('showGuarantors', true)" @class(['btn w-full justify-between', 'border-red-300 text-red-700' => $errors->has('guarantors.*')])>
                    <span class="flex items-center gap-1.5"><x-icon name="users" class="h-4 w-4" /> Guarantors · Zamin</span>
                    <span class="badge {{ $gCount ? 'badge-green' : 'badge-slate' }}">{{ $gCount }} / 2</span>
                </button>
                <x-field label="Salesman" name="sale.salesman_id">
                    <select id="sale.salesman_id" class="input" wire:model="sale.salesman_id"><option value="">—</option>@foreach ($salesmen as $sm)<option value="{{ $sm->id }}">{{ $sm->name }}</option>@endforeach</select>
                </x-field>
            </div>
        </section>

        {{-- 2. Item and plan --}}
        <section class="card-pad flex flex-col gap-2.5">
            <h2 class="panel-title text-sm"><span class="icon-chip tone-violet h-6! w-6! text-xs font-bold">2</span> Item and plan · Cheez aur qistein</h2>
            @if ($product)
                <div class="flex items-center justify-between gap-2 rounded-lg border border-slate-200 px-3 py-1.5">
                    <div class="min-w-0"><div class="truncate font-semibold">{{ $product->label() }}</div><div class="text-xs text-slate-500">{{ rs($product->sale_price) }} · {{ $pieces->count() }} in stock</div></div>
                    <button type="button" class="btn btn-sm" wire:click="clearProduct">Change</button>
                </div>
                @if ($pieces->isNotEmpty())
                    <select id="stockUnitId" class="input" wire:model.live="stockUnitId" aria-label="Which piece">
                        @foreach ($pieces as $piece)
                            <option value="{{ $piece->id }}">Piece: cost {{ rs($piece->unit_cost) }} · {{ $piece->purchase->date->format('j M Y') }}{{ ($s = $piece->summary($product->fields())) ? ' · '.$s : '' }}</option>
                        @endforeach
                    </select>
                @else
                    <p class="rounded-lg bg-amber-50 px-2.5 py-1.5 text-xs text-amber-900 ring-1 ring-amber-200">Not in stock. Type its cost below, or <a class="font-medium underline" href="{{ route('biz.products') }}" wire:navigate>add stock first</a>.</p>
                @endif
                @if ($product->fields())
                    {{-- Filled from the stock record; open only to check or change. --}}
                    <details class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs" @if ($errors->has('details.*')) open @endif>
                        <summary class="cursor-pointer truncate text-slate-600">Piece details · {{ collect($product->fields())->map(fn ($f) => ($details[$f['key']] ?? '') !== '' ? $f['label'].' '.$details[$f['key']] : null)->filter()->implode(' · ') ?: 'none yet' }}</summary>
                        <div class="mt-2 grid grid-cols-3 gap-2">
                            @foreach ($product->fields() as $f)
                                <x-field :label="$f['label']" :name="'details.'.$f['key']"><input class="input" wire:model="details.{{ $f['key'] }}"></x-field>
                            @endforeach
                        </div>
                    </details>
                @endif
            @else
                <div class="relative">
                    <input class="input" type="search" placeholder="Search your products: name, brand or code" wire:model.live.debounce.250ms="productSearch" aria-label="Find product">
                    @if ($productMatches->isNotEmpty())
                        <ul class="absolute inset-x-0 top-full z-20 mt-1 divide-y divide-slate-100 rounded-xl border border-slate-200 bg-white shadow-lg">
                            @foreach ($productMatches as $match)
                                <li><button type="button" class="flex w-full items-center justify-between gap-2 px-3 py-1.5 text-start text-sm hover:bg-slate-50" wire:click="pickProduct({{ $match->id }})">
                                    <span><span class="font-medium">{{ $match->label() }}</span> <span class="text-xs text-slate-500">{{ rs($match->sale_price) }}</span></span>
                                    <span @class(['badge', 'badge-green' => $match->in_stock, 'badge-red' => ! $match->in_stock])>{{ $match->in_stock }} in stock</span>
                                </button></li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            @endif

            <div class="grid grid-cols-3 gap-2">
                <x-field label="Item (prints on agreement)" name="sale.item" class="col-span-2"><input id="sale.item" class="input" wire:model="sale.item"></x-field>
                <x-field label="Sale date" name="sale.sale_date"><input id="sale.sale_date" type="date" class="input" wire:model.live="sale.sale_date"></x-field>
                <x-field label="Cash price (Rs)" name="sale.cash_price"><input id="sale.cash_price" type="number" min="1" class="input" wire:model.live.debounce.400ms="sale.cash_price"></x-field>
                <x-field :label="$product && $stockUnitId ? 'Cost of this piece' : 'Your cost (optional)'" name="sale.cost_price"><input id="sale.cost_price" type="number" min="0" @class(['input', 'bg-slate-50' => $product && $stockUnitId]) wire:model="sale.cost_price" @readonly($product && $stockUnitId)></x-field>
                <x-field label="Down payment" name="sale.down_payment"><input id="sale.down_payment" type="number" min="0" class="input" wire:model.live.debounce.400ms="sale.down_payment"></x-field>
                <x-field label="Markup %" name="sale.markup_percent"><input id="sale.markup_percent" type="number" step="0.01" min="0" class="input" wire:model.live.debounce.400ms="sale.markup_percent"></x-field>
                <x-field label="Or markup Rs" name="sale.markup"><input id="sale.markup" type="number" min="0" class="input" wire:model.live.debounce.400ms="sale.markup"></x-field>
                <x-field label="Installments" name="sale.installments"><input id="sale.installments" type="number" min="1" max="120" class="input" wire:model.live.debounce.400ms="sale.installments"></x-field>
                <x-field label="Paid every" name="sale.frequency"><select id="sale.frequency" class="input" wire:model.live="sale.frequency">@foreach (\App\Models\Biz\Contract::FREQUENCIES as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></x-field>
                <x-field label="First due" name="sale.first_due_on"><input id="sale.first_due_on" type="date" class="input" wire:model.live="sale.first_due_on"></x-field>
                <x-field label="Down payment by" name="sale.method"><select id="sale.method" class="input" wire:model.live="sale.method">@foreach (\App\Models\Biz\Receipt::METHODS as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></x-field>
            </div>
            {{-- Settings that rarely change; they start from the business settings. --}}
            <details class="rounded-lg border border-slate-200 px-2.5 py-1.5 text-xs" @if ($errors->hasAny(['sale.late_fee', 'sale.grace_days', 'sale.rounding', 'sale.item_details'])) open @endif>
                <summary class="cursor-pointer text-slate-600">More settings · Late fee {{ rs((int) $sale['late_fee']) }} · {{ (int) $sale['grace_days'] }} grace days · round to {{ $sale['rounding'] }}</summary>
                <div class="mt-2 grid grid-cols-3 gap-2">
                    <x-field label="Late fee per late inst." name="sale.late_fee"><input id="sale.late_fee" type="number" min="0" class="input" wire:model="sale.late_fee"></x-field>
                    <x-field label="Grace days" name="sale.grace_days"><input id="sale.grace_days" type="number" min="0" max="60" class="input" wire:model="sale.grace_days"></x-field>
                    <x-field label="Round to" name="sale.rounding"><select id="sale.rounding" class="input" wire:model.live="sale.rounding">@foreach ([1 => 'Exact', 10 => 'Nearest 10', 50 => 'Nearest 50', 100 => 'Nearest 100'] as $r => $label)<option value="{{ $r }}">{{ $label }}</option>@endforeach</select></x-field>
                    <x-field label="Other details to print on the agreement" name="sale.item_details" class="col-span-3"><input id="sale.item_details" class="input" wire:model="sale.item_details"></x-field>
                </div>
            </details>
            @if ((int) $sale['cost_price'] > 0 && (int) $sale['cash_price'] > 0)
                @php($margin = (int) $sale['cash_price'] - (int) $sale['cost_price'])
                <p @class(['text-xs', 'text-emerald-700' => $margin >= 0, 'text-red-700' => $margin < 0])>
                    {{ $margin >= 0 ? 'Profit on the cash price' : 'Loss on the cash price' }}: {{ rs(abs($margin)) }}{{ $plan ? ', plus markup '.rs($plan['markup']).' = '.rs($plan['total'] - (int) $sale['cost_price']).' in all' : '' }}
                </p>
            @endif
        </section>

        {{-- 3. The plan --}}
        <aside class="card-pad flex flex-col gap-2.5">
            <h2 class="panel-title text-sm"><x-icon name="calendar" class="h-4 w-4 text-brand-600" /> The plan · Plan</h2>
            @if ($plan)
                <dl class="space-y-0.5 text-sm">
                    <div class="flex justify-between"><dt class="text-slate-500">Cash price</dt><dd>{{ rs((int) $sale['cash_price']) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Markup</dt><dd>+ {{ rs($plan['markup']) }}</dd></div>
                    <div class="flex justify-between font-semibold"><dt>Total price</dt><dd>{{ rs($plan['total']) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Down payment today</dt><dd>− {{ rs((int) $sale['down_payment']) }}</dd></div>
                </dl>
                <div class="rounded-xl bg-brand-50 px-3 py-1.5 text-center ring-1 ring-brand-100">
                    <div class="text-xs text-slate-600">{{ count($plan['rows']) }} × {{ strtolower(\App\Models\Biz\Contract::FREQUENCIES[$sale['frequency']] ?? '') }} installment{{ end($plan['rows'])['amount'] !== $plan['installment'] ? ' · last '.rs(end($plan['rows'])['amount']) : '' }}</div>
                    <div class="text-2xl font-bold text-brand-700">{{ rs($plan['installment']) }}</div>
                </div>
                <details class="text-xs">
                    <summary class="cursor-pointer text-brand-700">Schedule · Qiston ki tareekhein ({{ count($plan['rows']) }})</summary>
                    <div class="mt-1 max-h-32 overflow-y-auto rounded-lg border border-slate-100">
                        <table class="w-full">
                            @foreach ($plan['rows'] as $row)
                                <tr class="border-b border-slate-100 last:border-0"><td class="px-2 py-0.5 text-slate-500">{{ $row['number'] }}</td><td class="px-2 py-0.5">{{ $row['due_on']->format('j M Y') }}</td><td class="px-2 py-0.5 text-end font-medium">{{ rs($row['amount']) }}</td></tr>
                            @endforeach
                        </table>
                    </div>
                </details>
            @else
                <p class="text-sm text-slate-500">Enter the cash price and the number of installments to see the plan.</p>
            @endif
            @error('form')<p class="err">{{ $message }}</p>@enderror

            <x-biz.impact :impact="$impact" compact />

            <button class="btn btn-primary sticky bottom-0 mt-auto w-full py-2.5 text-base" wire:loading.attr="disabled" wire:target="save"><x-icon name="check-circle" class="h-5 w-5" /> Save sale · Save karein</button>
        </aside>
    </form>

    @if ($showGuarantors)
        <x-modal title="Guarantors · Zamin" close="$set('showGuarantors', false)" size="lg">
            <div class="grid gap-4 lg:grid-cols-2">
                @foreach ($guarantors as $i => $g)
                    <fieldset class="space-y-2 rounded-xl border border-slate-200 p-3" wire:key="g-{{ $i }}">
                        <legend class="px-1 text-sm font-semibold">Guarantor {{ $i + 1 }}</legend>
                        <div class="grid gap-2 sm:grid-cols-2">
                            <x-field label="Name" name="guarantors.{{ $i }}.name"><input class="input" wire:model="guarantors.{{ $i }}.name"></x-field>
                            <x-field label="Father's name" name="guarantors.{{ $i }}.father_name"><input class="input" wire:model="guarantors.{{ $i }}.father_name"></x-field>
                            <x-field label="CNIC" name="guarantors.{{ $i }}.cnic"><input class="input" wire:model="guarantors.{{ $i }}.cnic" inputmode="numeric"></x-field>
                            <x-field label="Phone" name="guarantors.{{ $i }}.phone"><input class="input" type="tel" wire:model="guarantors.{{ $i }}.phone"></x-field>
                            <x-field label="Relation to customer" name="guarantors.{{ $i }}.relation"><input class="input" wire:model="guarantors.{{ $i }}.relation"></x-field>
                            <x-field label="Address" name="guarantors.{{ $i }}.address"><input class="input" wire:model="guarantors.{{ $i }}.address"></x-field>
                        </div>
                    </fieldset>
                @endforeach
            </div>
            <div class="mt-4 flex justify-end"><button type="button" class="btn btn-primary" wire:click="$set('showGuarantors', false)">Done · Theek hai</button></div>
        </x-modal>
    @endif
</div>
