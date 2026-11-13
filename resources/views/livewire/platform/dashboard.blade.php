<div>
    @php($hour = now()->hour)
    <x-dash-hero :greeting="$hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening')" :subtitle="'Every school and college using '.config('app.name').', and what they pay.'">
        <div class="flex flex-wrap gap-2">
            <a class="btn border-white/20 bg-white/10 text-white hover:bg-white/20" href="{{ route('platform.payments') }}" wire:navigate><x-icon name="card" class="h-4 w-4" /> Payments</a>
            <a class="btn border-white bg-white text-brand-800 hover:bg-white/90" href="{{ route('platform.schools') }}" wire:navigate><x-icon name="building" class="h-4 w-4" /> All schools</a>
        </div>
    </x-dash-hero>

    <div class="mb-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat icon="building" tone="blue" label="Schools and colleges" :value="$total" :note="number_format($students).' active students'" :href="route('platform.schools')" />
        <x-stat icon="check-circle" tone="green" label="Paying or free period" :value="($counts['active'] ?? 0) + ($counts['trial'] ?? 0)" :note="($counts['active'] ?? 0).' paying, '.($counts['trial'] ?? 0).' in free period'" />
        <x-stat icon="cash" tone="brand" label="Collected this month" :value="'Rs '.number_format($thisMonth)" :note="'Last month Rs '.number_format($lastMonth)" :href="route('platform.payments')" />
        <x-stat icon="package" tone="violet" label="Monthly fees set" :value="'Rs '.number_format($expected)" note="All active schools together" :href="route('platform.plans')" />
    </div>

    <div class="mb-5 grid grid-cols-2 gap-3 lg:grid-cols-4">
        @foreach (['grace' => ['Payment due', 'clock', 'amber'], 'expired' => ['Locked or expired', 'x-circle', 'red'], 'suspended' => ['Suspended', 'alert-circle', 'red'], 'open' => ['No end date', 'calendar', 'slate']] as $key => [$label, $icon, $tone])
            <a href="{{ route('platform.schools', ['status' => $key]) }}" wire:navigate class="action-tile">
                <div class="icon-chip tone-{{ $tone }}"><x-icon :name="$icon" /></div>
                <div><div class="text-xl font-bold">{{ $counts[$key] ?? 0 }}</div><div class="text-xs text-slate-500">{{ $label }}</div></div>
            </a>
        @endforeach
    </div>

    <div class="grid gap-4 xl:grid-cols-2">
        <div class="card overflow-x-auto">
            <div class="border-b border-slate-200 px-4 py-3 text-sm font-semibold">Needs attention <span class="font-normal text-slate-500">ending within 2 weeks, or already past</span></div>
            <table class="tbl">
                <thead><tr><th>School</th><th>Status</th><th>Access until</th><th>Fee</th><th></th></tr></thead>
                <tbody>
                @forelse ($attention as $school)
                    <tr wire:key="att-{{ $school->id }}">
                        <td><a class="font-medium text-brand-600 hover:underline" href="{{ route('platform.school', $school) }}" wire:navigate>{{ $school->name }}</a><div class="text-xs text-slate-500">{{ $school->phone }}</div></td>
                        <td><x-school-status :school="$school" /></td>
                        <td>{{ $school->accessUntil()?->format('j M Y') }}<div class="text-xs text-slate-500">{{ $school->daysLeft() >= 0 ? $school->daysLeft().' days left' : abs($school->daysLeft()).' days ago' }}</div></td>
                        <td>{{ $school->monthly_fee ? 'Rs '.number_format($school->monthly_fee) : 'Not set' }}</td>
                        <td class="text-end">@if ($reminder = $school->reminderUrl())<a class="btn btn-sm" href="{{ $reminder }}" target="_blank" rel="noopener">Remind</a>@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="p-6 text-center text-slate-500">Nothing needs attention.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="card overflow-x-auto">
            <div class="border-b border-slate-200 px-4 py-3 text-sm font-semibold">Latest changes to access</div>
            <table class="tbl">
                <thead><tr><th>School</th><th>What</th><th>Until</th></tr></thead>
                <tbody>
                @forelse ($recent as $entry)
                    <tr wire:key="rec-{{ $entry->id }}">
                        <td><a class="text-brand-600 hover:underline" href="{{ route('platform.school', $entry->school_id) }}" wire:navigate>{{ $entry->school?->name }}</a><div class="text-xs text-slate-500">{{ $entry->created_at->format('j M Y') }}</div></td>
                        <td>{{ \App\Models\SubscriptionEntry::TYPES[$entry->type] }}@if ($entry->type === 'payment') Rs {{ number_format($entry->amount) }}@endif @if ($entry->months) · {{ $entry->months }} {{ str('month')->plural($entry->months) }}@endif</td>
                        <td>{{ $entry->to_date->format('j M Y') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="p-6 text-center text-slate-500">No payments or free months recorded yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
