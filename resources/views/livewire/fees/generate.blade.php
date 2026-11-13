<div class="mx-auto max-w-2xl">
    <x-page-header title="Generate monthly vouchers" subtitle="Creates one voucher per family, with siblings together. Running it again only adds families that were missed." />

    @unless ($hasStructure)
        <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
            Set class fee amounts first. <a class="underline" href="{{ route('fees.structure') }}" wire:navigate>Open fee structure</a>
        </div>
    @endunless

    <form wire:submit="generate" class="card-pad space-y-3">
        <div class="grid gap-3 sm:grid-cols-2">
            <x-field label="Month" name="month"><input id="month" class="input" type="month" wire:model.live="month"></x-field>
            <x-field label="Due date" name="due_on"><input id="due_on" class="input" type="date" wire:model="due_on"></x-field>
        </div>
        <x-field label="Class" name="class_id" hint="Families with a child in this class get a voucher that covers all their children.">
            <select id="class_id" class="input" wire:model="class_id">
                <option value="">All classes</option>
                @foreach ($classes as $c)<option value="{{ $c->id }}">{{ $c->label }}</option>@endforeach
            </select>
        </x-field>
        <p class="text-sm text-slate-500">{{ $existing }} vouchers already exist for this month. Sibling discount: {{ $school->sibling_discount_percent }}%.</p>
        <button class="btn btn-primary" wire:loading.attr="disabled" wire:target="generate">Generate vouchers</button>
    </form>

    @if ($result)
        <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">
            Created {{ $result['created'] }} vouchers. {{ $result['skipped'] }} families already had one.
            @if ($result['skipped_no_fee']) {{ $result['skipped_no_fee'] }} families were skipped because their class has no fee set. @endif
            <a class="underline" href="{{ route('fees.vouchers') }}" wire:navigate>View vouchers</a>
        </div>
    @endif

    <div class="card-pad mt-4">
        <h2 class="font-semibold">Late fees</h2>
        <p class="mt-1 text-sm text-slate-500">
            @if ($school->late_fee_per_day)
                Rs {{ $school->late_fee_per_day }} per day after the due date{{ $school->late_fee_max ? ', up to Rs '.number_format($school->late_fee_max) : '' }}. This also runs by itself each night when the scheduler is set up.
            @else
                Late fee is off. Turn it on in Settings.
            @endif
        </p>
        <button class="btn mt-3" wire:click="applyLateFees" @disabled(! $school->late_fee_per_day)>Update late fees now</button>
    </div>
</div>
