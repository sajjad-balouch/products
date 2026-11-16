@props(['label', 'value', 'note' => null, 'tone' => 'slate', 'href' => null, 'icon' => null])
@php
    $color = ['red' => 'text-red-600', 'green' => 'text-emerald-600', 'amber' => 'text-amber-600', 'slate' => 'text-slate-900', 'brand' => 'text-slate-900', 'blue' => 'text-slate-900', 'violet' => 'text-slate-900'][$tone] ?? 'text-slate-900';
    $chip = in_array($tone, ['red', 'green', 'amber', 'blue', 'violet', 'brand'], true) ? 'tone-'.$tone : 'tone-brand';
    $bar = ['red' => 'from-red-400', 'green' => 'from-emerald-400', 'amber' => 'from-amber-400', 'blue' => 'from-sky-400', 'violet' => 'from-violet-400'][$tone] ?? 'from-brand-400';
@endphp
{{-- A number card: label and icon on top, the number big, a note under it. --}}
<{{ $href ? 'a' : 'div' }} @if ($href) href="{{ $href }}" wire:navigate @endif class="group relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white p-5 shadow-soft {{ $href ? 'transition-all duration-200 hover:-translate-y-0.5 hover:border-brand-200 hover:shadow-lift' : '' }}">
    <div class="pointer-events-none absolute inset-x-0 top-0 h-0.5 bg-gradient-to-r {{ $bar }} to-transparent opacity-70"></div>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <div class="text-[13px] font-medium text-slate-500">{{ $label }}</div>
            <div class="font-display mt-1.5 text-[26px] leading-none font-bold tracking-tight tabular-nums {{ $color }}">{{ $value }}</div>
        </div>
        @if ($icon)<div class="icon-chip {{ $chip }} h-11 w-11"><x-icon :name="$icon" /></div>@endif
    </div>
    @if ($note || $href)
        <div class="mt-3 flex items-center justify-between gap-2 text-xs text-slate-500">
            <span class="truncate">{{ $note }}</span>
            @if ($href)<span class="flex shrink-0 items-center gap-0.5 font-semibold text-brand-600 opacity-0 transition group-hover:opacity-100">Open <x-icon name="chevron-right" class="h-3.5 w-3.5" /></span>@endif
        </div>
    @endif
</{{ $href ? 'a' : 'div' }}>
