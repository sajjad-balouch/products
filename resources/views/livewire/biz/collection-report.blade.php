<div>
    <x-page-header title="Collections" subtitle="Down payments, installments and late fees received. Cancelled receipts are left out." />
    @include('livewire.biz.report-bar', ['title' => 'Collections'])

    <div class="no-print mb-4 flex flex-wrap gap-2">
        <select class="input w-auto" wire:model.live="method" aria-label="Method"><option value="">All methods</option>@foreach (\App\Models\Biz\Receipt::METHODS as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select>
        <select class="input w-auto" wire:model.live="by" aria-label="Received by"><option value="">Everyone</option>@foreach ($staff as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select>
    </div>

    <div class="mb-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat icon="cash" tone="green" label="Total received" :value="rs($receipts->sum(fn ($r) => $r->total()))" :note="$receipts->count().' receipts'" />
        <x-stat icon="layers" tone="brand" label="Installments" :value="rs($receipts->where('is_down_payment', false)->sum('amount'))" />
        <x-stat icon="tag" tone="blue" label="Down payments" :value="rs($receipts->where('is_down_payment', true)->sum('amount'))" />
        <x-stat icon="clock" tone="amber" label="Late fees" :value="rs($receipts->sum('late_fee'))" />
    </div>

    <div class="grid gap-4 xl:grid-cols-3">
        <div class="card overflow-x-auto xl:col-span-2">
            <table class="tbl">
                <thead><tr><th>Date</th><th>Receipt</th><th>Customer</th><th>Method</th><th class="text-end">Amount</th></tr></thead>
                <tbody>
                @forelse ($receipts as $r)
                    <tr wire:key="r-{{ $r->id }}">
                        <td class="whitespace-nowrap">{{ $r->date->format('j M Y') }}</td>
                        <td><a class="font-mono text-xs hover:underline" href="{{ route('biz.print.receipt', $r) }}" target="_blank">{{ $r->number }}</a>@if ($r->is_down_payment)<span class="badge badge-blue ms-1">Down</span>@endif</td>
                        <td>{{ $r->contract->customer->name }}<div class="text-xs text-slate-500">{{ $r->contract->number }} · {{ $r->receiver?->name }}</div></td>
                        <td class="text-xs">{{ \App\Models\Biz\Receipt::METHODS[$r->method] ?? $r->method }}</td>
                        <td class="text-end">{{ rs($r->total()) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="p-6 text-center text-slate-500">Nothing received in these dates.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="space-y-4">
            @foreach (['By method' => $byMethod->mapWithKeys(fn ($v, $k) => [\App\Models\Biz\Receipt::METHODS[$k] ?? $k => $v]), 'By person' => $byPerson] as $heading => $totals)
                <div class="card-pad">
                    <h2 class="text-sm font-semibold">{{ $heading }}</h2>
                    <table class="mt-2 w-full text-sm">
                        @forelse ($totals as $label => $amount)
                            <tr class="border-b border-slate-100 last:border-0"><td class="py-1.5">{{ $label }}</td><td class="py-1.5 text-end font-medium">{{ rs($amount) }}</td></tr>
                        @empty
                            <tr><td class="py-1.5 text-slate-500">—</td></tr>
                        @endforelse
                    </table>
                </div>
            @endforeach
        </div>
    </div>
</div>
