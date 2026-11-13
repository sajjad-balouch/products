<div class="mx-auto max-w-4xl">
    <x-page-header title="Business settings" subtitle="Your details print on receipts, statements and agreements. The plan defaults fill in every new sale, and can be changed per sale." />

    <form wire:submit="save" class="space-y-5">
        <section class="card-pad">
            <h2 class="panel-title"><x-icon name="building" class="h-5 w-5 text-brand-600" /> Business</h2>
            <div class="mt-3 grid gap-3 sm:grid-cols-2">
                <x-field label="Business name" name="details.name"><input id="details.name" class="input" wire:model="details.name"></x-field>
                <x-field label="Owner's name" name="details.principal_name"><input id="details.principal_name" class="input" wire:model="details.principal_name"></x-field>
                <x-field label="Phone" name="details.phone"><input id="details.phone" class="input" wire:model="details.phone"></x-field>
                <x-field label="Email" name="details.email"><input id="details.email" class="input" type="email" wire:model="details.email"></x-field>
                <x-field label="Address" name="details.address" class="sm:col-span-2"><input id="details.address" class="input" wire:model="details.address"></x-field>
            </div>
        </section>

        <section class="card-pad">
            <h2 class="panel-title"><x-icon name="package" class="h-5 w-5 text-brand-600" /> What your business sells</h2>
            <p class="mt-1 text-sm text-slate-500">Sets which product types, categories and brands you see, and what your dashboard and menu put first. Changing it adds the new ready-made items; nothing you already have is removed.</p>
            <div class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-3">
                @foreach (\App\Support\BusinessKinds::KINDS as $key => [$label, $icon, $about])
                    <label @class(['flex cursor-pointer items-start gap-2.5 rounded-xl border p-3 transition', 'border-brand-500 bg-brand-50 ring-1 ring-brand-500' => $kind === $key, 'border-slate-200 hover:border-slate-300' => $kind !== $key])>
                        <input type="radio" class="sr-only" value="{{ $key }}" wire:model.live="kind">
                        <span @class(['icon-chip h-9! w-9! shrink-0', 'tone-brand' => $kind === $key, 'tone-slate' => $kind !== $key])><x-icon :name="$icon" class="h-4 w-4" /></span>
                        <span><span class="block text-sm font-semibold">{{ $label }}</span><span class="block text-xs text-slate-500">{{ $about }}</span></span>
                    </label>
                @endforeach
            </div>
            @error('kind')<p class="err">{{ $message }}</p>@enderror
        </section>

        <section class="card-pad">
            <h2 class="panel-title"><x-icon name="calendar" class="h-5 w-5 text-violet-600" /> Plan defaults</h2>
            <div class="mt-3 grid gap-3 sm:grid-cols-3">
                <x-field label="Markup %" name="plan.markup_percent"><input id="plan.markup_percent" type="number" step="0.01" class="input" wire:model="plan.markup_percent"></x-field>
                <x-field label="Number of installments" name="plan.installments"><input id="plan.installments" type="number" class="input" wire:model="plan.installments"></x-field>
                <x-field label="Paid every" name="plan.frequency"><select id="plan.frequency" class="input" wire:model="plan.frequency">@foreach (\App\Models\Biz\Contract::FREQUENCIES as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></x-field>
                <x-field label="Late fee per late installment (Rs)" name="plan.late_fee"><input id="plan.late_fee" type="number" class="input" wire:model="plan.late_fee"></x-field>
                <x-field label="Grace days" name="plan.grace_days"><input id="plan.grace_days" type="number" class="input" wire:model="plan.grace_days"></x-field>
                <x-field label="Round installments to" name="plan.rounding"><select id="plan.rounding" class="input" wire:model="plan.rounding">@foreach (['1' => 'Exact rupee', '10' => 'Nearest 10', '50' => 'Nearest 50', '100' => 'Nearest 100'] as $r => $label)<option value="{{ $r }}">{{ $label }}</option>@endforeach</select></x-field>
            </div>
        </section>

        <section class="card-pad">
            <h2 class="panel-title"><x-icon name="file-plus" class="h-5 w-5 text-amber-600" /> Printing</h2>
            <div class="mt-3 space-y-3">
                <x-field label="Note at the bottom of receipts" name="plan.receipt_note"><input id="plan.receipt_note" class="input" wire:model="plan.receipt_note"></x-field>
                <x-field label="Terms and conditions on the agreement" name="plan.terms" hint="One condition per line. The customer and guarantors sign under these."><textarea id="plan.terms" class="input" rows="7" wire:model="plan.terms"></textarea></x-field>
            </div>
        </section>

        <div class="flex justify-end"><button class="btn btn-primary" wire:loading.attr="disabled">Save settings</button></div>
    </form>
    <livewire:reminder-settings />
</div>
