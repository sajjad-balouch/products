<div>
    <x-page-header title="Products and stock" :subtitle="'Set up for a '.strtolower(\App\Support\BusinessKinds::label($kind)).'. Save each product once with its brand, model and prices, and add stock as it comes in, with each piece\'s IMEI or serial, so every sale shows its real profit.'">
        @switch($tab)
            @case('types')<button class="btn btn-primary" wire:click="openType()"><x-icon name="plus" class="h-4 w-4" /> New product type</button>@break
            @case('categories')<button class="btn btn-primary" wire:click="openCategory()"><x-icon name="plus" class="h-4 w-4" /> New category</button>@break
            @case('brands')<button class="btn btn-primary" wire:click="openBrand()"><x-icon name="plus" class="h-4 w-4" /> New brand</button>@break
            @case('find')@break
            @default<button class="btn btn-primary" wire:click="openProduct()"><x-icon name="plus" class="h-4 w-4" /> New product</button>
        @endswitch
    </x-page-header>

    <nav class="mb-4 flex gap-1 overflow-x-auto border-b border-slate-200 text-sm">
        @foreach (\App\Livewire\Biz\Products::TABS as $key => [$label, $icon])
            <button wire:click="$set('tab', '{{ $key }}')" @class(['-mb-px flex shrink-0 items-center gap-2 border-b-2 px-3 py-2 font-medium', 'border-brand-600 text-brand-700' => $tab === $key, 'border-transparent text-slate-500 hover:text-slate-800' => $tab !== $key])><x-icon :name="$icon" class="h-4 w-4" /> {{ $label }}</button>
        @endforeach
    </nav>

    @if ($tab === 'products')
        <div class="mb-4 grid gap-3 sm:grid-cols-3">
            <x-stat icon="package" tone="blue" label="Products" :value="$products->count()" />
            <x-stat icon="layers" tone="violet" label="Pieces in stock" :value="number_format($products->sum('in_stock'))" />
            <x-stat icon="banknote" tone="brand" label="Stock value (at cost)" :value="rs((int) $products->sum('stock_value'))" />
        </div>
        <div class="card">
            <div class="flex flex-wrap gap-2 border-b border-slate-200 p-3">
                <input class="input max-w-xs" type="search" placeholder="Name, brand, variant or code" wire:model.live.debounce.300ms="search" aria-label="Search products">
                <select class="input w-auto" wire:model.live="categoryFilter" aria-label="Category"><option value="">All categories</option>@foreach ($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select>
                <select class="input w-auto" wire:model.live="typeFilter" aria-label="Type"><option value="">All types</option>@foreach ($types as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach</select>
            </div>
            <div class="overflow-x-auto">
                <table class="tbl">
                    <thead><tr><th>Product</th><th>Category</th><th class="text-end">Retail</th><th class="text-end">Wholesale</th><th class="text-end">In stock</th><th class="text-end">Stock cost</th><th class="text-end">Sold</th><th></th></tr></thead>
                    <tbody>
                    @forelse ($products as $p)
                        <tr wire:key="p-{{ $p->id }}" @class(['opacity-50' => ! $p->is_active])>
                            <td><a class="font-medium text-brand-600 hover:underline" href="{{ route('biz.product', $p) }}" wire:navigate>{{ $p->label() }}</a><div class="text-xs text-slate-500">{{ $p->type?->name }}{{ $p->code ? ' · '.$p->code : '' }}</div></td>
                            <td class="text-xs">{{ $p->category?->name }}</td>
                            <td class="text-end">{{ rs($p->sale_price) }}</td>
                            <td class="text-end">{{ $p->wholesale_price ? rs($p->wholesale_price) : '—' }}</td>
                            <td class="text-end"><span @class(['badge', 'badge-green' => $p->in_stock > 0, 'badge-red' => ! $p->in_stock])>{{ number_format($p->in_stock) }}</span></td>
                            <td class="text-end">{{ $p->in_stock ? rs($p->stock_value) : '—' }}@if ($p->in_stock)<div class="text-xs text-slate-500">avg {{ rs(intdiv((int) $p->stock_value, $p->in_stock)) }}</div>@endif</td>
                            <td class="text-end">{{ number_format($p->sold) }}</td>
                            <td class="text-end whitespace-nowrap">
                                <button class="btn btn-sm btn-primary" wire:click="openStock({{ $p->id }})"><x-icon name="plus" class="h-3.5 w-3.5" /> Stock in</button>
                                <a class="btn btn-sm" href="{{ route('biz.sales.new', ['product' => $p->id]) }}" wire:navigate title="Cash or wholesale sale"><x-icon name="receipt" class="h-3.5 w-3.5" /></a>
                                <a class="btn btn-sm" href="{{ route('biz.contracts.new', ['product' => $p->id]) }}" wire:navigate title="Sell on installments"><x-icon name="calendar" class="h-3.5 w-3.5" /></a>
                                <button class="btn btn-sm" wire:click="openProduct({{ $p->id }})" title="Edit"><x-icon name="pencil" class="h-3.5 w-3.5" /></button>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="8" class="p-6 text-center text-slate-500">No products here yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if ($tab === 'categories')
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            @foreach ($categories as $c)
                <div class="card-pad flex items-center justify-between gap-2 p-4!" wire:key="c-{{ $c->id }}">
                    <button class="min-w-0 text-start" wire:click="$set('categoryFilter', '{{ $c->id }}'); $set('tab', 'products')">
                        <div class="truncate font-medium">{{ $c->name }}</div><div class="text-xs text-slate-500">{{ $c->products_count }} {{ str('product')->plural($c->products_count) }}</div>
                    </button>
                    <div class="flex shrink-0 gap-1">
                        <button class="btn btn-sm" wire:click="openCategory({{ $c->id }})" title="Rename"><x-icon name="pencil" class="h-3.5 w-3.5" /></button>
                        @unless ($c->products_count)<button class="btn btn-sm" wire:click="deleteCategory({{ $c->id }})" wire:confirm="Delete {{ $c->name }}?" title="Delete"><x-icon name="x-circle" class="h-3.5 w-3.5 text-red-600" /></button>@endunless
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @if ($tab === 'brands')
        <div class="mb-3 flex flex-wrap gap-2">
            <select class="input w-auto" wire:model.live="typeFilter" aria-label="Type"><option value="">All types</option>@foreach ($types as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach</select>
            <input class="input max-w-xs" type="search" placeholder="Find a brand" wire:model.live.debounce.300ms="search" aria-label="Find brand">
        </div>
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($brands as $b)
                <div class="card-pad" wire:key="b-{{ $b->id }}">
                    <div class="flex items-start justify-between gap-2">
                        <div><div class="font-semibold">{{ $b->name }}</div><div class="text-xs text-slate-500">{{ $b->type?->name ?? 'Any type' }} · {{ $b->products_count }} {{ str('product')->plural($b->products_count) }}</div></div>
                        <div class="flex gap-1">
                            <button class="btn btn-sm" wire:click="openBrand({{ $b->id }})" title="Edit"><x-icon name="pencil" class="h-3.5 w-3.5" /></button>
                            @unless ($b->products_count)<button class="btn btn-sm" wire:click="deleteBrand({{ $b->id }})" wire:confirm="Delete {{ $b->name }} and its models?" title="Delete"><x-icon name="x-circle" class="h-3.5 w-3.5 text-red-600" /></button>@endunless
                        </div>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-1.5">
                        @foreach ($b->models as $m)
                            <span class="inline-flex items-center gap-1 rounded-full bg-slate-100 py-0.5 ps-2.5 pe-1 text-xs" wire:key="m-{{ $m->id }}">{{ $m->name }}
                                @unless ($m->products_count)<button class="rounded-full p-0.5 text-slate-400 hover:bg-slate-200 hover:text-red-600" wire:click="deleteModel({{ $m->id }})" aria-label="Remove {{ $m->name }}"><x-icon name="x-circle" class="h-3 w-3" /></button>@endunless
                            </span>
                        @endforeach
                    </div>
                    <form wire:submit="addModelTo({{ $b->id }})" class="mt-3 flex gap-2">
                        <input class="input py-1.5 text-sm" placeholder="Add a model" wire:model="modelDraft.{{ $b->id }}" aria-label="New model of {{ $b->name }}">
                        <button class="btn btn-sm">Add</button>
                    </form>
                </div>
            @empty
                <div class="card-pad text-sm text-slate-500">No brands here.</div>
            @endforelse
        </div>
    @endif

    @if ($tab === 'types')
        <p class="mb-3 text-sm text-slate-600">A type decides the details written for each piece: the IMEI of a phone, the engine and chassis numbers of a motorcycle. <strong>The first detail must be different on every piece</strong>, so the same IMEI can never be entered twice.</p>
        <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
            @foreach ($types as $t)
                <div class="card-pad" wire:key="t-{{ $t->id }}">
                    <div class="flex items-start justify-between gap-2">
                        <div><div class="font-semibold">{{ $t->name }}</div><div class="text-xs text-slate-500">{{ $t->products_count }} {{ str('product')->plural($t->products_count) }}</div></div>
                        <button class="btn btn-sm" wire:click="openType({{ $t->id }})"><x-icon name="pencil" class="h-3.5 w-3.5" /></button>
                    </div>
                    <div class="mt-3 flex flex-wrap gap-1.5">
                        @forelse ($t->fields ?? [] as $i => $f)<span @class(['badge', 'badge-blue' => $i === 0, 'badge-slate' => $i > 0])>{{ $f['label'] }}{{ $i === 0 ? ' · unique' : '' }}</span>@empty<span class="text-xs text-slate-400">No details (counted by quantity)</span>@endforelse
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @if ($tab === 'find')
        <div class="card-pad">
            <input class="input py-3 text-lg" type="search" placeholder="Type an IMEI, engine no. or serial no. (3 or more characters)" wire:model.live.debounce.300ms="find" autofocus aria-label="IMEI or serial">
            <ul class="mt-4 space-y-2">
                @forelse ($found as $u)
                    <li class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 p-3" wire:key="f-{{ $u->id }}">
                        <div class="min-w-0">
                            <a class="font-semibold text-brand-600 hover:underline" href="{{ route('biz.product', $u->product_id) }}" wire:navigate>{{ $u->product->label() }}</a>
                            <div class="text-sm text-slate-600">{{ $u->summary($u->product->fields()) }}</div>
                            <div class="text-xs text-slate-500">Bought {{ $u->purchase->date->format('j M Y') }} ({{ $u->purchase->number }}{{ $u->purchase->supplier ? ', '.$u->purchase->supplier : '' }}) for {{ rs($u->unit_cost) }}</div>
                        </div>
                        @if ($u->status === 'in_stock')
                            <span class="badge badge-green">In stock</span>
                        @elseif ($u->contract)
                            <a class="text-end text-sm hover:underline" href="{{ route('biz.contract', $u->contract) }}" wire:navigate><span class="badge badge-blue">Sold on installments</span><div class="text-xs">{{ $u->contract->number }} · {{ $u->contract->customer->name }} · {{ $u->sold_on?->format('j M Y') }}</div></a>
                        @elseif ($u->invoiceLine)
                            <a class="text-end text-sm hover:underline" href="{{ route('biz.sale', $u->invoiceLine->invoice_id) }}" wire:navigate><span class="badge badge-amber">Sold</span><div class="text-xs">{{ $u->invoiceLine->invoice->number }} · {{ $u->invoiceLine->invoice->buyer() }} · {{ $u->sold_on?->format('j M Y') }}</div></a>
                        @endif
                    </li>
                @empty
                    @if (mb_strlen(trim($find)) >= 3)<li class="p-6 text-center text-sm text-slate-500">Nothing with that number.</li>@endif
                @endforelse
            </ul>
        </div>
    @endif

    @if ($showProduct)
        <x-modal :title="$productId ? 'Edit product' : 'New product'" close="$set('showProduct', false)" size="lg">
            <form wire:submit="saveProduct" class="space-y-3">
                <div class="grid gap-3 sm:grid-cols-2">
                    <x-field label="Type" name="product.type_id" hint="Decides the details of each piece, like IMEI"><select id="product.type_id" class="input" wire:model.live="product.type_id"><option value="">No type</option>@foreach ($types as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach</select></x-field>
                    <x-field label="Category" name="product.category_id"><select id="product.category_id" class="input" wire:model="product.category_id"><option value="">No category</option>@foreach ($categories as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></x-field>
                    <div>
                        <x-field label="Brand" name="product.brand_id"><select id="product.brand_id" class="input" wire:model.live="product.brand_id"><option value="">Choose a brand…</option>@foreach ($formBrands as $b)<option value="{{ $b->id }}">{{ $b->name }}</option>@endforeach</select></x-field>
                        <div class="mt-1 flex gap-1.5"><input class="input py-1 text-sm" placeholder="Or add a new brand" wire:model="newBrand" wire:keydown.enter.prevent="addBrand"><button type="button" class="btn btn-sm" wire:click="addBrand">Add</button></div>
                        @error('newBrand')<p class="err">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <x-field label="Model" name="product.model_id"><select id="product.model_id" class="input" wire:model.live="product.model_id" @disabled(! $product['brand_id'])><option value="">{{ $product['brand_id'] ? 'Choose a model…' : 'Choose the brand first' }}</option>@foreach ($formModels as $m)<option value="{{ $m->id }}">{{ $m->name }}</option>@endforeach</select></x-field>
                        <div class="mt-1 flex gap-1.5"><input class="input py-1 text-sm" placeholder="Or add a new model" wire:model="newModel" wire:keydown.enter.prevent="addModel" @disabled(! $product['brand_id'])><button type="button" class="btn btn-sm" wire:click="addModel" @disabled(! $product['brand_id'])>Add</button></div>
                        @error('newModel')<p class="err">{{ $message }}</p>@enderror
                    </div>
                    <x-field label="Product name" name="product.name" hint="Filled from the model; change it if you like"><input id="product.name" class="input" wire:model="product.name"></x-field>
                    <x-field label="Variant" name="product.variant" hint="8/256, colour, size…"><input id="product.variant" class="input" wire:model="product.variant"></x-field>
                    <x-field label="Retail price (Rs)" name="product.sale_price"><input id="product.sale_price" type="number" min="0" class="input" wire:model="product.sale_price"></x-field>
                    <x-field label="Wholesale price (Rs)" name="product.wholesale_price" hint="Optional, for shopkeepers"><input id="product.wholesale_price" type="number" min="0" class="input" wire:model="product.wholesale_price"></x-field>
                    <x-field label="Code / barcode" name="product.code"><input id="product.code" class="input" wire:model="product.code"></x-field>
                    <x-field label="Notes" name="product.notes"><input id="product.notes" class="input" wire:model="product.notes"></x-field>
                </div>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="product.is_active"> Active (shown when selling)</label>
                <x-biz.guide key="product" />
                <div class="flex justify-end gap-2"><button type="button" class="btn" wire:click="$set('showProduct', false)">Cancel</button><button class="btn btn-primary">Save product</button></div>
            </form>
        </x-modal>
    @endif

    @if ($showType)
        <x-modal :title="$typeId ? 'Edit product type' : 'New product type'" close="$set('showType', false)" size="sm">
            <form wire:submit="saveType" class="space-y-3">
                <x-field label="Name" name="typeName"><input id="typeName" class="input" wire:model="typeName" placeholder="Mobile phone"></x-field>
                <x-field label="Details for each piece" name="typeFields" hint="One per line, up to 12. The first one (like IMEI) must be different on every piece."><textarea id="typeFields" class="input" rows="6" wire:model="typeFields"></textarea></x-field>
                <x-biz.guide key="type" />
                <div class="flex justify-end gap-2"><button type="button" class="btn" wire:click="$set('showType', false)">Cancel</button><button class="btn btn-primary">Save type</button></div>
            </form>
        </x-modal>
    @endif

    @if ($categoryName !== null)
        <x-modal :title="$categoryId ? 'Rename category' : 'New category'" close="$set('categoryName', null)" size="sm">
            <form wire:submit="saveCategory" class="space-y-3">
                <x-field label="Name" name="categoryName"><input id="categoryName" class="input" wire:model="categoryName" placeholder="Chargers and cables"></x-field>
                <x-biz.guide key="category" />
                <div class="flex justify-end gap-2"><button type="button" class="btn" wire:click="$set('categoryName', null)">Cancel</button><button class="btn btn-primary">Save</button></div>
            </form>
        </x-modal>
    @endif

    @if ($brandName !== null)
        <x-modal :title="$brandId ? 'Edit brand' : 'New brand'" close="$set('brandName', null)" size="sm">
            <form wire:submit="saveBrand" class="space-y-3">
                <x-field label="Brand" name="brandName"><input id="brandName" class="input" wire:model="brandName" placeholder="Infinix"></x-field>
                <x-field label="For which type" name="brandType"><select id="brandType" class="input" wire:model="brandType"><option value="">Any type</option>@foreach ($types as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach</select></x-field>
                <x-biz.guide key="brand" />
                <div class="flex justify-end gap-2"><button type="button" class="btn" wire:click="$set('brandName', null)">Cancel</button><button class="btn btn-primary">Save</button></div>
            </form>
        </x-modal>
    @endif

    @if ($stockProduct)
        @php($id = $stockProduct->identifier())
        <x-modal :title="'Stock in · '.$stockProduct->label()" close="$set('stockFor', null)" size="lg">
            {{-- Leaving any box refreshes the preview below. --}}
            <form wire:submit="saveStock" x-on:change="$wire.$refresh()" class="space-y-3">
                <div class="grid gap-3 sm:grid-cols-3">
                    <x-field label="Date" name="purchase.date"><input id="purchase.date" type="date" class="input" wire:model="purchase.date"></x-field>
                    <x-field label="Pieces" name="purchase.quantity"><input id="purchase.quantity" type="number" min="1" max="{{ \App\Services\Biz\Stock::MAX_PIECES }}" class="input" wire:model.live.debounce.400ms="purchase.quantity"></x-field>
                    <x-field label="Cost of one piece (Rs)" name="purchase.unit_cost"><input id="purchase.unit_cost" type="number" min="0" class="input" wire:model.live.debounce.400ms="purchase.unit_cost"></x-field>
                    <div>
                        <x-field label="Vendor · Kis se liya" name="purchase.vendor_id">
                            <select id="purchase.vendor_id" class="input" wire:model.live="purchase.vendor_id"><option value="">{{ ($purchase['paid_by'] ?? '') === 'credit' ? 'Choose the vendor…' : 'No vendor' }}</option>@foreach ($vendors as $vd)<option value="{{ $vd->id }}">{{ $vd->name }}</option>@endforeach</select>
                        </x-field>
                        <div class="mt-1 flex gap-1.5"><input class="input py-1 text-sm" placeholder="Or add a new vendor" wire:model="newVendor" wire:keydown.enter.prevent="addVendor"><button type="button" class="btn btn-sm" wire:click="addVendor">Add</button></div>
                        @error('newVendor')<p class="err">{{ $message }}</p>@enderror
                    </div>
                    <x-field label="Paid by" name="purchase.paid_by"><select id="purchase.paid_by" class="input" wire:model.live="purchase.paid_by">@foreach (\App\Models\Biz\Purchase::PAID_BY as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></x-field>
                    @if (in_array($purchase['paid_by'] ?? 'cash', ['cash', 'bank'], true))
                        @php($stockTotal = (int) ($purchase['quantity'] ?? 0) * (int) ($purchase['unit_cost'] ?? 0))
                        @php($left = ($purchase['paid_now'] ?? '') === '' ? 0 : max(0, $stockTotal - (int) $purchase['paid_now']))
                        <x-field label="Paid now (Rs) · Abhi diye" name="purchase.paid_now" :hint="$left ? rs($left).' baqi: owed to the vendor' : 'Empty = paid in full ('.rs($stockTotal).')'">
                            <input id="purchase.paid_now" type="number" min="0" class="input" wire:model.live.debounce.400ms="purchase.paid_now" placeholder="{{ $stockTotal }}">
                        </x-field>
                    @endif
                    <x-field label="Bill / reference" name="purchase.reference"><input id="purchase.reference" class="input" wire:model="purchase.reference"></x-field>
                </div>
                @if ($id)
                    <x-field :label="'Paste the '.$id['label'].' of every piece'" name="identifiers" :hint="'One per line (or scan them one after another). The number of lines becomes the number of pieces. Each '.$id['label'].' must be new.'">
                        <textarea id="identifiers" class="input font-mono text-sm" rows="5" wire:model.live.debounce.600ms="identifiers" placeholder="356789012345678&#10;356789012345686"></textarea>
                    </x-field>
                    @if (count($stockProduct->fields()) > 1)
                        <div>
                            <div class="label">Other details <span class="font-normal text-slate-500">(optional; first 25 pieces)</span></div>
                            <div class="max-h-64 space-y-2 overflow-y-auto">
                                @foreach ($units as $i => $u)
                                    <div class="grid items-center gap-2" style="grid-template-columns: 2rem repeat({{ min(3, count($stockProduct->fields()) - 1) }}, minmax(0, 1fr))" wire:key="u-{{ $i }}">
                                        <span class="text-xs text-slate-500">{{ $i + 1 }}</span>
                                        @foreach (array_slice($stockProduct->fields(), 1, 3) as $f)
                                            <input class="input" placeholder="{{ $f['label'] }}" wire:model="units.{{ $i }}.{{ $f['key'] }}" aria-label="{{ $f['label'] }} of piece {{ $i + 1 }}">
                                        @endforeach
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                @endif
                <x-biz.impact :impact="$impact" />
                <x-biz.guide key="stock" />
                <div class="flex justify-end gap-2"><button type="button" class="btn" wire:click="$set('stockFor', null)">Cancel</button><button class="btn btn-primary" wire:loading.attr="disabled">Add to stock</button></div>
            </form>
        </x-modal>
    @endif
</div>
