<div>
    <x-page-header title="Defaulters" :subtitle="$rows->count().' families owe Rs '.number_format($total).'. Rs '.number_format($old).' of that is more than 60 days late.'">
        <a class="btn" href="{{ route('fees.defaulters.export') }}">Download CSV</a>
        <button class="btn btn-primary" wire:click="remindAll" wire:confirm="Add a reminder for every family on this list to the outbox?">Remind all</button>
    </x-page-header>
    <div class="card overflow-hidden">
        <div class="border-b border-slate-200 p-3"><input class="input max-w-xs" type="search" placeholder="Search" aria-label="Search" wire:model.live.debounce.300ms="search"></div>
        <div class="overflow-x-auto"><table class="tbl">
            <thead><tr><th>Family</th><th>Children</th><th class="text-end!">0-30 days</th><th class="text-end!">31-60 days</th><th class="text-end!">60+ days</th><th class="text-end!">Total</th><th></th></tr></thead>
            <tbody>
            @forelse ($rows as $row)
                <tr wire:key="d-{{ $row['guardian']->id }}">
                    <td>{{ $row['guardian']->name }}<div class="text-xs text-slate-500">{{ $row['guardian']->phone }}</div></td>
                    <td class="text-xs">@foreach ($row['guardian']->students as $s)<div>{{ $s->name }} · {{ $s->section->label }}</div>@endforeach</td>
                    <td class="text-end">{{ number_format($row['d30']) }}</td>
                    <td class="text-end">{{ number_format($row['d60']) }}</td>
                    <td @class(['text-end', 'font-medium text-red-700' => $row['d90'] > 0])>{{ number_format($row['d90']) }}</td>
                    <td class="text-end font-semibold">{{ number_format($row['total']) }}</td>
                    <td class="text-end"><button class="btn btn-sm" wire:click="remind({{ $row['guardian']->id }})">Remind</button></td>
                </tr>
            @empty
                <tr><td colspan="7" class="py-10 text-center text-slate-500">No overdue fees.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </div>
</div>
