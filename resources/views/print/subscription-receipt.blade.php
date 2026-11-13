@extends('print.layout')
@section('title', 'Receipt '.$number)
@section('content')
<div class="mx-auto max-w-md text-sm">
    <div class="text-center">
        <div class="text-lg font-bold">{{ config('app.name') }}</div>
        <div class="text-xs text-slate-600">School and college management software</div>
        <div class="mt-2 font-semibold uppercase">Subscription receipt</div>
    </div>
    <table class="mt-4 w-full">
        <tr><td class="py-1 text-slate-600">Receipt no.</td><td class="text-end font-medium">{{ $number }}</td></tr>
        <tr><td class="py-1 text-slate-600">Date paid</td><td class="text-end">{{ $entry->paid_on?->format('j M Y') }}</td></tr>
        <tr><td class="py-1 text-slate-600">Received from</td><td class="text-end">{{ $school->name }}</td></tr>
        @if ($school->address)<tr><td class="py-1 text-slate-600">Address</td><td class="text-end">{{ $school->address }}</td></tr>@endif
        <tr><td class="py-1 text-slate-600">For</td><td class="text-end">{{ $entry->months }} {{ str('month')->plural($entry->months) }} of {{ config('app.name') }}</td></tr>
        <tr><td class="py-1 text-slate-600">Period</td><td class="text-end">{{ $entry->from_date?->format('j M Y') }} to {{ $entry->to_date->format('j M Y') }}</td></tr>
        <tr><td class="py-1 text-slate-600">Paid by</td><td class="text-end">{{ $entry->method ? \App\Models\SubscriptionEntry::METHODS[$entry->method] : '' }}{{ $entry->reference ? ', '.$entry->reference : '' }}</td></tr>
        <tr class="border-t border-slate-300 text-base font-bold"><td class="pt-2">Amount received</td><td class="pt-2 text-end">Rs {{ number_format($entry->amount) }}</td></tr>
    </table>
    @if ($entry->note)<p class="mt-3 text-xs text-slate-600">{{ $entry->note }}</p>@endif
    <div class="mt-10 flex justify-between text-xs text-slate-600"><span>Received by: {{ $entry->user?->name }}</span><span>Signature</span></div>
</div>
@endsection
