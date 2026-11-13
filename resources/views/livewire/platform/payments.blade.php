<div>
    <x-page-header title="Payments" subtitle="Payments, free months and date changes for every school. Record new ones from the school's Fee and access tab." />

    <div class="mb-4 flex flex-wrap items-center gap-2">
        <select class="input w-auto" aria-label="Month" wire:model.live="month">
            <option value="">All time</option>
            @foreach ($months as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
        </select>
        <select class="input w-auto" aria-label="Type" wire:model.live="type">
            <option value="">Everything</option>
            @foreach (\App\Models\SubscriptionEntry::TYPES as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach
        </select>
        <span class="ms-auto text-sm">Payments received: <strong>Rs {{ number_format($total) }}</strong></span>
    </div>

    <div class="card overflow-x-auto">
        <table class="tbl">
            <thead><tr><th>Date</th><th>School</th><th>What</th><th>Amount</th><th>Access until</th><th>Details</th><th>By</th><th></th></tr></thead>
            <tbody>
            @forelse ($entries as $entry)
                <tr wire:key="pay-{{ $entry->id }}">
                    <td>{{ ($entry->paid_on ?? $entry->created_at)->format('j M Y') }}</td>
                    <td><a class="text-brand-600 hover:underline" href="{{ route('platform.school', $entry->school_id) }}" wire:navigate>{{ $entry->school?->name }}</a></td>
                    <td>{{ \App\Models\SubscriptionEntry::TYPES[$entry->type] }}@if ($entry->months) · {{ $entry->months }} {{ str('month')->plural($entry->months) }}@endif</td>
                    <td>{{ $entry->amount ? 'Rs '.number_format($entry->amount) : '' }}</td>
                    <td>{{ $entry->to_date->format('j M Y') }}</td>
                    <td class="text-xs">{{ $entry->method ? \App\Models\SubscriptionEntry::METHODS[$entry->method] : '' }} {{ $entry->reference }} {{ $entry->note }}</td>
                    <td class="text-xs">{{ $entry->user?->name }}</td>
                    <td class="text-end">@if ($entry->type === 'payment')<a class="btn btn-sm" href="{{ route('platform.receipt', $entry) }}" target="_blank" rel="noopener">Receipt</a>@endif</td>
                </tr>
            @empty
                <tr><td colspan="8" class="p-6 text-center text-slate-500">Nothing recorded for this choice.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-4">{{ $entries->links() }}</div>
</div>
