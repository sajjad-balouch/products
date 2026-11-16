@props(['greeting', 'title' => null, 'subtitle' => null])
{{-- The welcome band at the top of a dashboard. Anything in the slot sits on the right. --}}
<div class="relative mb-6 overflow-hidden rounded-3xl bg-brand-900 p-6 text-white shadow-lift sm:p-7">
    <div class="bg-dots pointer-events-none absolute inset-0 opacity-60" aria-hidden="true"></div>
    <div class="pointer-events-none absolute -end-16 -top-24 h-72 w-72 rounded-full bg-brand-400/30 blur-3xl" aria-hidden="true"></div>
    <div class="pointer-events-none absolute -bottom-28 end-40 h-64 w-64 rounded-full bg-accent-500/25 blur-3xl" aria-hidden="true"></div>
    <div class="pointer-events-none absolute -start-20 -bottom-32 h-72 w-72 rounded-full bg-brand-500/40 blur-3xl" aria-hidden="true"></div>
    <div class="relative flex flex-wrap items-end justify-between gap-5">
        <div>
            <div class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-medium text-brand-50 ring-1 ring-white/15 backdrop-blur"><x-icon name="sun" class="h-3.5 w-3.5 text-accent-400" /> {{ now()->format('l, j F Y') }}</div>
            <h1 class="font-display mt-3 text-[28px] leading-tight font-extrabold tracking-tight sm:text-[34px]">{{ $greeting }}, <span class="text-gradient">{{ $title ?? strtok(auth()->user()->name, ' ') }}</span></h1>
            @if ($subtitle)<p class="mt-1.5 max-w-xl text-sm text-brand-100/85">{{ $subtitle }}</p>@endif
        </div>
        {{ $slot }}
    </div>
</div>
