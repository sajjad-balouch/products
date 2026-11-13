<div class="mx-auto max-w-3xl">
    <x-page-header title="Profit and loss" subtitle="Income less expenses for the dates chosen. Markup counts as income only when the installments are paid." />
    @include('livewire.biz.report-bar', ['title' => 'Profit and loss'])

    <div class="card-pad">
        @foreach (['Income' => [$income, $total_income], 'Expenses' => [$expense, $total_expense]] as $heading => [$rows, $total])
            <h2 class="mb-2 mt-1 text-sm font-semibold tracking-wide text-slate-500 uppercase">{{ $heading }}</h2>
            <table class="mb-4 w-full text-sm">
                @forelse ($rows as $a)
                    <tr class="border-b border-slate-100"><td class="py-1.5"><a class="hover:underline" href="{{ route('biz.ledger', ['account' => $a->id, 'from' => $from, 'to' => $to]) }}" wire:navigate>{{ $a->name }}</a></td><td class="py-1.5 text-end">{{ rs($a->balance) }}</td></tr>
                @empty
                    <tr><td class="py-1.5 text-slate-500">None</td><td></td></tr>
                @endforelse
                <tr class="font-semibold"><td class="py-1.5">Total {{ strtolower($heading) }}</td><td class="py-1.5 text-end">{{ rs($total) }}</td></tr>
            </table>
        @endforeach
        <div @class(['flex items-center justify-between rounded-xl p-4 text-lg font-bold', 'bg-emerald-50 text-emerald-800' => $profit >= 0, 'bg-red-50 text-red-800' => $profit < 0])>
            <span>{{ $profit >= 0 ? 'Net profit' : 'Net loss' }}</span><span>{{ rs(abs($profit)) }}</span>
        </div>
    </div>
</div>
