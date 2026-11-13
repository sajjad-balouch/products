<div>
    <x-page-header :title="$v->name" :subtitle="$v->code.($v->company ? ' · '.$v->company : '').($v->phone ? ' · '.$v->phone : '')">
        <a class="btn" href="{{ route('biz.vendors') }}" wire:navigate>All vendors</a>
        <button class="btn" wire:click="open('opening')">Old balance · Purana baqaya</button>
        <button class="btn" wire:click="open('loan')">Cash borrowed · Cash udhaar liya</button>
        <button class="btn" wire:click="open('bill')">Their bill · Bill</button>
        <button class="btn btn-primary" wire:click="open('pay')"><x-icon name="wallet" class="h-4 w-4" /> Pay vendor · Payment</button>
    </x-page-header>

    <div class="mb-4 grid gap-3 sm:grid-cols-3">
        <x-stat label="You owe now · Ab dena hai" :value="rs($balance)" :tone="$balance > 0 ? 'red' : 'green'" icon="wallet" />
        <x-stat label="Stock bought from them" :value="rs($bought)" icon="package" />
        <x-stat label="Entries" :value="number_format($rows->count())" icon="list" />
    </div>

    <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_24rem]">
        <section class="card">
            <h2 class="panel-title border-b border-slate-200 p-3">Account · Khata</h2>
            <div class="overflow-x-auto">
                <table class="tbl">
                    <thead><tr><th>Date</th><th>Detail</th><th class="text-end">Owed more · Dena barha</th><th class="text-end">Paid · Diye</th><th class="text-end">Paid at once</th><th class="text-end">Cash taken</th><th class="text-end">You owe</th></tr></thead>
                    <tbody>
                    @forelse ($rows as $r)
                        <tr>
                            <td class="whitespace-nowrap">{{ \Illuminate\Support\Carbon::parse($r->date)->format('j M Y') }}<div class="font-mono text-xs text-slate-500">{{ $r->number }}</div></td>
                            <td class="text-sm">{{ $r->memo }}</td>
                            <td class="text-end">{{ $r->owed_more ? number_format($r->owed_more) : '' }}</td>
                            <td class="text-end text-emerald-700">{{ $r->paid ? number_format($r->paid) : '' }}</td>
                            <td class="text-end text-slate-500">{{ $r->paid_now ? number_format($r->paid_now) : '' }}</td>
                            <td class="text-end text-slate-500">{{ $r->received ? number_format($r->received) : '' }}</td>
                            <td class="text-end font-semibold">{{ rs($r->balance) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="p-6 text-center text-slate-500">Nothing yet. Stock in from this vendor and payments to them show here.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </section>
        <section class="card">
            <h2 class="panel-title border-b border-slate-200 p-3">Stock bought</h2>
            <ul class="divide-y divide-slate-100 text-sm">
                @forelse ($purchases as $p)
                    <li class="flex justify-between gap-3 p-3">
                        <span>{{ $p->quantity }} × {{ $p->product?->label() }}<span class="block text-xs text-slate-500">{{ $p->date->format('j M Y') }} · {{ $p->number }} · {{ $p->paymentLabel() }}</span></span>
                        <span class="font-medium">{{ rs($p->total()) }}</span>
                    </li>
                @empty
                    <li class="p-4 text-slate-500">No stock bought yet. Choose this vendor on Products → Stock in.</li>
                @endforelse
            </ul>
        </section>
    </div>

    @if ($action)
        @php($boxes = ['pay' => ['Pay '.$v->name, 'vendor_pay', 'Amount paid (Rs) · Raqam'], 'opening' => ['Old balance owed to '.$v->name, 'vendor_opening', 'Amount owed (Rs) · Raqam'],
            'loan' => ['Cash borrowed from '.$v->name, 'vendor_loan', 'Cash taken (Rs) · Raqam'], 'bill' => ['Bill from '.$v->name, 'vendor_bill', 'Bill total (Rs) · Bill ki raqam']][$action])
        <x-modal :title="$boxes[0]" close="$set('action', null)">
            <form wire:submit="save" class="space-y-3">
                <div class="grid gap-3 sm:grid-cols-2">
                    <x-field :label="$boxes[2]" name="amount"><input id="amount" type="number" min="1" class="input text-lg font-semibold" wire:model.live.debounce.400ms="amount"></x-field>
                    <x-field label="Date · Tareekh" name="date"><input id="date" type="date" class="input" wire:model.live="date"></x-field>
                    @if ($action === 'bill')
                        <x-field label="What was it for · Kis cheez ka" name="expense">
                            <select id="expense" class="input" wire:model.live="expense"><option value="">Choose · Chunein…</option>@foreach ($expenses as $a)<option value="{{ $a->id }}">{{ $a->name }}</option>@endforeach</select>
                        </x-field>
                        <x-field label="Paid now (Rs) · Abhi diye" name="paidNow" :hint="(int) $amount > (int) $paidNow ? rs((int) $amount - (int) $paidNow).' baqi: you will owe '.$v->name : 'Paid in full'"><input id="paidNow" type="number" min="0" class="input" wire:model.live.debounce.400ms="paidNow"></x-field>
                    @endif
                    @if (in_array($action, ['pay', 'loan'], true) || ($action === 'bill' && (int) $paidNow > 0))
                        <div>
                            <div class="label">{{ $action === 'loan' ? 'Went into · Kahan aaye' : 'Paid from · Kahan se diye' }}</div>
                            <div class="flex rounded-lg border border-slate-300 bg-white p-0.5">
                                @foreach (['cash' => 'Cash', 'bank' => 'Bank'] as $k => $label)
                                    <button type="button" wire:click="$set('money', '{{ $k }}')" @class(['flex-1 rounded-md px-3 py-1.5 text-sm', 'bg-brand-600 text-white' => $money === $k, 'text-slate-600' => $money !== $k])>{{ $label }}</button>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    <x-field label="Detail (optional)" name="note"><input id="note" class="input" wire:model="note"></x-field>
                </div>
                <x-biz.impact :impact="$impact" />
                <x-biz.guide :key="$boxes[1]" />
                <div class="flex justify-end gap-2">
                    <button type="button" class="btn" wire:click="$set('action', null)">Cancel</button>
                    <button class="btn btn-primary" wire:loading.attr="disabled">Save</button>
                </div>
            </form>
        </x-modal>
    @endif
</div>
