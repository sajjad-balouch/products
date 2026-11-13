<div>
    <x-page-header title="Payment proofs" subtitle="Parents pay by JazzCash, Easypaisa or bank, then upload the screenshot. Match it to your statement, then approve. A receipt is created automatically." />

    <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse ($pending as $proof)
            <div class="card overflow-hidden" wire:key="pf-{{ $proof->id }}">
                <a href="{{ route('proofs.image', $proof->id) }}" target="_blank">
                    <img src="{{ route('proofs.image', $proof->id) }}" alt="Payment screenshot from {{ $proof->guardian->name }}" class="h-48 w-full bg-slate-100 object-contain">
                </a>
                <div class="space-y-2 p-4 text-sm">
                    <div class="flex justify-between gap-2"><span class="font-semibold">Rs {{ number_format($proof->amount) }}</span><span class="badge badge-blue">{{ ucfirst($proof->method) }}</span></div>
                    <div>{{ $proof->guardian->name }} · {{ $proof->guardian->phone }}</div>
                    <div class="text-slate-500">Voucher {{ $proof->voucher->voucher_no }}, balance Rs {{ number_format($proof->voucher->balance) }}</div>
                    <div class="font-mono text-xs">Transaction ID: {{ $proof->reference }}</div>
                    @if ($proof->is_duplicate)
                        <p class="rounded-md bg-red-50 p-2 text-xs font-medium text-red-800">This transaction ID was already used on another proof. Check it before approving.</p>
                    @endif
                    @if ($proof->amount > $proof->voucher->balance)
                        <p class="rounded-md bg-amber-50 p-2 text-xs text-amber-900">More than the balance. Only Rs {{ number_format($proof->voucher->balance) }} will be applied.</p>
                    @endif
                    <input class="input" placeholder="Reason, if rejecting" aria-label="Reason for rejecting" wire:model="notes.{{ $proof->id }}">
                    <div class="flex gap-2">
                        <button class="btn btn-primary flex-1" wire:click="approve({{ $proof->id }})" wire:confirm="Have you seen this amount in your account?" @disabled($proof->voucher->balance <= 0)>Approve</button>
                        <button class="btn btn-danger flex-1" wire:click="reject({{ $proof->id }})">Reject</button>
                    </div>
                </div>
            </div>
        @empty
            <div class="card-pad text-sm text-slate-500 md:col-span-2 xl:col-span-3">Nothing is waiting for review.</div>
        @endforelse
    </div>

    @if ($recent->isNotEmpty())
        <div class="card mt-6 overflow-hidden">
            <h2 class="border-b border-slate-200 px-4 py-3 font-semibold">Recently reviewed</h2>
            <div class="overflow-x-auto"><table class="tbl">
                <thead><tr><th>Family</th><th>Voucher</th><th>Amount</th><th>Transaction ID</th><th>Result</th></tr></thead>
                <tbody>
                @foreach ($recent as $proof)
                    <tr><td>{{ $proof->guardian->name }}</td><td class="font-mono text-xs">{{ $proof->voucher->voucher_no }}</td><td>Rs {{ number_format($proof->amount) }}</td><td class="font-mono text-xs">{{ $proof->reference }}</td>
                        <td><span @class(['badge', 'badge-green' => $proof->status === 'approved', 'badge-red' => $proof->status === 'rejected'])>{{ ucfirst($proof->status) }}</span> <span class="text-xs text-slate-500">{{ $proof->review_note }}</span></td></tr>
                @endforeach
                </tbody>
            </table></div>
        </div>
    @endif
</div>
