<div>
    <x-page-header title="Vouchers and collection" subtitle="Find a family by child's name, phone or voucher number, then record the payment.">
        <a class="btn btn-primary" href="{{ route('fees.generate') }}" wire:navigate>Generate vouchers</a>
    </x-page-header>

    <div class="card overflow-hidden">
        <div class="flex flex-wrap gap-2 border-b border-slate-200 p-3">
            <input class="input max-w-xs" type="search" placeholder="Search" aria-label="Search" wire:model.live.debounce.300ms="search">
            <input class="input w-auto" type="month" aria-label="Month" wire:model.live="month">
            <select class="input w-auto" aria-label="Status" wire:model.live="status">
                <option value="">All statuses</option>
                <option value="unpaid">Unpaid</option><option value="partial">Partial</option><option value="paid">Paid</option><option value="void">Void</option>
            </select>
        </div>
        <div class="overflow-x-auto"><table class="tbl">
            <thead><tr><th>Voucher</th><th>Month</th><th>Family</th><th>Children</th><th>Due</th><th>Total</th><th>Balance</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse ($vouchers as $v)
                <tr wire:key="v-{{ $v->id }}">
                    <td class="font-mono text-xs">{{ $v->voucher_no }}</td>
                    <td>{{ $v->month->format('M Y') }}</td>
                    <td>{{ $v->guardian->name }}<div class="text-xs text-slate-500">{{ $v->guardian->phone }}</div></td>
                    <td class="max-w-48 truncate">{{ $v->guardian->students->pluck('name')->join(', ') }}</td>
                    <td @class(['font-medium text-red-700' => $v->due_on->isPast() && in_array($v->status, ['unpaid', 'partial'])])>{{ $v->due_on->format('j M') }}</td>
                    <td>{{ number_format($v->total) }}</td>
                    <td class="font-medium">{{ number_format($v->balance) }}</td>
                    <td>@include('partials.voucher-status', ['status' => $v->status])</td>
                    <td class="text-end whitespace-nowrap">
                        <button class="btn btn-sm btn-primary" wire:click="open({{ $v->id }})">Open</button>
                        <a class="btn btn-sm" href="{{ route('print.voucher', $v->id) }}" target="_blank">Print</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="py-10 text-center text-slate-500">No vouchers found.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </div>
    <div class="mt-3">{{ $vouchers->links() }}</div>

    @if ($current)
        <x-modal :title="'Voucher '.$current->voucher_no" close="$set('openId', null)">
            <div class="space-y-4 text-sm">
                <div>
                    <div class="font-medium">{{ $current->guardian->name }} · {{ $current->month->format('F Y') }}</div>
                    <table class="mt-2 w-full">
                        @foreach ($current->lines as $line)
                            <tr><td class="py-0.5 text-slate-600">{{ $line->student?->name }} · {{ $line->label }}</td><td class="ps-3 text-end whitespace-nowrap">{{ number_format($line->amount) }}</td></tr>
                        @endforeach
                        @if ($current->late_fee)<tr><td class="py-0.5 text-slate-600">Late fee</td><td class="ps-3 text-end whitespace-nowrap">{{ number_format($current->late_fee) }}</td></tr>@endif
                        <tr class="border-t font-semibold"><td class="pt-1">Total</td><td class="ps-3 pt-1 text-end whitespace-nowrap">Rs {{ number_format($current->total) }}</td></tr>
                        <tr class="font-semibold"><td>Balance</td><td class="ps-3 text-end whitespace-nowrap">Rs {{ number_format($current->balance) }}</td></tr>
                    </table>
                </div>

                @error('action')<p class="rounded-lg bg-red-50 p-2 text-red-800">{{ $message }}</p>@enderror

                @if ($current->balance > 0)
                    <form wire:submit="collect" class="grid gap-2 rounded-lg bg-slate-50 p-3 sm:grid-cols-2">
                        <x-field label="Amount (Rs)" name="amount"><input id="amount" class="input" type="number" min="1" max="{{ $current->balance }}" wire:model="amount"></x-field>
                        <x-field label="Method" name="method">
                            <select id="method" class="input" wire:model="method">
                                <option value="cash">Cash</option><option value="bank">Bank</option><option value="jazzcash">JazzCash</option><option value="easypaisa">Easypaisa</option><option value="raast">Raast</option><option value="cheque">Cheque</option>
                            </select>
                        </x-field>
                        <x-field label="Reference" name="reference"><input id="reference" class="input" wire:model="reference"></x-field>
                        <x-field label="Date" name="paid_on"><input id="paid_on" class="input" type="date" wire:model="paid_on"></x-field>
                        <button class="btn btn-primary sm:col-span-2" wire:loading.attr="disabled">Record payment</button>
                    </form>
                @endif

                @if ($current->payments->isNotEmpty())
                    <div>
                        <div class="mb-1 font-medium">Payments</div>
                        @foreach ($current->payments as $p)
                            <div class="flex items-center justify-between gap-2 border-t border-slate-100 py-1.5" wire:key="p-{{ $p->id }}">
                                <div @class(['text-slate-400 line-through' => $p->voided_at])>
                                    {{ $p->receipt_no }} · Rs {{ number_format($p->amount) }} · {{ ucfirst($p->method) }} · {{ $p->paid_on->format('j M') }}
                                    <div class="text-xs text-slate-500">{{ $p->receivedBy?->name }}{{ $p->voided_at ? ' · Void: '.$p->void_reason : '' }}</div>
                                </div>
                                @unless ($p->voided_at)
                                    <div class="whitespace-nowrap">
                                        <a class="btn btn-sm" href="{{ route('print.receipt', $p->id) }}" target="_blank">Receipt</a>
                                        <button class="btn btn-sm btn-danger" wire:click="voidPayment({{ $p->id }})" wire:confirm="Void this payment? Enter the reason below first.">Void</button>
                                    </div>
                                @endunless
                            </div>
                        @endforeach
                    </div>
                @endif

                @if ($current->status !== 'void')
                    <div class="rounded-lg border border-slate-200 p-3">
                        <x-field label="Reason for a correction" name="reason" hint="Required to void a payment, void the voucher or waive a late fee. Every correction is saved in the audit log.">
                            <input id="reason" class="input" wire:model="reason">
                        </x-field>
                        <div class="mt-2 flex gap-2">
                            @if ($current->late_fee)<button class="btn btn-sm" wire:click="waive">Waive late fee</button>@endif
                            <button class="btn btn-sm btn-danger" wire:click="voidVoucher" wire:confirm="Void this whole voucher?">Void voucher</button>
                        </div>
                    </div>
                @endif
            </div>
        </x-modal>
    @endif
</div>
