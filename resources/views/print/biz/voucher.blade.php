@extends('print.layout')
@section('title', $e->number)
@section('content')
<div class="text-sm text-black">
    <div class="flex items-start justify-between border-b-2 border-black pb-2">
        <div><div class="text-xl font-bold">{{ $school->name }}</div><div>{{ $school->address }}</div></div>
        <div class="text-end"><div class="text-lg font-bold uppercase">{{ $e->kindLabel() }}</div><div>{{ $e->number }} · {{ $e->date->format('d M Y') }}</div></div>
    </div>
    @if ($e->reversed_at)<div class="mt-2 border-2 border-red-700 p-1 text-center font-bold text-red-700">REVERSED {{ $e->reversed_at->format('d M Y') }}</div>@endif
    <p class="mt-3"><span class="font-semibold">Description:</span> {{ $e->memo }}</p>
    <table class="mt-3 w-full border border-black">
        <thead><tr class="bg-slate-100"><th class="border border-black px-2 py-1 text-start">Code</th><th class="border border-black px-2 py-1 text-start">Account</th><th class="border border-black px-2 py-1 text-start">Note</th><th class="border border-black px-2 py-1 text-end">Debit</th><th class="border border-black px-2 py-1 text-end">Credit</th></tr></thead>
        <tbody>
        @foreach ($e->lines as $l)
            <tr>
                <td class="border border-black px-2 py-1 font-mono text-xs">{{ $l->account->code }}</td>
                <td class="border border-black px-2 py-1">{{ $l->account->name }}</td>
                <td class="border border-black px-2 py-1">{{ $l->memo }}{{ $l->customer ? ($l->memo ? ' · ' : '').$l->customer->name : '' }}</td>
                <td class="border border-black px-2 py-1 text-end">{{ $l->debit ? number_format($l->debit) : '' }}</td>
                <td class="border border-black px-2 py-1 text-end">{{ $l->credit ? number_format($l->credit) : '' }}</td>
            </tr>
        @endforeach
        </tbody>
        <tfoot><tr class="font-bold"><td colspan="3" class="border border-black px-2 py-1">Total</td><td class="border border-black px-2 py-1 text-end">{{ number_format($e->lines->sum('debit')) }}</td><td class="border border-black px-2 py-1 text-end">{{ number_format($e->lines->sum('credit')) }}</td></tr></tfoot>
    </table>
    <div class="mt-12 grid grid-cols-3 gap-6 text-center">
        <div class="border-t border-black pt-1">Prepared by {{ $e->creator?->name }}</div>
        <div class="border-t border-black pt-1">Checked by</div>
        <div class="border-t border-black pt-1">Approved by</div>
    </div>
</div>
@endsection
