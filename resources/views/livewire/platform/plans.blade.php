<div>
    <x-page-header title="Plans" subtitle="The packages you sell, for each product. Putting a customer on a plan sets its monthly fee and its features in one step." />

    @foreach (\App\Models\School::PRODUCTS as $product => $productName)
        <div class="mb-3 mt-6 flex flex-wrap items-center justify-between gap-2 first:mt-0">
            <h2 class="panel-title"><x-icon :name="$product === 'business' ? 'briefcase' : 'school'" class="h-5 w-5 text-brand-600" /> {{ $productName }}</h2>
            <button class="btn btn-sm btn-primary" wire:click="openForm(null, '{{ $product }}')"><x-icon name="plus" class="h-3.5 w-3.5" /> New {{ $product }} plan</button>
        </div>
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($plans->where('product', $product) as $plan)
                <div class="card-pad flex flex-col" wire:key="plan-{{ $plan->id }}">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <h3 class="text-lg font-semibold">{{ $plan->name }}</h3>
                            @if ($plan->description)<p class="text-sm text-slate-500">{{ $plan->description }}</p>@endif
                        </div>
                        <span class="badge badge-slate">{{ $plan->schools_count }} {{ str($product === 'business' ? 'business' : 'school')->plural($plan->schools_count) }}</span>
                    </div>
                    <div class="mt-3 text-2xl font-bold">{{ $plan->monthly_price ? 'Rs '.number_format($plan->monthly_price) : 'Price not set' }}<span class="text-sm font-normal text-slate-500"> / month</span></div>
                    <ul class="mt-3 flex-1 space-y-1 text-sm">
                        @foreach (\App\Models\School::featuresFor($product) as $key => [$label])
                            <li @class(['text-slate-400 line-through' => ! in_array($key, $plan->features, true)])>{{ in_array($key, $plan->features, true) ? '✓' : '✕' }} {{ $label }}</li>
                        @endforeach
                    </ul>
                    <div class="mt-4 flex gap-2">
                        <button class="btn btn-sm" wire:click="openForm({{ $plan->id }})">Edit</button>
                        <button class="btn btn-sm btn-danger" wire:click="delete({{ $plan->id }})" wire:confirm="Delete the {{ $plan->name }} plan?">Delete</button>
                    </div>
                </div>
            @empty
                <div class="card-pad text-sm text-slate-500">No plans yet.</div>
            @endforelse
        </div>
    @endforeach

    @if ($showForm)
        <x-modal :title="$editingId ? 'Edit plan' : 'New plan'" close="$set('showForm', false)" size="lg">
            <form wire:submit="save" class="space-y-3">
                <div class="grid gap-3 sm:grid-cols-2">
                    <x-field label="Name" name="name"><input id="name" class="input" wire:model="name"></x-field>
                    <x-field label="Monthly price (Rs)" name="monthlyPrice"><input id="monthlyPrice" class="input" type="number" min="0" wire:model="monthlyPrice"></x-field>
                </div>
                @unless ($editingId)
                    <x-field label="Short code" name="code" hint="Letters and dashes. Leave empty to make it from the name."><input id="code" class="input" wire:model="code"></x-field>
                @endunless
                <x-field label="Description" name="description"><input id="description" class="input" wire:model="description"></x-field>
                <fieldset>
                    <legend class="label">Features in this {{ \App\Models\School::PRODUCTS[$product] }} plan</legend>
                    <div class="grid gap-1 sm:grid-cols-2">
                        @foreach (\App\Models\School::featuresFor($product) as $key => [$label])
                            <label class="flex items-center gap-2 text-sm"><input type="checkbox" value="{{ $key }}" wire:model="features"> {{ $label }}</label>
                        @endforeach
                    </div>
                </fieldset>
                @if ($editingId)
                    <label class="flex items-start gap-2 rounded-lg bg-amber-50 p-2 text-sm text-amber-900"><input type="checkbox" class="mt-1" wire:model="updateSchools"> Also give every school on this plan the new price and features now. Without this, only schools put on the plan later get them.</label>
                @endif
                <div class="flex justify-end gap-2">
                    <button type="button" class="btn" wire:click="$set('showForm', false)">Cancel</button>
                    <button class="btn btn-primary" wire:loading.attr="disabled">Save plan</button>
                </div>
            </form>
        </x-modal>
    @endif
</div>
