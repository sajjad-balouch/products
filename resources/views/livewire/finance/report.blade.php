<div>
    <x-page-header title="Income and expense" subtitle="Last 12 months. Income is fee actually received, and salaries count once marked as paid." />
    <div class="card overflow-x-auto">
        <table class="tbl">
            <thead><tr><th>Month</th><th class="text-end!">Fee received</th><th class="text-end!">Expenses</th><th class="text-end!">Salaries</th><th class="text-end!">Net</th></tr></thead>
            <tbody>
            @foreach ($months as $m)
                <tr><td>{{ $m['month']->format('F Y') }}</td><td class="text-end">{{ number_format($m['income']) }}</td><td class="text-end">{{ number_format($m['expense']) }}</td><td class="text-end">{{ number_format($m['salaries']) }}</td><td @class(['text-end font-medium', 'text-red-700' => $m['net'] < 0, 'text-emerald-700' => $m['net'] > 0])>{{ number_format($m['net']) }}</td></tr>
            @endforeach
            </tbody>
            <tfoot><tr class="font-semibold"><td class="px-3 py-2">Total</td><td class="px-3 py-2 text-end">{{ number_format($months->sum('income')) }}</td><td class="px-3 py-2 text-end">{{ number_format($months->sum('expense')) }}</td><td class="px-3 py-2 text-end">{{ number_format($months->sum('salaries')) }}</td><td class="px-3 py-2 text-end">{{ number_format($months->sum('net')) }}</td></tr></tfoot>
        </table>
    </div>
</div>
