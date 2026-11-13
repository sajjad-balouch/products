<div>
    <x-page-header title="Salesman report" subtitle="What each salesman sold, the commission earned on it, and what is still to pay. · Har salesman ki sale aur commission." />
    @include('livewire.biz.report-bar', ['title' => 'Salesman report'])

    <div class="card overflow-x-auto">
        <table class="tbl">
            <thead><tr><th>Salesman</th><th class="text-end">Cash / credit bills</th><th class="text-end">Installment plans</th><th class="text-end">Total sales</th><th class="text-end">Commission %</th><th class="text-end">Earned</th><th class="text-end">Paid</th><th class="text-end">Still to pay</th></tr></thead>
            <tbody>
            @forelse ($rows as $r)
                <tr>
                    <td><a class="font-medium text-brand-600 hover:underline" href="{{ route('biz.employee', $r['e']) }}" wire:navigate>{{ $r['e']->name }}</a></td>
                    <td class="text-end">{{ rs($r['bills_total']) }}<div class="text-xs text-slate-500">{{ $r['bills'] }} bills</div></td>
                    <td class="text-end">{{ rs($r['plans_total']) }}<div class="text-xs text-slate-500">{{ $r['plans'] }} plans</div></td>
                    <td class="text-end font-semibold">{{ rs($r['total']) }}</td>
                    <td class="text-end">{{ rtrim(rtrim(number_format($r['e']->commission_percent, 2), '0'), '.') }}%</td>
                    <td class="text-end">{{ rs($r['commission']) }}</td>
                    <td class="text-end text-emerald-700">{{ rs($r['paid']) }}</td>
                    <td @class(['text-end font-semibold', 'text-amber-700' => $r['due'] > 0])>{{ rs($r['due']) }}</td>
                </tr>
            @empty
                <tr><td colspan="8" class="p-6 text-center text-slate-500">No salesmen yet. Add one under Employees and tick "Salesman".</td></tr>
            @endforelse
            </tbody>
            @if ($rows->isNotEmpty())
                <tfoot><tr class="font-semibold"><td>Total</td><td class="text-end">{{ rs($rows->sum('bills_total')) }}</td><td class="text-end">{{ rs($rows->sum('plans_total')) }}</td><td class="text-end">{{ rs($rows->sum('total')) }}</td><td></td><td class="text-end">{{ rs($rows->sum('commission')) }}</td><td class="text-end">{{ rs($rows->sum('paid')) }}</td><td class="text-end">{{ rs($rows->sum('due')) }}</td></tr></tfoot>
            @endif
        </table>
    </div>
    @if ($unassigned)
        <p class="mt-3 text-sm text-slate-500">{{ rs($unassigned) }} of sales in these dates had no salesman chosen.</p>
    @endif
</div>
