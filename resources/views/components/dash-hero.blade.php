@props(['greeting', 'title' => null, 'subtitle' => null])
{{-- The welcome band at the top of a dashboard. Anything in the slot sits on the right. --}}
<div class="relative mb-5 overflow-hidden rounded-2xl bg-gradient-to-br from-brand-700 via-brand-600 to-brand-900 p-5 text-white shadow-sm sm:p-6">
    <div class="pointer-events-none absolute -end-10 -top-16 h-56 w-56 rounded-full bg-white/10" aria-hidden="true"></div>
    <div class="pointer-events-none absolute -bottom-20 end-32 h-44 w-44 rounded-full bg-accent-500/20" aria-hidden="true"></div>
    <div class="relative flex flex-wrap items-end justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-sm text-white/80"><x-icon name="sun" class="h-4 w-4" /> {{ now()->format('l, j F Y') }}</div>
            <h1 class="mt-1 text-2xl font-bold sm:text-3xl">{{ $greeting }}, {{ $title ?? strtok(auth()->user()->name, ' ') }}</h1>
            @if ($subtitle)<p class="mt-1 max-w-xl text-sm text-white/80">{{ $subtitle }}</p>@endif
        </div>
        {{ $slot }}
    </div>
</div>
