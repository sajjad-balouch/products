<div x-data x-on:open-whatsapp.window="window.open($event.detail.url, '_blank')">
    <x-page-header :title="$c->number.' · '.$c->item" :subtitle="$c->customer->name.' · sold '.$c->sale_date->format('j M Y').' · '.\App\Models\Biz\Contract::FREQUENCIES[$c->frequency]">
        <a class="btn" href="{{ route('biz.print.agreement', $c) }}" target="_blank"><x-icon name="file-plus" class="h-4 w-4" /> Agreement</a>
        <a class="btn" href="{{ route('biz.print.statement', $c) }}" target="_blank"><x-icon name="list" class="h-4 w-4" /> Statement</a>
        @if ($c->isOpen())
            <button class="btn" wire:click="remind"><x-icon name="message" class="h-4 w-4" /> Remind</button>
            <button class="btn btn-primary" wire:click="$set('collect', true)"><x-icon name="cash" class="h-4 w-4" /> Take payment</button>
        @endif
    </x-page-header>

    @if ($lastReceipt)
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl bg-emerald-50 p-3 text-sm text-emerald-900 ring-1 ring-emerald-200">
            <span class="flex items-center gap-2"><x-icon name="check-circle" class="h-5 w-5" /> Receipt <strong>{{ $lastReceipt->number }}</strong> for {{ rs($lastReceipt->total()) }} saved.</span>
            <a class="btn btn-sm" href="{{ route('biz.print.receipt', $lastReceipt) }}" target="_blank"><x-icon name="receipt" class="h-3.5 w-3.5" /> Print receipt</a>
        </div>
    @endif

    <div class="mb-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-5">
        <x-stat icon="tag" tone="violet" label="Total price" :value="rs($c->total_price)" :note="'Cash '.rs($c->cash_price).' + markup '.rs($c->markup)" />
        <x-stat icon="cash" tone="green" label="Paid" :value="rs($paid + $c->down_payment)" :note="'Down payment '.rs($c->down_payment)" />
        <x-stat icon="wallet" tone="brand" label="Still owed" :value="rs($outstanding)" :note="$c->schedule->filter(fn ($s) => $s->paid < $s->amount)->count().' installments left'" />
        <x-stat icon="alert-circle" :tone="$overdue ? 'red' : 'green'" label="Overdue" :value="rs($overdue)" :note="$c->isOpen() && $c->daysLate() ? $c->daysLate().' days late' : 'Nothing late'" />
        <x-stat icon="calendar" tone="amber" label="Next due" :value="$next ? rs($next->amount - $next->paid) : '—'" :note="$next ? $next->due_on->format('D, j M Y') : 'All paid'" />
    </div>

    <div class="grid gap-4 xl:grid-cols-3">
        <div class="space-y-4 xl:col-span-2">
            <div class="card overflow-x-auto">
                <div class="flex items-center justify-between border-b border-slate-200 px-4 py-3">
                    <h2 class="text-sm font-semibold">Installments</h2>
                    <x-biz-status :contract="$c" />
                </div>
                <table class="tbl">
                    <thead><tr><th>#</th><th>Due</th><th class="text-end">Amount</th><th class="text-end">Paid</th><th>Status</th></tr></thead>
                    <tbody>
                    @foreach ($c->schedule as $item)
                        @php($st = $item->status())
                        <tr wire:key="s-{{ $item->id }}" @class(['bg-red-50/50' => $st === 'late' && $c->isOpen()])>
                            <td class="text-slate-500">{{ $item->number }}</td>
                            <td>{{ $item->due_on->format('j M Y') }}</td>
                            <td class="text-end">{{ rs($item->amount) }}</td>
                            <td class="text-end">{{ $item->paid ? rs($item->paid) : '—' }}</td>
                            <td>
                                @switch($st)
                                    @case('paid')<span class="badge badge-green">Paid {{ $item->paid_on?->format('j M') }}</span>@break
                                    @case('late')<span class="badge badge-red">{{ $item->paid ? 'Part paid, late' : 'Late' }}</span>@break
                                    @case('part')<span class="badge badge-amber">Part paid</span>@break
                                    @case('today')<span class="badge badge-amber">Due today</span>@break
                                    @default<span class="badge badge-slate">Upcoming</span>
                                @endswitch
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <div class="card overflow-x-auto">
                <div class="border-b border-slate-200 px-4 py-3 text-sm font-semibold">Receipts</div>
                <table class="tbl">
                    <thead><tr><th>Receipt</th><th>Date</th><th class="text-end">Amount</th><th>By</th><th></th></tr></thead>
                    <tbody>
                    @forelse ($c->receipts as $r)
                        <tr wire:key="r-{{ $r->id }}" @class(['opacity-50' => $r->voided_at])>
                            <td>
                                <span class="font-medium">{{ $r->number }}</span>
                                @if ($r->is_down_payment)<span class="badge badge-blue ms-1">Down payment</span>@endif
                                @if ($r->voided_at)<span class="badge badge-red ms-1">Cancelled</span><div class="text-xs text-slate-500">{{ $r->void_reason }}</div>@endif
                            </td>
                            <td>{{ $r->date->format('j M Y') }}<div class="text-xs text-slate-500">{{ \App\Models\Biz\Receipt::METHODS[$r->method] ?? $r->method }}{{ $r->reference ? ' · '.$r->reference : '' }}</div></td>
                            <td class="text-end">{{ rs($r->total()) }}
                                @if ($r->late_fee || $r->discount)<div class="text-xs text-slate-500">{{ $r->late_fee ? 'incl. late fee '.rs($r->late_fee) : '' }}{{ $r->discount ? ' discount '.rs($r->discount) : '' }}</div>@endif
                            </td>
                            <td class="text-xs">{{ $r->receiver?->name }}</td>
                            <td class="text-end whitespace-nowrap">
                                <a class="btn btn-sm" href="{{ route('biz.print.receipt', $r) }}" target="_blank" title="Print"><x-icon name="receipt" class="h-3.5 w-3.5" /></a>
                                @if (! $r->voided_at && ! $r->is_down_payment && $c->status !== 'written_off')
                                    <button class="btn btn-sm" wire:click="$set('voidId', {{ $r->id }})" title="Cancel receipt"><x-icon name="x-circle" class="h-3.5 w-3.5 text-red-600" /></button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-6 text-center text-slate-500">No payments yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="space-y-4">
            <div class="card-pad text-sm">
                <h2 class="panel-title"><x-icon name="users" class="h-5 w-5 text-brand-600" /> Customer</h2>
                <a class="mt-2 block font-semibold text-brand-600 hover:underline" href="{{ route('biz.customer', $c->customer) }}" wire:navigate>{{ $c->customer->name }}</a>
                <div class="text-slate-600">{{ $c->customer->phone }}{{ $c->customer->cnic ? ' · '.$c->customer->cnic : '' }}</div>
                <div class="text-slate-500">{{ $c->customer->address }}</div>
                @foreach ($c->guarantors as $i => $g)
                    <div class="mt-3 border-t border-slate-100 pt-3">
                        <div class="text-xs text-slate-500">Guarantor {{ $i + 1 }}{{ $g->relation ? ' · '.$g->relation : '' }}</div>
                        <div class="font-medium">{{ $g->name }}</div>
                        <div class="text-slate-600">{{ $g->phone }}{{ $g->cnic ? ' · '.$g->cnic : '' }}</div>
                    </div>
                @endforeach
            </div>
            <div class="card-pad text-sm">
                <h2 class="panel-title"><x-icon name="tag" class="h-5 w-5 text-violet-600" /> Item</h2>
                <div class="mt-2 font-medium">@if ($c->product_id)<a class="text-brand-600 hover:underline" href="{{ route('biz.product', $c->product_id) }}" wire:navigate>{{ $c->item }}</a>@else{{ $c->item }}@endif</div>
                @if ($c->item_details)<div class="whitespace-pre-line text-slate-600">{{ $c->item_details }}</div>@endif
                <dl class="mt-3 space-y-1">
                    <div class="flex justify-between"><dt class="text-slate-500">Markup</dt><dd>{{ $c->markup_percent ? rtrim(rtrim(number_format($c->markup_percent, 2), '0'), '.').'%' : '' }} {{ rs($c->markup) }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Late fee</dt><dd>{{ $c->late_fee ? rs($c->late_fee).' per late installment' : 'None' }}</dd></div>
                    <div class="flex justify-between"><dt class="text-slate-500">Grace days</dt><dd>{{ $c->grace_days }}</dd></div>
                    @if ($c->cost_price !== null)
                        <div class="flex justify-between"><dt class="text-slate-500">Cost of this piece</dt><dd>{{ rs($c->cost_price) }}</dd></div>
                        <div class="flex justify-between"><dt class="text-slate-500">Profit on sale</dt><dd @class(['font-semibold', 'text-emerald-700' => $c->grossProfit() >= 0, 'text-red-700' => $c->grossProfit() < 0])>{{ rs($c->grossProfit()) }}</dd></div>
                    @endif
                </dl>
                @if ($c->notes)<p class="mt-3 whitespace-pre-line text-slate-600">{{ $c->notes }}</p>@endif
                @if ($isAdmin && $c->isOpen() && $outstanding > 0)
                    <button class="mt-4 text-xs text-red-600 hover:underline" wire:click="$set('showWriteOff', true)">Write off as bad debt…</button>
                @endif
            </div>
        </div>
    </div>

    @if ($collect && $c->isOpen())
        <x-modal :title="'Take payment · '.$c->customer->name" close="$set('collect', false)">
            <form wire:submit="takePayment" class="space-y-3">
                <div class="rounded-lg bg-slate-50 p-3 text-sm">
                    Owed <strong>{{ rs($outstanding) }}</strong> · overdue <strong @class(['text-red-700' => $overdue])>{{ rs($overdue) }}</strong>
                    @if ($c->lateInstallments()) · {{ $c->lateInstallments() }} late {{ str('installment')->plural($c->lateInstallments()) }}@endif
                </div>
                <div class="grid gap-3 sm:grid-cols-2">
                    <x-field label="Amount for installments (Rs)" name="amount"><input id="amount" type="number" min="0" class="input text-lg font-semibold" wire:model.live.debounce.400ms="amount" autofocus></x-field>
                    <x-field label="Late fee (Rs)" name="lateFee" :hint="$c->late_fee ? rs($c->late_fee).' × late installments' : null"><input id="lateFee" type="number" min="0" class="input" wire:model.live.debounce.400ms="lateFee"></x-field>
                    <x-field label="Paid by" name="method"><select id="method" class="input" wire:model.live="method">@foreach (\App\Models\Biz\Receipt::METHODS as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></x-field>
                    <x-field label="Date" name="date"><input id="date" type="date" class="input" wire:model.live="date"></x-field>
                    <x-field label="Reference" name="reference" hint="Transaction or cheque number"><input id="reference" class="input" wire:model="reference"></x-field>
                    <x-field label="Discount (Rs)" name="discount" hint="Taken off what is owed, e.g. for early payment"><input id="discount" type="number" min="0" class="input" wire:model.live.debounce.400ms="discount"></x-field>
                    <x-field label="Note" name="note" class="sm:col-span-2"><input id="note" class="input" wire:model="note"></x-field>
                </div>
                <x-biz.impact :impact="$impact" />
                <x-biz.guide key="collect" />
                <div class="flex justify-end gap-2">
                    <button type="button" class="btn" wire:click="$set('collect', false)">Cancel</button>
                    <button class="btn btn-primary" wire:loading.attr="disabled">Save and make receipt</button>
                </div>
            </form>
        </x-modal>
    @endif

    @if ($voidId)
        <x-modal title="Cancel receipt" close="$set('voidId', null)" size="sm">
            <form wire:submit="voidReceipt" class="space-y-3">
                <p class="text-sm text-slate-600">The receipt stays on record, marked cancelled. Its accounting entry is reversed and the installments are worked out again.</p>
                <x-field label="Reason" name="voidReason"><input id="voidReason" class="input" wire:model="voidReason" placeholder="Entered twice, wrong amount…"></x-field>
                <x-biz.impact :impact="$impact" />
                <x-biz.guide key="void_receipt" />
                <div class="flex justify-end gap-2">
                    <button type="button" class="btn" wire:click="$set('voidId', null)">Keep it</button>
                    <button class="btn btn-danger" wire:loading.attr="disabled">Cancel receipt</button>
                </div>
            </form>
        </x-modal>
    @endif

    @if ($showWriteOff)
        <x-modal title="Write off as bad debt" close="$set('showWriteOff', false)" size="sm">
            <form wire:submit="writeOff" class="space-y-3">
                <p class="text-sm text-slate-600">{{ rs($outstanding) }} is still owed. It is moved to "Bad debts written off" and the plan is closed. The unearned markup on it is cancelled.</p>
                <x-field label="Reason" name="writeOffReason"><input id="writeOffReason" class="input" wire:model="writeOffReason" placeholder="Customer moved away, item not recovered…"></x-field>
                <x-biz.impact :impact="$impact" />
                <x-biz.guide key="write_off" />
                <div class="flex justify-end gap-2">
                    <button type="button" class="btn" wire:click="$set('showWriteOff', false)">Cancel</button>
                    <button class="btn btn-danger" wire:loading.attr="disabled">Write off</button>
                </div>
            </form>
        </x-modal>
    @endif
</div>
