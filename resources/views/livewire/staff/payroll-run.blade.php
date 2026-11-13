<div>
    <x-page-header title="Payroll" subtitle="One day's pay, salary divided by 30, is deducted for each day off pay: absent days, half of the half days, and lates if Settings says so.">
        <input class="input w-auto" type="month" aria-label="Month" wire:model.live="month">
        <button class="btn btn-primary" wire:click="generate">Calculate salaries</button>
    </x-page-header>
    <div class="card overflow-x-auto">
        <table class="tbl">
            <thead><tr><th>Staff</th><th>Basic</th><th>Allowance</th><th>Absent</th><th>Half / late</th><th>Days off pay</th><th>Deduction</th><th>Net</th><th>Status</th><th></th></tr></thead>
            <tbody>
            @forelse ($slips as $slip)
                <tr wire:key="sl-{{ $slip->id }}">
                    <td class="font-medium">{{ $slip->staff->name }}</td><td>{{ number_format($slip->basic) }}</td><td>{{ number_format($slip->allowance) }}</td><td>{{ $slip->absent_days }}</td><td>{{ $slip->half_days }} / {{ $slip->late_days }}</td><td>{{ $slip->deducted_days + 0 }}</td><td>{{ number_format($slip->deduction) }}</td><td class="font-semibold">{{ number_format($slip->net) }}</td>
                    <td>@if ($slip->paid_on)<span class="badge badge-green">Paid {{ $slip->paid_on->format('j M') }}</span>@else<span class="badge badge-amber">Unpaid</span>@endif</td>
                    <td class="text-end whitespace-nowrap">
                        <button class="btn btn-sm" wire:click="markPaid({{ $slip->id }})">{{ $slip->paid_on ? 'Undo' : 'Mark paid' }}</button>
                        <a class="btn btn-sm" href="{{ route('print.payslip', $slip->id) }}" target="_blank">Slip</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="10" class="py-10 text-center text-slate-500">Nothing calculated for this month yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
