@extends('print.layout')
@section('title', 'Statement '.$c->number)
@section('content')
<div class="text-sm text-black">
    <div class="flex items-start justify-between border-b-2 border-black pb-2">
        <div>
            <div class="text-xl font-bold">{{ $school->name }}</div>
            <div>{{ $school->address }}{{ $school->phone ? ' · '.$school->phone : '' }}</div>
        </div>
        <div class="text-end">
            <div class="text-lg font-bold">Account statement</div>
            <div>Plan {{ $c->number }} · {{ now()->format('d M Y') }}</div>
        </div>
    </div>

    <div class="mt-3 grid grid-cols-2 gap-4">
        <div>
            <div class="font-semibold">{{ $c->customer->name }}{{ $c->customer->father_name ? ' s/o '.$c->customer->father_name : '' }}</div>
            <div>{{ $c->customer->phone }}{{ $c->customer->cnic ? ' · CNIC '.$c->customer->cnic : '' }}</div>
            <div>{{ $c->customer->address }}</div>
        </div>
        <table class="w-full">
            <tr><td>Item</td><td class="text-end">{{ $c->item }}</td></tr>
            <tr><td>Sale date</td><td class="text-end">{{ $c->sale_date->format('d M Y') }}</td></tr>
            <tr><td>Total price</td><td class="text-end">Rs {{ number_format($c->total_price) }}</td></tr>
            <tr><td>Down payment</td><td class="text-end">Rs {{ number_format($c->down_payment) }}</td></tr>
            <tr><td>Installments</td><td class="text-end">{{ $c->installments }} × Rs {{ number_format($c->installment_amount) }} ({{ strtolower(\App\Models\Biz\Contract::FREQUENCIES[$c->frequency]) }})</td></tr>
        </table>
    </div>

    <h3 class="mt-4 font-semibold">Installments</h3>
    <table class="mt-1 w-full border border-black text-[12px]">
        <thead><tr class="bg-slate-100"><th class="border border-black px-2 py-1 text-start">#</th><th class="border border-black px-2 py-1 text-start">Due date</th><th class="border border-black px-2 py-1 text-end">Amount</th><th class="border border-black px-2 py-1 text-end">Paid</th><th class="border border-black px-2 py-1 text-end">Balance</th><th class="border border-black px-2 py-1 text-start">Status</th></tr></thead>
        <tbody>
        @foreach ($c->schedule as $s)
            <tr>
                <td class="border border-black px-2 py-0.5">{{ $s->number }}</td>
                <td class="border border-black px-2 py-0.5">{{ $s->due_on->format('d M Y') }}</td>
                <td class="border border-black px-2 py-0.5 text-end">{{ number_format($s->amount) }}</td>
                <td class="border border-black px-2 py-0.5 text-end">{{ $s->paid ? number_format($s->paid) : '' }}</td>
                <td class="border border-black px-2 py-0.5 text-end">{{ $s->amount - $s->paid ? number_format($s->amount - $s->paid) : '' }}</td>
                <td class="border border-black px-2 py-0.5">{{ ['paid' => 'Paid', 'late' => 'Late', 'part' => 'Part paid', 'today' => 'Due today', 'upcoming' => ''][$s->status()] }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    <h3 class="mt-4 font-semibold">Payments received</h3>
    <table class="mt-1 w-full border border-black text-[12px]">
        <thead><tr class="bg-slate-100"><th class="border border-black px-2 py-1 text-start">Date</th><th class="border border-black px-2 py-1 text-start">Receipt</th><th class="border border-black px-2 py-1 text-start">For</th><th class="border border-black px-2 py-1 text-end">Amount</th></tr></thead>
        <tbody>
        @forelse ($c->receipts as $r)
            <tr>
                <td class="border border-black px-2 py-0.5">{{ $r->date->format('d M Y') }}</td>
                <td class="border border-black px-2 py-0.5">{{ $r->number }}</td>
                <td class="border border-black px-2 py-0.5">{{ $r->is_down_payment ? 'Down payment' : 'Installment' }}{{ $r->late_fee ? ' + late fee '.number_format($r->late_fee) : '' }}{{ $r->discount ? ', discount '.number_format($r->discount) : '' }}</td>
                <td class="border border-black px-2 py-0.5 text-end">{{ number_format($r->total()) }}</td>
            </tr>
        @empty
            <tr><td colspan="4" class="border border-black px-2 py-1">No payments yet.</td></tr>
        @endforelse
        </tbody>
    </table>

    <div class="mt-4 flex justify-end">
        <table class="w-72 text-sm">
            <tr><td>Total price</td><td class="text-end">Rs {{ number_format($c->total_price) }}</td></tr>
            <tr><td>Paid (incl. down payment)</td><td class="text-end">Rs {{ number_format($c->down_payment + $c->paid()) }}</td></tr>
            <tr class="text-base font-bold"><td class="border-t-2 border-black pt-1">Balance due</td><td class="border-t-2 border-black pt-1 text-end">Rs {{ number_format($c->outstanding()) }}</td></tr>
            @if ($c->isOpen() && $c->overdue())<tr class="font-semibold"><td>Overdue now</td><td class="text-end">Rs {{ number_format($c->overdue()) }}</td></tr>@endif
        </table>
    </div>
</div>
@endsection
