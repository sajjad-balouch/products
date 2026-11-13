<div>
    <x-page-header :title="$customer->name" :subtitle="$customer->code.($customer->father_name ? ' · s/o '.$customer->father_name : '').' · '.$customer->phone">
        <a class="btn" href="{{ route('biz.customers') }}" wire:navigate>All customers</a>
        <a class="btn" href="{{ route('biz.sales.new') }}" wire:navigate><x-icon name="receipt" class="h-4 w-4" /> Cash sale</a>
        <a class="btn btn-primary" href="{{ route('biz.contracts.new', ['customer' => $customer->id]) }}" wire:navigate><x-icon name="plus" class="h-4 w-4" /> New installment sale</a>
    </x-page-header>

    @if ($customer->is_blacklisted)
        <div class="mb-4 flex items-center gap-2 rounded-xl bg-red-50 p-3 text-sm text-red-800 ring-1 ring-red-200"><x-icon name="warning" class="h-5 w-5" /> This customer is blacklisted. Check with the owner before selling to them again.</div>
    @endif

    <div class="mb-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat icon="layers" tone="blue" label="Plans" :value="$contracts->count()" :note="$contracts->where('status', 'active')->count().' running'" />
        <x-stat icon="cash" tone="green" label="Paid so far" :value="rs($paidTotal)" note="Down payments and installments" />
        <x-stat icon="wallet" tone="brand" label="Still owed on plans" :value="rs($owed)" :note="$overdue ? rs($overdue).' overdue' : 'Nothing overdue'" />
        <x-stat icon="receipt" :tone="$credit ? 'amber' : 'green'" label="Udhaar on bills" :value="rs($credit)" :note="$invoices->whereNull('voided_at')->count().' bills'" />
    </div>

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="card-pad text-sm">
            <h2 class="panel-title"><x-icon name="id-card" class="h-5 w-5 text-brand-600" /> Details</h2>
            <dl class="mt-3 space-y-2">
                @foreach (['CNIC' => $customer->cnic, 'Mobile' => $customer->phone, 'Second phone' => $customer->phone2, 'Occupation' => $customer->occupation, 'Address' => $customer->address, 'Notes' => $customer->notes] as $label => $value)
                    @if ($value)<div><dt class="text-xs text-slate-500">{{ $label }}</dt><dd class="font-medium">{{ $value }}</dd></div>@endif
                @endforeach
            </dl>
            <a class="btn btn-sm mt-4" href="https://wa.me/{{ \App\Support\Phone::international($customer->phone) }}" target="_blank" rel="noopener"><x-icon name="message" class="h-3.5 w-3.5" /> WhatsApp</a>
        </div>

        <div class="card overflow-x-auto lg:col-span-2">
            <div class="border-b border-slate-200 px-4 py-3 text-sm font-semibold">Installment plans</div>
            <table class="tbl">
                <thead><tr><th>Plan</th><th>Item</th><th>Total</th><th>Owed</th><th>Status</th></tr></thead>
                <tbody>
                @forelse ($contracts as $contract)
                    <tr wire:key="k-{{ $contract->id }}">
                        <td><a class="font-medium text-brand-600 hover:underline" href="{{ route('biz.contract', $contract) }}" wire:navigate>{{ $contract->number }}</a><div class="text-xs text-slate-500">{{ $contract->sale_date->format('j M Y') }}</div></td>
                        <td>{{ $contract->item }}</td>
                        <td>{{ rs($contract->total_price) }}</td>
                        <td>{{ rs($contract->outstanding()) }}@if ($contract->isOpen() && $contract->daysLate())<div class="text-xs text-red-600">{{ $contract->daysLate() }} days late</div>@endif</td>
                        <td><x-biz-status :contract="$contract" /></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="p-6 text-center text-slate-500">No plans yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        @if ($invoices->isNotEmpty())
            <div class="card overflow-x-auto lg:col-span-3">
                <div class="border-b border-slate-200 px-4 py-3 text-sm font-semibold">Cash and udhaar bills</div>
                <table class="tbl">
                    <thead><tr><th>Bill</th><th class="text-end">Total</th><th class="text-end">Paid</th><th class="text-end">Udhaar</th></tr></thead>
                    <tbody>
                    @foreach ($invoices as $inv)
                        <tr wire:key="i-{{ $inv->id }}" @class(['opacity-50' => $inv->voided_at])>
                            <td><a class="font-medium text-brand-600 hover:underline" href="{{ route('biz.sale', $inv) }}" wire:navigate>{{ $inv->number }}</a><div class="text-xs text-slate-500">{{ $inv->date->format('j M Y') }}{{ $inv->voided_at ? ' · cancelled' : '' }}</div></td>
                            <td class="text-end">{{ rs($inv->total) }}</td>
                            <td class="text-end">{{ rs($inv->paid) }}</td>
                            <td class="text-end">{{ $inv->balance() ? rs($inv->balance()) : '—' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
