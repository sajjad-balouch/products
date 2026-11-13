@extends('print.layout')
@section('title', 'Bill '.$inv->number)
@section('content')
<div class="text-sm text-black">
    <div class="flex items-start justify-between border-b-2 border-black pb-2">
        <div><div class="text-xl font-bold">{{ $school->name }}</div><div>{{ $school->address }}{{ $school->phone ? ' · '.$school->phone : '' }}</div></div>
        <div class="text-end"><div class="text-lg font-bold">SALE INVOICE</div><div>{{ $inv->number }} · {{ $inv->date->format('d M Y') }}</div></div>
    </div>
    @if ($inv->voided_at)<div class="mt-2 border-2 border-red-700 p-1 text-center font-bold text-red-700">CANCELLED · {{ $inv->void_reason }}</div>@endif
    <div class="mt-3"><span class="font-semibold">Customer:</span> {{ $inv->buyer() }}{{ $inv->customer?->phone ? ' · '.$inv->customer->phone : '' }}</div>
    <table class="mt-3 w-full border border-black">
        <thead><tr class="bg-slate-100"><th class="border border-black px-2 py-1 text-start">#</th><th class="border border-black px-2 py-1 text-start">Product</th><th class="border border-black px-2 py-1 text-end">Qty</th><th class="border border-black px-2 py-1 text-end">Price</th><th class="border border-black px-2 py-1 text-end">Amount</th></tr></thead>
        <tbody>
        @foreach ($inv->lines as $i => $l)
            <tr>
                <td class="border border-black px-2 py-1">{{ $i + 1 }}</td>
                <td class="border border-black px-2 py-1">{{ $l->product->label() }}@if ($l->details)<div class="font-mono text-[10px] break-all">{{ $l->details }}</div>@endif</td>
                <td class="border border-black px-2 py-1 text-end">{{ number_format($l->quantity) }}</td>
                <td class="border border-black px-2 py-1 text-end">{{ number_format($l->unit_price) }}</td>
                <td class="border border-black px-2 py-1 text-end">{{ number_format($l->total) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <div class="mt-3 flex justify-end">
        <table class="w-64">
            <tr><td>Subtotal</td><td class="text-end">{{ number_format($inv->subtotal) }}</td></tr>
            @if ($inv->discount)<tr><td>Discount</td><td class="text-end">− {{ number_format($inv->discount) }}</td></tr>@endif
            <tr class="text-base font-bold"><td class="border-t-2 border-black pt-1">Total</td><td class="border-t-2 border-black pt-1 text-end">Rs {{ number_format($inv->total) }}</td></tr>
            <tr><td>Paid</td><td class="text-end">{{ number_format($inv->paid) }}</td></tr>
            @if ($inv->balance())<tr class="font-bold"><td>Balance (udhaar)</td><td class="text-end">Rs {{ number_format($inv->balance()) }}</td></tr>@endif
        </table>
    </div>
    <div class="mt-12 grid grid-cols-2 gap-6 text-center">
        <div class="border-t border-black pt-1">Customer</div>
        <div class="border-t border-black pt-1">For {{ $school->name }}</div>
    </div>
    @if ($settings['receipt_note'])<p class="mt-4 text-center text-xs">{{ $settings['receipt_note'] }}</p>@endif
</div>
@endsection
