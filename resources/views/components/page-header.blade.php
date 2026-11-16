@props(['title', 'subtitle' => null, 'icon' => null])
{{-- The top of every page: its name, a line about it, and its buttons on the right. --}}
@php($icon ??= \App\Support\Navigation::icon((string) request()->route()?->getName()))
<div class="mb-6 flex flex-wrap items-center justify-between gap-4 no-print">
    <div class="flex min-w-0 items-center gap-3.5">
        <div class="hidden h-11 w-11 shrink-0 place-items-center rounded-2xl bg-white text-brand-600 shadow-soft ring-1 ring-slate-200/80 sm:grid"><x-icon :name="$icon" class="h-5 w-5" /></div>
        <div class="min-w-0">
            <h1 class="font-display text-[22px] leading-tight font-bold tracking-tight text-slate-900">{{ $title }}</h1>
            @if ($subtitle)<p class="mt-0.5 max-w-3xl text-sm text-slate-500">{{ $subtitle }}</p>@endif
        </div>
    </div>
    <div class="flex flex-wrap items-center gap-2">{{ $slot }}</div>
</div>
