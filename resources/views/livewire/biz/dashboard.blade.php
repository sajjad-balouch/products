<div>
    @php($sales = $focus === 'sales')
    <x-dash-hero :greeting="$greeting" :subtitle="auth()->user()->school->name.' · '.\App\Support\BusinessKinds::label($kind).' · '.now()->format('F').' at a glance'">
        <div class="flex flex-wrap gap-2">
            <div class="rounded-xl bg-white/10 px-4 py-2.5 ring-1 ring-white/15">
                <div class="text-xs text-white/70">{{ $sales ? 'Sold today' : 'Received today' }}</div>
                <div class="text-lg font-bold">{{ rs($sales ? $salesToday : $todayCash) }}</div>
            </div>
            <div class="rounded-xl bg-white/10 px-4 py-2.5 ring-1 ring-white/15">
                <div class="text-xs text-white/70">{{ $sales ? 'Udhaar to collect' : 'Overdue' }}</div>
                <div class="text-lg font-bold">{{ rs($sales ? $udhaar : $overdue) }}</div>
            </div>
        </div>
    </x-dash-hero>

    @if ($newBusiness)
        <div class="card-pad mb-5">
            <h2 class="panel-title"><x-icon name="sparkles" class="h-5 w-5 text-accent-500" /> Get started in three steps</h2>
            <div class="mt-3 grid gap-2 sm:grid-cols-3">
                @foreach ([['biz.vouchers', "Enter your opening cash, bank and capital (journal voucher)"], ['biz.products', 'Add your products and stock in, with each '.($kind === 'mobile' ? 'IMEI' : 'serial / engine no.')], [$focus === 'sales' ? 'biz.sales.new' : 'biz.contracts.new', $focus === 'sales' ? 'Make your first sale' : 'Make your first installment sale']] as $i => [$route, $label])
                    @if ($route !== 'biz.settings' || $isAdmin)
                        <a class="action-tile" href="{{ route($route) }}" wire:navigate><div class="icon-chip tone-brand font-bold">{{ $i + 1 }}</div><div class="text-sm font-medium">{{ $label }}</div></a>
                    @endif
                @endforeach
            </div>
        </div>
    @endif

    {{-- A counter shop works with sales, stock and IMEIs first; a dealer with installments and recovery. --}}
    @php($identity = $kind === 'mobile' ? 'IMEI' : (in_array($kind, ['motorcycle', 'car'], true) ? 'engine no.' : 'serial'))
    <x-quick-actions :actions="$sales ? [
        ['biz.sales.new', 'New sale', 'Cash, wholesale or udhaar', 'receipt', 'brand'],
        ['biz.products', 'Stock in', 'Add pieces with '.$identity, 'package', 'blue'],
        ['biz.sales', 'Sales and udhaar', rs($udhaar).' to collect', 'wallet', 'amber'],
        ['biz.contracts.new', 'Installment sale', 'Sell on qist', 'calendar', 'violet'],
        ['biz.collect', 'Collect payment', 'Take a qist', 'cash', 'green'],
        ['biz.vouchers', 'New voucher', 'Expense, receipt, journal', 'list', 'slate'],
    ] : [
        ['biz.contracts.new', 'Installment sale', 'Sell on qist', 'calendar', 'brand'],
        ['biz.collect', 'Collect payment', 'Find customer, take qist', 'cash', 'green'],
        ['biz.due', 'Due and recovery', $lateCount.' plans late', 'alert-circle', 'red'],
        ['biz.sales.new', 'Cash sale', 'Cash, wholesale or udhaar', 'receipt', 'violet'],
        ['biz.products', 'Products and stock', 'Stock in, find '.$identity, 'package', 'blue'],
        ['biz.vouchers', 'New voucher', 'Expense, receipt, journal', 'list', 'slate'],
    ]" />

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
        @if ($sales)
            <x-stat icon="receipt" tone="brand" label="Sold this month" :value="rs($salesMonth)" :note="'Today '.rs($salesToday)" :href="route('biz.sales')" />
            <x-stat icon="cash" tone="green" label="Received this month" :value="rs($monthCash)" :href="route('biz.reports.collections')" />
            <x-stat icon="wallet" :tone="$udhaar ? 'amber' : 'green'" label="Udhaar to collect" :value="rs($udhaar)" :href="route('biz.sales', ['unpaid' => 1])" />
            <x-stat icon="package" tone="blue" label="Stock in hand" :value="number_format($stock->pieces).' pieces'" :note="'Cost '.rs($stock->value)" :href="route('biz.products')" />
            <x-stat icon="banknote" tone="blue" label="Cash in hand" :value="rs($cash)" :href="route('biz.ledger')" />
            <x-stat icon="building" tone="slate" label="Bank" :value="rs($bank)" :href="route('biz.ledger')" />
            <x-stat icon="trend" :tone="$profit >= 0 ? 'green' : 'red'" label="Profit this month" :value="rs($profit)" :href="route('biz.reports.pl')" />
            <x-stat icon="calendar" tone="violet" label="On installments" :value="rs($receivable)" :note="$activeCount.' running plans · '.rs($overdue).' overdue'" :href="route('biz.due')" />
        @else
            <x-stat icon="cash" tone="green" label="Received this month" :value="rs($monthCash)" :href="route('biz.reports.collections')" />
            <x-stat icon="percent" label="This month's installments paid" :value="($dueMonth ? round($paidMonth / $dueMonth * 100) : 0).'%'" :note="rs($paidMonth).' of '.rs($dueMonth).' due'" :tone="$dueMonth && $paidMonth / $dueMonth >= .8 ? 'green' : 'amber'" />
            <x-stat icon="alert-circle" tone="red" label="Overdue" :value="rs($overdue)" :note="$lateCount.' '.str('plan')->plural($lateCount).' late'" :href="route('biz.due')" />
            <x-stat icon="wallet" tone="brand" label="To be received (all plans)" :value="rs($receivable)" :note="$activeCount.' running plans'" :href="route('biz.reports.aging')" />
            <x-stat icon="banknote" tone="blue" label="Cash in hand" :value="rs($cash)" :href="route('biz.ledger')" />
            <x-stat icon="package" tone="slate" label="Stock in hand" :value="number_format($stock->pieces).' pieces'" :note="'Cost '.rs($stock->value)" :href="route('biz.products')" />
            <x-stat icon="trend" :tone="$profit >= 0 ? 'green' : 'red'" label="Profit this month" :value="rs($profit)" :href="route('biz.reports.pl')" />
            <x-stat icon="receipt" tone="violet" label="Udhaar to collect" :value="rs($udhaar)" :note="'Sold today '.rs($salesToday)" :href="route('biz.sales', ['unpaid' => 1])" />
        @endif
    </div>

    <div class="mt-5 grid gap-4 lg:grid-cols-3">
        <div class="card-pad lg:col-span-2">
            @php($max = max(1, $trend->max('amount')))
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <h2 class="panel-title"><x-icon name="trend" class="h-5 w-5 text-brand-600" /> Money received, last 6 months</h2>
                <span class="text-sm text-slate-500">Total {{ rs($trend->sum('amount')) }}</span>
            </div>
            <div class="relative mt-5 h-52" role="img" aria-label="Money received per month: {{ $trend->map(fn ($b) => $b['label'].' '.rs($b['amount']))->implode(', ') }}">
                <div class="absolute inset-x-0 top-0 bottom-6 flex flex-col justify-between" aria-hidden="true">@foreach (range(1, 4) as $line)<div class="border-t border-dashed border-slate-200"></div>@endforeach</div>
                <div class="relative flex h-full items-end gap-3 sm:gap-5">
                    @foreach ($trend as $bar)
                        <div class="group flex h-full flex-1 flex-col items-center justify-end gap-1.5" title="{{ $bar['label'] }}: {{ rs($bar['amount']) }}">
                            <div class="text-[11px] font-semibold text-slate-600">{{ $bar['amount'] >= 1000 ? round($bar['amount'] / 1000).'k' : $bar['amount'] }}</div>
                            <div @class(['w-full max-w-16 rounded-t-lg transition group-hover:opacity-80', 'bg-gradient-to-t from-brand-600 to-brand-500' => ! $loop->last, 'bg-gradient-to-t from-accent-500 to-amber-400' => $loop->last]) style="height: {{ max(2, round($bar['amount'] / $max * 78)) }}%"></div>
                            <div @class(['text-xs', 'font-semibold text-slate-900' => $loop->last, 'text-slate-500' => ! $loop->last])>{{ $bar['label'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="card-pad">
            <div class="flex items-baseline justify-between">
                <h2 class="panel-title"><x-icon name="warning" class="h-5 w-5 text-red-600" /> Most late</h2>
                <a class="text-xs font-medium text-brand-600 hover:underline" href="{{ route('biz.due') }}" wire:navigate>All</a>
            </div>
            <ul class="mt-3 space-y-2">
                @forelse ($topLate as $c)
                    <li><a class="flex items-center gap-3 rounded-lg p-1.5 hover:bg-slate-50" href="{{ route('biz.contract', $c) }}" wire:navigate>
                        <div class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-red-50 text-xs font-bold text-red-700">{{ \App\Support\IdCards::initials($c->customer->name) }}</div>
                        <div class="min-w-0 flex-1"><div class="truncate text-sm font-medium">{{ $c->customer->name }}</div><div class="truncate text-xs text-slate-500">{{ $c->daysLate() }} days · {{ rs($c->overdue()) }}</div></div>
                    </a></li>
                @empty
                    <li class="flex items-center gap-2 text-sm text-slate-500"><x-icon name="check-circle" class="h-5 w-5 text-emerald-600" /> Nobody is late.</li>
                @endforelse
            </ul>
        </div>

        <div class="card-pad">
            <h2 class="panel-title"><x-icon name="calendar" class="h-5 w-5 text-amber-600" /> Due today</h2>
            <ul class="mt-3 divide-y divide-slate-100">
                @forelse ($dueToday as $c)
                    <li class="flex items-center justify-between gap-2 py-2 text-sm">
                        <a class="min-w-0 truncate hover:underline" href="{{ route('biz.contract', $c) }}" wire:navigate>{{ $c->customer->name }}</a>
                        <span class="font-medium">{{ rs($c->nextDue()->amount - $c->nextDue()->paid) }}</span>
                    </li>
                @empty
                    <li class="py-2 text-sm text-slate-500">Nothing due today.</li>
                @endforelse
            </ul>
        </div>

        <div class="card-pad lg:col-span-2">
            <h2 class="panel-title"><x-icon name="receipt" class="h-5 w-5 text-emerald-600" /> Latest receipts</h2>
            <ul class="mt-3 divide-y divide-slate-100">
                @forelse ($recent as $r)
                    <li class="flex items-center justify-between gap-3 py-2 text-sm">
                        <div class="min-w-0"><span class="font-medium">{{ $r->contract->customer->name }}</span> <span class="text-xs text-slate-500">{{ $r->number }} · {{ $r->date->format('j M') }}{{ $r->is_down_payment ? ' · down payment' : '' }}</span></div>
                        <div class="flex items-center gap-2"><span class="font-semibold">{{ rs($r->total()) }}</span><a class="btn btn-sm" href="{{ route('biz.print.receipt', $r) }}" target="_blank" title="Print"><x-icon name="receipt" class="h-3.5 w-3.5" /></a></div>
                    </li>
                @empty
                    <li class="py-2 text-sm text-slate-500">No receipts yet.</li>
                @endforelse
            </ul>
        </div>
    </div>
</div>
