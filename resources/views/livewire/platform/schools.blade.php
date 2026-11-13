<div>
    <x-page-header title="Schools" :subtitle="$schools->count().' shown, with '.number_format($students->only($schools->pluck('id'))->sum()).' active students. Open a school to change its fee, access, features, logins or website.'">
        <button class="btn btn-primary" wire:click="$set('showForm', true)">Add school</button>
    </x-page-header>

    @if ($issued)
        <div class="mb-4 rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
            <p class="font-semibold">{{ $issued['school'] }} is ready. This password is shown only once.</p>
            <p class="mt-1">Username <span class="font-mono">{{ $issued['username'] }}</span>, password <span class="font-mono">{{ $issued['password'] }}</span>. It must be changed at first sign-in.
                <a class="font-medium underline" href="{{ route('platform.school', $issued['id']) }}" wire:navigate>Open the school</a></p>
        </div>
    @endif

    <div class="mb-4 flex flex-wrap gap-2">
        <input class="input w-full sm:w-72" type="search" placeholder="Search name, phone or email" aria-label="Search schools" wire:model.live.debounce.300ms="search">
        <select class="input w-auto" aria-label="Status" wire:model.live="status">
            <option value="">Every status</option>
            @foreach (['active' => 'Active', 'trial' => 'Free period', 'grace' => 'Payment due', 'expired' => 'Locked or expired', 'suspended' => 'Suspended', 'open' => 'No end date'] as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
        </select>
    </div>

    <div class="card overflow-x-auto">
        <table class="tbl">
            <thead><tr><th>School</th><th>Students</th><th>Monthly fee</th><th>Access until</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse ($schools as $school)
                <tr wire:key="sc-{{ $school->id }}">
                    <td><a class="font-medium text-brand-600 hover:underline" href="{{ route('platform.school', $school) }}" wire:navigate>{{ $school->name }}</a><div class="text-xs text-slate-500">{{ $school->phone }} · {{ $school->email }}</div></td>
                    <td>{{ $students[$school->id] ?? 0 }}</td>
                    <td>{{ $school->monthly_fee ? 'Rs '.number_format($school->monthly_fee) : 'Not set' }}</td>
                    <td>{{ $school->accessUntil()?->format('j M Y') ?? 'No end date' }}</td>
                    <td><x-school-status :school="$school" /></td>
                    <td class="text-end whitespace-nowrap">
                        <a class="btn btn-sm" href="{{ route('platform.school', $school) }}" wire:navigate>Open</a>
                        <button class="btn btn-sm" wire:click="toggle({{ $school->id }})" wire:confirm="{{ $school->is_active ? 'Suspend' : 'Activate' }} {{ $school->name }}?">{{ $school->is_active ? 'Suspend' : 'Activate' }}</button>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="p-6 text-center text-slate-500">No schools match.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>

    @if ($showForm)
        <x-modal title="Add school or college" close="$set('showForm', false)" size="lg">
            <form wire:submit="create" class="space-y-3">
                <x-field label="School or college name" name="school"><input id="school" class="input" wire:model="school"></x-field>
                <x-field label="Type" name="type"><select id="type" class="input" wire:model="type"><option value="school">School</option><option value="college">College</option><option value="both">School and college</option></select></x-field>
                <div class="grid gap-3 sm:grid-cols-2">
                    <x-field label="Admin name" name="name"><input id="name" class="input" wire:model="name"></x-field>
                    <x-field label="Admin email" name="email" hint="This is their username"><input id="email" class="input" type="email" wire:model="email"></x-field>
                    <x-field label="Phone" name="phone"><input id="phone" class="input" wire:model="phone"></x-field>
                    <x-field label="Monthly fee (Rs)" name="monthlyFee"><input id="monthlyFee" class="input" type="number" min="0" wire:model="monthlyFee"></x-field>
                </div>
                <x-field label="Free days to start" name="freeDays" hint="Access ends after these days unless a payment or free months are added"><input id="freeDays" class="input" type="number" min="0" wire:model="freeDays"></x-field>
                <div class="flex justify-end gap-2">
                    <button type="button" class="btn" wire:click="$set('showForm', false)">Cancel</button>
                    <button class="btn btn-primary" wire:loading.attr="disabled">Create school</button>
                </div>
            </form>
        </x-modal>
    @endif
</div>
