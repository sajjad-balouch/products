@extends('print.layout')
@section('title', 'Salary slip')
@section('content')
<div class="mx-auto max-w-md text-sm">
    <div class="text-center"><div class="text-lg font-bold">{{ $school->name }}</div><div class="font-semibold">Salary slip: {{ $slip->month->format('F Y') }}</div></div>
    <table class="mt-4 w-full">
        <tr><td class="py-1 text-slate-600">Name</td><td class="text-end font-medium">{{ $slip->staff->name }}</td></tr>
        <tr><td class="py-1 text-slate-600">Designation</td><td class="text-end">{{ $slip->staff->designation }}</td></tr>
        <tr><td class="py-1 text-slate-600">Basic salary</td><td class="text-end">{{ number_format($slip->basic) }}</td></tr>
        <tr><td class="py-1 text-slate-600">Allowance</td><td class="text-end">{{ number_format($slip->allowance) }}</td></tr>
        <tr>
            <td class="py-1 text-slate-600">
                Deduction for {{ $slip->deducted_days + 0 }} {{ str('day')->plural((int) ceil($slip->deducted_days)) }} off pay
                <div class="text-xs">{{ $slip->absent_days }} absent, {{ $slip->half_days }} half {{ str('day')->plural($slip->half_days) }}, {{ $slip->late_days }} late</div>
            </td>
            <td class="text-end">-{{ number_format($slip->deduction) }}</td>
        </tr>
        <tr class="border-t border-slate-300 text-base font-bold"><td class="pt-2">Net pay</td><td class="pt-2 text-end">Rs {{ number_format($slip->net) }}</td></tr>
        <tr><td class="py-1 text-slate-600">Paid on</td><td class="text-end">{{ $slip->paid_on?->format('j M Y') ?? 'Not paid yet' }}</td></tr>
    </table>
    <div class="mt-12 flex justify-between text-xs text-slate-600"><span>Accounts</span><span>Received by</span></div>
</div>
@endsection
