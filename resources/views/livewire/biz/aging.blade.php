<div>
    <x-page-header title="Aging of dues" subtitle="What every running plan still owes, split by how long it has been late. The further right, the harder to recover.">
        <button type="button" class="btn btn-sm no-print" onclick="window.print()"><x-icon name="file-plus" class="h-3.5 w-3.5" /> Print</button>
    </x-page-header>

    <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-5">
        @foreach (\App\Livewire\Biz\AgingReport::BUCKETS as $key => $label)
            <div class="card-pad p-4!">
                <div class="text-xs text-slate-500">{{ $label }}</div>
                <div @class(['text-lg font-bold', 'text-red-700' => in_array($key, ['d90', 'over'], true), 'text-amber-700' => in_array($key, ['d30', 'd60'], true)])>{{ rs($totals[$key]) }}</div>
                <div class="mt-1 h-1.5 rounded-full bg-slate-100"><div @class(['h-full rounded-full', 'bg-emerald-500' => $key === 'current', 'bg-amber-400' => in_array($key, ['d30', 'd60'], true), 'bg-red-500' => in_array($key, ['d90', 'over'], true)]) style="width: {{ $grand ? round($totals[$key] / $grand * 100) : 0 }}%"></div></div>
            </div>
        @endforeach
    </div>

    <div class="card overflow-x-auto">
        <table class="tbl">
            <thead><tr><th>Customer</th><th>Plan</th>@foreach (\App\Livewire\Biz\AgingReport::BUCKETS as $label)<th class="text-end">{{ $label }}</th>@endforeach<th class="text-end">Total owed</th></tr></thead>
            <tbody>
            @forelse ($rows as $row)
                <tr wire:key="a-{{ $row['contract']->id }}">
                    <td>{{ $row['contract']->customer->name }}<div class="text-xs text-slate-500">{{ $row['contract']->customer->phone }}</div></td>
                    <td><a class="hover:underline" href="{{ route('biz.contract', $row['contract']) }}" wire:navigate>{{ $row['contract']->number }}</a></td>
                    @foreach ($row['buckets'] as $key => $amount)
                        <td @class(['text-end', 'font-semibold text-red-700' => $amount && in_array($key, ['d90', 'over'], true)])>{{ $amount ? number_format($amount) : '' }}</td>
                    @endforeach
                    <td class="text-end font-semibold">{{ rs($row['total']) }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="p-6 text-center text-slate-500">Nothing is owed.</td></tr>
            @endforelse
            </tbody>
            @if ($rows->isNotEmpty())
                <tfoot><tr class="font-semibold"><td colspan="2">Total</td>@foreach ($totals as $amount)<td class="text-end">{{ number_format($amount) }}</td>@endforeach<td class="text-end">{{ rs($grand) }}</td></tr></tfoot>
            @endif
        </table>
    </div>
</div>
