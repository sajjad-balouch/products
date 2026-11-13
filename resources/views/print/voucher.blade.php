@extends('print.layout')
@section('title', 'Voucher '.$voucher->voucher_no)
@section('content')
<div class="grid gap-4 sm:grid-cols-3 print:grid-cols-3">
    @foreach (['Parent copy', 'School copy', 'Bank copy'] as $copy)
        <div class="border border-dashed border-slate-400 p-3 text-xs">
            <div class="flex items-start justify-between gap-1">
                <div><div class="text-sm font-bold">{{ $school->name }}</div><div class="text-[10px] text-slate-600">{{ $school->address }} {{ $school->phone }}</div></div>
                <div class="text-[10px] font-medium whitespace-nowrap uppercase">{{ $copy }}</div>
            </div>
            <div class="mt-2 grid grid-cols-2 gap-x-2 gap-y-0.5 border-y border-slate-300 py-1.5">
                <div>Voucher: <b>{{ $voucher->voucher_no }}</b></div>
                <div>Month: <b>{{ $voucher->month->format('M Y') }}</b></div>
                <div>Issued: {{ $voucher->created_at->format('j M Y') }}</div>
                <div>Due: <b>{{ $voucher->due_on->format('j M Y') }}</b></div>
                <div class="col-span-2">Family: <b>{{ $voucher->guardian->name }}</b> ({{ $voucher->guardian->phone }})</div>
            </div>
            <table class="mt-1.5 w-full">
                @foreach ($voucher->lines->groupBy('student_id') as $lines)
                    @php($student = $lines->first()->student)
                    @if ($student)<tr><td colspan="2" class="pt-1 font-semibold">{{ $student->name }} <span class="font-normal text-slate-600">{{ $student->admission_no }}, {{ $student->section->label }}</span></td></tr>@endif
                    @foreach ($lines as $line)<tr><td class="ps-2">{{ $line->label }}</td><td class="text-end">{{ number_format($line->amount) }}</td></tr>@endforeach
                @endforeach
                @if ($voucher->late_fee)<tr><td class="pt-1">Late fee</td><td class="pt-1 text-end">{{ number_format($voucher->late_fee) }}</td></tr>@endif
                <tr class="border-t border-slate-400 font-bold"><td class="pt-1">Total</td><td class="pt-1 text-end">Rs {{ number_format($voucher->total) }}</td></tr>
                @if ($voucher->paid)<tr><td>Already paid</td><td class="text-end">{{ number_format($voucher->paid) }}</td></tr><tr class="font-bold"><td>Balance</td><td class="text-end">Rs {{ number_format($voucher->balance) }}</td></tr>@endif
                @if ($arrears)<tr class="text-red-800"><td class="pt-1">Previous dues (separate vouchers)</td><td class="pt-1 text-end">{{ number_format($arrears) }}</td></tr>@endif
            </table>
            @if ($school->late_fee_per_day)<p class="mt-1.5 text-[10px] text-slate-600">After the due date: Rs {{ $school->late_fee_per_day }} per day{{ $school->late_fee_max ? ', up to Rs '.number_format($school->late_fee_max) : '' }}.</p>@endif
            @if ($school->bank_details || $school->jazzcash_number || $school->easypaisa_number)
                <div class="mt-1.5 border-t border-slate-300 pt-1 text-[10px] whitespace-pre-line text-slate-700">{{ $school->bank_details }}@if ($school->jazzcash_number)

JazzCash: {{ $school->jazzcash_number }}@endif @if ($school->easypaisa_number)

Easypaisa: {{ $school->easypaisa_number }}@endif</div>
            @endif
            <div class="mt-6 flex justify-between text-[10px] text-slate-600"><span>Cashier</span><span>Stamp</span></div>
        </div>
    @endforeach
</div>
@endsection
