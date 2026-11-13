@extends('print.layout')
@section('title', 'Agreement '.$c->number)
@section('content')
<div class="text-sm leading-relaxed text-black">
    <div class="border-b-2 border-black pb-2 text-center">
        <div class="text-xl font-bold">{{ $school->name }}</div>
        <div>{{ $school->address }}{{ $school->phone ? ' · '.$school->phone : '' }}</div>
        <div class="mt-2 text-lg font-bold tracking-wide">INSTALLMENT SALE AGREEMENT</div>
        <div>Agreement no. {{ $c->number }} · Date {{ $c->sale_date->format('d M Y') }}</div>
    </div>

    <div class="mt-3 grid grid-cols-[1fr_auto] gap-4">
        <div>
            <h3 class="font-bold">Buyer</h3>
            <table class="w-full">
                <tr><td class="w-36">Name</td><td class="font-semibold">{{ $c->customer->name }}</td></tr>
                <tr><td>Father / husband</td><td>{{ $c->customer->father_name }}</td></tr>
                <tr><td>CNIC</td><td>{{ $c->customer->cnic }}</td></tr>
                <tr><td>Phone</td><td>{{ $c->customer->phone }}{{ $c->customer->phone2 ? ', '.$c->customer->phone2 : '' }}</td></tr>
                <tr><td>Address</td><td>{{ $c->customer->address }}</td></tr>
                <tr><td>Occupation</td><td>{{ $c->customer->occupation }}</td></tr>
            </table>
        </div>
        <div class="grid h-32 w-28 place-items-center overflow-hidden border border-black text-xs text-slate-500">
            @if ($c->customer->photo_path)<img src="{{ route('biz.customer.photo', $c->customer) }}" alt="" class="h-full w-full object-cover">@else Photo @endif
        </div>
    </div>

    <h3 class="mt-3 font-bold">Item and payment</h3>
    <table class="w-full border border-black">
        <tr><td class="w-1/3 border border-black px-2 py-1">Item</td><td class="border border-black px-2 py-1 font-semibold">{{ $c->item }}</td></tr>
        @if ($c->item_details)<tr><td class="border border-black px-2 py-1">Details (model, serial, engine/chassis)</td><td class="border border-black px-2 py-1 whitespace-pre-line">{{ $c->item_details }}</td></tr>@endif
        <tr><td class="border border-black px-2 py-1">Cash price</td><td class="border border-black px-2 py-1">Rs {{ number_format($c->cash_price) }}</td></tr>
        <tr><td class="border border-black px-2 py-1">Installment price (total)</td><td class="border border-black px-2 py-1 font-semibold">Rs {{ number_format($c->total_price) }}</td></tr>
        <tr><td class="border border-black px-2 py-1">Down payment received</td><td class="border border-black px-2 py-1">Rs {{ number_format($c->down_payment) }}</td></tr>
        <tr><td class="border border-black px-2 py-1">Balance on installments</td><td class="border border-black px-2 py-1">Rs {{ number_format($c->financed) }}</td></tr>
        <tr><td class="border border-black px-2 py-1">Installments</td><td class="border border-black px-2 py-1">{{ $c->installments }} × Rs {{ number_format($c->installment_amount) }}, {{ strtolower(\App\Models\Biz\Contract::FREQUENCIES[$c->frequency]) }}, from {{ $c->first_due_on->format('d M Y') }} to {{ $c->schedule->last()?->due_on->format('d M Y') }}</td></tr>
        @if ($c->late_fee)<tr><td class="border border-black px-2 py-1">Late fee</td><td class="border border-black px-2 py-1">Rs {{ number_format($c->late_fee) }} for each installment paid more than {{ $c->grace_days }} days late</td></tr>@endif
    </table>

    @if ($c->guarantors->isNotEmpty())
        <h3 class="mt-3 font-bold">Guarantors</h3>
        <table class="w-full border border-black">
            <thead><tr class="bg-slate-100"><th class="border border-black px-2 py-1 text-start">Name</th><th class="border border-black px-2 py-1 text-start">Father</th><th class="border border-black px-2 py-1 text-start">CNIC</th><th class="border border-black px-2 py-1 text-start">Phone</th><th class="border border-black px-2 py-1 text-start">Address</th></tr></thead>
            @foreach ($c->guarantors as $g)
                <tr><td class="border border-black px-2 py-1">{{ $g->name }}{{ $g->relation ? ' ('.$g->relation.')' : '' }}</td><td class="border border-black px-2 py-1">{{ $g->father_name }}</td><td class="border border-black px-2 py-1">{{ $g->cnic }}</td><td class="border border-black px-2 py-1">{{ $g->phone }}</td><td class="border border-black px-2 py-1">{{ $g->address }}</td></tr>
            @endforeach
        </table>
    @endif

    @if ($settings['terms'])
        <h3 class="mt-3 font-bold">Terms and conditions</h3>
        <div class="whitespace-pre-line">{{ $settings['terms'] }}</div>
    @endif

    <div class="mt-10 grid grid-cols-{{ min(4, 2 + $c->guarantors->count()) }} gap-6 text-center">
        <div><div class="border-t border-black pt-1">Buyer's signature / thumb</div></div>
        @foreach ($c->guarantors as $i => $g)<div><div class="border-t border-black pt-1">Guarantor {{ $i + 1 }}</div></div>@endforeach
        <div><div class="border-t border-black pt-1">For {{ $school->name }}</div></div>
    </div>
</div>
@endsection
