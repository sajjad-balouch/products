@extends('print.layout')
@section('title', 'Receipt '.$payment->receipt_no)
@section('content')
<div class="mx-auto max-w-md text-sm">
    <div class="text-center"><div class="text-lg font-bold">{{ $school->name }}</div><div class="text-xs text-slate-600">{{ $school->address }} {{ $school->phone }}</div><div class="mt-2 font-semibold uppercase">Fee receipt</div></div>
    @if ($payment->voided_at)<div class="mt-2 rounded bg-red-100 p-2 text-center font-bold text-red-800">VOID: {{ $payment->void_reason }}</div>@endif
    <table class="mt-4 w-full">
        <tr><td class="py-1 text-slate-600">Receipt no.</td><td class="text-end font-medium">{{ $payment->receipt_no }}</td></tr>
        <tr><td class="py-1 text-slate-600">Date</td><td class="text-end">{{ $payment->paid_on->format('j M Y') }}</td></tr>
        <tr><td class="py-1 text-slate-600">Family</td><td class="text-end">{{ $payment->voucher->guardian->name }}</td></tr>
        <tr><td class="py-1 text-slate-600">Students</td><td class="text-end">{{ $payment->voucher->lines->pluck('student.name')->filter()->unique()->join(', ') }}</td></tr>
        <tr><td class="py-1 text-slate-600">Voucher</td><td class="text-end">{{ $payment->voucher->voucher_no }}, {{ $payment->voucher->month->format('M Y') }}</td></tr>
        <tr><td class="py-1 text-slate-600">Method</td><td class="text-end">{{ ucfirst($payment->method) }}{{ $payment->reference ? ', '.$payment->reference : '' }}</td></tr>
        <tr class="border-t border-slate-300 text-base font-bold"><td class="pt-2">Amount received</td><td class="pt-2 text-end">Rs {{ number_format($payment->amount) }}</td></tr>
        <tr><td class="py-1 text-slate-600">Balance on voucher</td><td class="text-end">Rs {{ number_format($payment->voucher->balance) }}</td></tr>
    </table>
    <div class="mt-10 flex justify-between text-xs text-slate-600"><span>Received by: {{ $payment->receivedBy?->name }}</span><span>Signature</span></div>
</div>
@endsection
