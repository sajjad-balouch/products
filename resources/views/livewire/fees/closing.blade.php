<div>
    <x-page-header title="Daily cash closing" subtitle="Count the drawer at the end of the day. Once closed, cash receipts for that date cannot be added or voided." />
    <div class="grid gap-4 lg:grid-cols-3">
        <div class="space-y-4">
            <form wire:submit="close" class="card-pad space-y-3">
                <x-field label="Date" name="date"><input id="date" class="input" type="date" wire:model.live="date"></x-field>
                <div class="rounded-lg bg-slate-50 p-3">
                    <div class="text-xs text-slate-500">Cash the system expects</div>
                    <div class="text-2xl font-semibold">Rs {{ number_format($expected) }}</div>
                </div>
                @if ($isClosed)
                    <p class="badge badge-green">This date is closed</p>
                @else
                    <x-field label="Cash you counted (Rs)" name="counted"><input id="counted" class="input" type="number" min="0" wire:model="counted"></x-field>
                    <x-field label="Note" name="note"><input id="note" class="input" wire:model="note"></x-field>
                    <button class="btn btn-primary w-full" wire:confirm="Close this date? This cannot be undone.">Close the day</button>
                @endif
            </form>
        </div>
        <div class="space-y-4 lg:col-span-2">
            <div class="card overflow-hidden">
                <h2 class="border-b border-slate-200 px-4 py-3 font-semibold">Receipts on this date</h2>
                <div class="overflow-x-auto"><table class="tbl">
                    <thead><tr><th>Receipt</th><th>Family</th><th>Method</th><th>Amount</th><th>Received by</th></tr></thead>
                    <tbody>
                    @forelse ($payments as $p)
                        <tr><td class="font-mono text-xs">{{ $p->receipt_no }}</td><td>{{ $p->voucher->guardian->name }}</td><td>{{ ucfirst($p->method) }}</td><td>Rs {{ number_format($p->amount) }}</td><td>{{ $p->receivedBy?->name }}</td></tr>
                    @empty
                        <tr><td colspan="5" class="py-6 text-center text-slate-500">No receipts on this date.</td></tr>
                    @endforelse
                    </tbody>
                </table></div>
            </div>
            <div class="card overflow-hidden">
                <h2 class="border-b border-slate-200 px-4 py-3 font-semibold">Past closings</h2>
                <div class="overflow-x-auto"><table class="tbl">
                    <thead><tr><th>Date</th><th>Expected</th><th>Counted</th><th>Difference</th><th>By</th><th>Note</th></tr></thead>
                    <tbody>
                    @forelse ($closings as $c)
                        <tr><td>{{ $c->date->format('j M Y') }}</td><td>{{ number_format($c->expected) }}</td><td>{{ number_format($c->counted) }}</td><td @class(['font-medium', 'text-red-700' => $c->difference !== 0])>{{ number_format($c->difference) }}</td><td>{{ $c->closedBy?->name }}</td><td>{{ $c->note }}</td></tr>
                    @empty
                        <tr><td colspan="6" class="py-6 text-center text-slate-500">No days closed yet.</td></tr>
                    @endforelse
                    </tbody>
                </table></div>
            </div>
        </div>
    </div>
</div>
