{{-- Date filter and print button shared by the reports. Pass $single = true for one "as of" date. --}}
<div class="no-print mb-4 flex flex-wrap items-center gap-2">
    @if ($single ?? false)
        <label class="flex items-center gap-2 text-sm">As of <input type="date" class="input w-auto" wire:model.live="to"></label>
    @else
        <input type="date" class="input w-auto" wire:model.live="from" aria-label="From">
        <span class="text-sm text-slate-500">to</span>
        <input type="date" class="input w-auto" wire:model.live="to" aria-label="To">
        <div class="flex gap-1">
            @foreach (['today' => 'Today', 'month' => 'This month', 'last-month' => 'Last month', 'year' => 'This year'] as $key => $label)
                <button type="button" class="btn btn-sm" wire:click="setRange('{{ $key }}')">{{ $label }}</button>
            @endforeach
        </div>
    @endif
    {{ $slot ?? '' }}
    <button type="button" class="btn btn-sm ms-auto" onclick="window.print()"><x-icon name="file-plus" class="h-3.5 w-3.5" /> Print</button>
</div>
<div class="mb-3 hidden text-center print:block">
    <div class="text-lg font-bold">{{ auth()->user()->school->name }}</div>
    <div class="text-sm">{{ $title }} · {{ ($single ?? false) ? 'as of '.\Illuminate\Support\Carbon::parse($to)->format('j M Y') : \Illuminate\Support\Carbon::parse($from)->format('j M Y').' to '.\Illuminate\Support\Carbon::parse($to)->format('j M Y') }}</div>
</div>
