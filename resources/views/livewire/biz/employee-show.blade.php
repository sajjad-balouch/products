<div>
    <x-page-header :title="$e->name" :subtitle="$e->code.($e->designation ? ' · '.$e->designation : '').($e->phone ? ' · '.$e->phone : '')">
        <a class="btn" href="{{ route('biz.employees') }}" wire:navigate>All employees</a>
        <button class="btn" wire:click="open('advance')">Give advance · Advance</button>
        @if ($e->is_salesman)<button class="btn" wire:click="open('commission')">Pay commission</button>@endif
        <button class="btn btn-primary" wire:click="open('salary')"><x-icon name="wallet" class="h-4 w-4" /> Pay salary · Tankhwah</button>
    </x-page-header>

    <div class="mb-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat label="Monthly salary" :value="rs($e->salary)" icon="wallet" />
        <x-stat label="Salary paid this month" :value="rs($paidMonth)" icon="check-circle" :tone="$paidMonth >= $e->salary && $e->salary ? 'green' : 'slate'" />
        <x-stat label="Advance owed · Advance baqi" :value="rs($advance)" icon="alert-circle" :tone="$advance > 0 ? 'amber' : 'slate'" />
        @if ($sales)
            <x-stat label="Sales this month" :value="rs($sales['total'])" :note="$sales['bills'].' bills · '.$sales['plans'].' plans'" icon="trend" />
        @endif
    </div>

    @if ($sales)
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 p-3 text-sm text-emerald-900">
            Commission this month: <strong>{{ rs($sales['commission']) }}</strong> earned at {{ rtrim(rtrim(number_format($e->commission_percent, 2), '0'), '.') }}% · <strong>{{ rs($commissionPaid) }}</strong> paid · <strong>{{ rs(max(0, $sales['commission'] - $commissionPaid)) }}</strong> still to pay.
        </div>
    @endif

    {{-- The salesman phone app --}}
    @php($login = $e->user)
    <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-brand-200 bg-brand-50 p-3 text-sm">
        <div class="flex items-center gap-3">
            <img src="{{ asset('app-icons/icon-192.png') }}" alt="" class="h-10 w-10 rounded-xl">
            <div>
                <div class="font-semibold text-slate-900">Sales app · {{ config('services.sales_app.name') }}</div>
                @if ($login)
                    <div class="text-slate-600">Login <strong class="font-mono">{{ $login->username }}</strong> · {{ $login->is_active ? 'can take orders on the phone' : 'switched off' }}</div>
                @else
                    <div class="text-slate-600">Give {{ $e->name }} a login to take orders on the phone, even offline.</div>
                @endif
            </div>
        </div>
        <div class="flex gap-2">
            @if ($login && ! $login->is_active)<button class="btn btn-sm" wire:click="switchOnLogin">Switch on</button>@endif
            <a class="btn btn-sm" href="{{ route('app') }}" target="_blank">Open the app</a>
            <button class="btn btn-sm btn-primary" wire:click="openLogin">{{ $login ? 'Change login / password' : 'Give app login' }}</button>
        </div>
    </div>
    <div @class(['grid gap-4', 'xl:grid-cols-2' => $e->is_salesman])>
        <section class="card">
            <h2 class="panel-title border-b border-slate-200 p-3">Payments · Adaigiyan</h2>
            <div class="overflow-x-auto">
                <table class="tbl">
                    <thead><tr><th>Date</th><th>Detail</th><th class="text-end">Salary</th><th class="text-end">Advance</th><th class="text-end">Commission</th></tr></thead>
                    <tbody>
                    @forelse ($history as $h)
                        <tr>
                            <td class="whitespace-nowrap">{{ \Illuminate\Support\Carbon::parse($h->date)->format('j M Y') }}<div class="font-mono text-xs text-slate-500">{{ $h->number }}</div></td>
                            <td class="text-sm">{{ $h->memo }}</td>
                            <td class="text-end">{{ $h->salary ? number_format($h->salary) : '' }}</td>
                            <td @class(['text-end', 'text-amber-700' => $h->advance > 0, 'text-emerald-700' => $h->advance < 0])>{{ $h->advance ? ($h->advance > 0 ? '+' : '−').number_format(abs($h->advance)) : '' }}</td>
                            <td class="text-end">{{ $h->commission ? number_format($h->commission) : '' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-6 text-center text-slate-500">No payments yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
            <p class="border-t border-slate-100 p-3 text-xs text-slate-500">Advance: + given, − taken back from salary.</p>
        </section>
        @if ($e->is_salesman)
            <section class="card">
                <h2 class="panel-title border-b border-slate-200 p-3">Latest sales by {{ $e->name }}</h2>
                <ul class="divide-y divide-slate-100 text-sm">
                    @foreach ($bills as $b)
                        <li class="flex justify-between gap-3 p-3"><a class="text-brand-600 hover:underline" href="{{ route('biz.sale', $b) }}" wire:navigate>{{ $b->number }}</a><span class="text-slate-500">{{ $b->date->format('j M') }}</span><span class="font-medium">{{ rs($b->total) }}</span></li>
                    @endforeach
                    @foreach ($plans as $p)
                        <li class="flex justify-between gap-3 p-3"><a class="text-brand-600 hover:underline" href="{{ route('biz.contract', $p) }}" wire:navigate>{{ $p->number }} · {{ $p->customer->name }}</a><span class="text-slate-500">{{ $p->sale_date->format('j M') }}</span><span class="font-medium">{{ rs($p->cash_price) }}</span></li>
                    @endforeach
                    @if ($bills->isEmpty() && $plans->isEmpty())<li class="p-4 text-slate-500">No sales yet. Choose {{ $e->name }} as the salesman on a sale.</li>@endif
                </ul>
            </section>
        @endif
    </div>

    @if ($action)
        @php($titles = ['salary' => 'Pay salary to '.$e->name, 'advance' => 'Advance to '.$e->name, 'commission' => 'Pay commission to '.$e->name])
        <x-modal :title="$titles[$action]" close="$set('action', null)">
            <form wire:submit="save" class="space-y-3">
                <div class="grid gap-3 sm:grid-cols-2">
                    <x-field :label="$action === 'salary' ? 'Salary (Rs) · Tankhwah' : 'Amount (Rs) · Raqam'" name="amount"><input id="amount" type="number" min="1" class="input text-lg font-semibold" wire:model.live.debounce.400ms="amount"></x-field>
                    @if ($action === 'salary')
                        <x-field label="For month · Mahina" name="month"><input id="month" type="month" class="input" wire:model.live="month"></x-field>
                        @if ($advance > 0)
                            <x-field label="Take back from advance (Rs)" name="deduct" :hint="'Owes '.rs($advance).' in advances'"><input id="deduct" type="number" min="0" class="input" wire:model.live.debounce.400ms="deduct"></x-field>
                        @endif
                    @endif
                    <div>
                        <div class="label">Paid from · Kahan se diye</div>
                        <div class="flex rounded-lg border border-slate-300 bg-white p-0.5">
                            @foreach (['cash' => 'Cash', 'bank' => 'Bank'] as $k => $label)
                                <button type="button" wire:click="$set('money', '{{ $k }}')" @class(['flex-1 rounded-md px-3 py-1.5 text-sm', 'bg-brand-600 text-white' => $money === $k, 'text-slate-600' => $money !== $k])>{{ $label }}</button>
                            @endforeach
                        </div>
                    </div>
                    <x-field label="Date · Tareekh" name="date"><input id="date" type="date" class="input" wire:model.live="date"></x-field>
                    <x-field label="Detail (optional)" name="note"><input id="note" class="input" wire:model="note"></x-field>
                </div>
                @if ($action === 'salary' && (int) $deduct > 0)
                    <p class="rounded-lg bg-slate-50 p-2.5 text-sm">Hand over <strong>{{ rs(max(0, (int) $amount - (int) $deduct)) }}</strong> · {{ rs((int) $deduct) }} taken back from the advance.</p>
                @endif
                <x-biz.impact :impact="$impact" />
                <x-biz.guide :key="$action" />
                <div class="flex justify-end gap-2">
                    <button type="button" class="btn" wire:click="$set('action', null)">Cancel</button>
                    <button class="btn btn-primary" wire:loading.attr="disabled">Save</button>
                </div>
            </form>
        </x-modal>
    @endif

    @if ($showLogin)
        <x-modal :title="'Sales app login for '.$e->name" close="$set('showLogin', false)">
            <form wire:submit="saveLogin" class="space-y-3">
                <p class="rounded-lg bg-sky-50 p-2.5 text-sm text-slate-700">{{ $e->name }} opens <strong>{{ route('app') }}</strong> (or the Play Store app), signs in with this login, and takes orders. Orders made with no internet are sent by themselves later. · Is login se app mein order lein.</p>
                <x-field label="Login (phone or email)" name="loginUsername"><input id="loginUsername" class="input" wire:model="loginUsername" autocomplete="off"></x-field>
                <x-field :label="$e->user ? 'New password (leave empty to keep the old one)' : 'Password (at least 6)'" name="loginPassword"><input id="loginPassword" class="input" wire:model="loginPassword" autocomplete="new-password"></x-field>
                <div class="flex flex-wrap justify-between gap-2">
                    @if ($e->user && $e->user->is_active)<button type="button" class="btn btn-danger" wire:click="switchOffLogin" wire:confirm="Switch off this login and sign their phone out?">Switch off login</button>@else<span></span>@endif
                    <div class="flex gap-2"><button type="button" class="btn" wire:click="$set('showLogin', false)">Cancel</button><button class="btn btn-primary">Save login</button></div>
                </div>
            </form>
        </x-modal>
    @endif
</div>
