@props(['label', 'value', 'note' => null, 'tone' => 'slate', 'href' => null, 'icon' => null])
@php
    $color = ['red' => 'text-red-700', 'green' => 'text-emerald-700', 'amber' => 'text-amber-700', 'slate' => 'text-slate-900', 'brand' => 'text-slate-900', 'blue' => 'text-slate-900', 'violet' => 'text-slate-900'][$tone] ?? 'text-slate-900';
    $chip = in_array($tone, ['red', 'green', 'amber', 'blue', 'violet', 'brand'], true) ? 'tone-'.$tone : 'tone-brand';
@endphp
<{{ $href ? 'a' : 'div' }} @if ($href) href="{{ $href }}" wire:navigate @endif class="card-pad group flex items-start gap-3.5 {{ $href ? 'transition hover:-translate-y-0.5 hover:border-brand-500 hover:shadow-md' : '' }}">
    @if ($icon)<div class="icon-chip {{ $chip }}"><x-icon :name="$icon" /></div>@endif
    <div class="min-w-0 flex-1">
        <div class="text-xs font-medium text-slate-500">{{ $label }}</div>
        <div class="mt-0.5 text-2xl font-bold tracking-tight {{ $color }}">{{ $value }}</div>
        @if ($note)<div class="mt-0.5 truncate text-xs text-slate-500">{{ $note }}</div>@endif
    </div>
    @if ($href)<x-icon name="chevron-right" class="mt-1 h-4 w-4 shrink-0 text-slate-300 transition group-hover:text-brand-600" />@endif
</{{ $href ? 'a' : 'div' }}>
