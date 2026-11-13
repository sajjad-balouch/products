@extends('print.layout')
@section('title', 'Receipt '.$receipt->number)
@section('content')
{{-- Narrow enough for an 80 mm receipt printer, and fine on A4 too. --}}
<div class="mx-auto max-w-[80mm] text-[12px] leading-snug text-black">
    <div class="text-center">
        <div class="text-base font-bold">{{ $school->name }}</div>
        @if ($school->address)<div>{{ $school->address }}</div>@endif
        @if ($school->phone)<div>Phone {{ $school->phone }}</div>@endif
        <div class="mt-2 inline-block border-y border-black px-3 py-0.5 font-bold tracking-widest">{{ $receipt->is_down_payment ? 'DOWN PAYMENT RECEIPT' : 'PAYMENT RECEIPT' }}</div>
    </div>

    @if ($receipt->voided_at)
        <div class="my-2 border-2 border-red-700 p-1 text-center font-bold text-red-700">CANCELLED · {{ $receipt->void_reason }}</div>
    @endif

    <table class="mt-2 w-full">
        <tr><td>Receipt no.</td><td class="text-end font-semibold">{{ $receipt->number }}</td></tr>
        <tr><td>Date</td><td class="text-end">{{ $receipt->date->format('d M Y') }}</td></tr>
        <tr><td>Customer</td><td class="text-end font-semibold">{{ $contract->customer->name }}</td></tr>
        <tr><td>Phone</td><td class="text-end">{{ $contract->customer->phone }}</td></tr>
        <tr><td>Plan</td><td class="text-end">{{ $contract->number }}</td></tr>
        <tr><td>Item</td><td class="text-end">{{ $contract->item }}</td></tr>
    </table>

    <table class="mt-2 w-full border-t border-dashed border-black">
        <tr><td class="pt-1">{{ $receipt->is_down_payment ? 'Down payment' : 'Installment' }}</td><td class="pt-1 text-end">{{ number_format($receipt->amount) }}</td></tr>
        @if ($receipt->late_fee)<tr><td>Late fee</td><td class="text-end">{{ number_format($receipt->late_fee) }}</td></tr>@endif
        @if ($receipt->discount)<tr><td>Discount given</td><td class="text-end">{{ number_format($receipt->discount) }}</td></tr>@endif
        <tr class="text-sm font-bold"><td class="border-t border-black pt-1">Received</td><td class="border-t border-black pt-1 text-end">Rs {{ number_format($receipt->total()) }}</td></tr>
        <tr><td>Paid by</td><td class="text-end">{{ \App\Models\Biz\Receipt::METHODS[$receipt->method] ?? $receipt->method }}{{ $receipt->reference ? ' · '.$receipt->reference : '' }}</td></tr>
    </table>

    @php($left = $contract->outstanding())
    @php($next = $contract->nextDue())
    <table class="mt-2 w-full border-t border-dashed border-black">
        <tr><td class="pt-1">Total price</td><td class="pt-1 text-end">{{ number_format($contract->total_price) }}</td></tr>
        <tr><td>Paid to date</td><td class="text-end">{{ number_format($contract->total_price - $left) }}</td></tr>
        <tr class="font-bold"><td>Balance</td><td class="text-end">Rs {{ number_format($left) }}</td></tr>
        @if ($next)<tr><td>Next installment</td><td class="text-end">{{ number_format($next->amount - $next->paid) }} on {{ $next->due_on->format('d M Y') }}</td></tr>@endif
    </table>

    <div class="mt-3 flex items-end justify-between gap-2">
        <div class="h-16 w-16">{!! $qr !!}</div>
        <div class="text-end">
            <div class="mb-1 h-8"></div>
            <div class="border-t border-black px-2 pt-0.5">Received by {{ $receipt->receiver?->name }}</div>
        </div>
    </div>
    @if ($settings['receipt_note'])<p class="mt-2 border-t border-dashed border-black pt-1 text-center">{{ $settings['receipt_note'] }}</p>@endif
    <p class="mt-1 text-center text-[10px]">Printed {{ now()->format('d M Y h:i A') }}</p>
</div>
@endsection
