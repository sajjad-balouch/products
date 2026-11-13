@include('site.heading')
<div @class(['grid gap-6', 'md:grid-cols-2' => count($site->campuses()) > 1, 'mx-auto max-w-3xl' => count($site->campuses()) === 1])>
    @foreach ($site->campuses() as $campus)
        <article class="site-card site-card-hover overflow-hidden p-0!">
            @if ($picture = $media($campus['image'] ?? null))
                <div class="site-zoom relative">
                    <img src="{{ $picture }}" alt="{{ $campus['name'] ?? '' }}" loading="lazy" class="aspect-[16/9] w-full object-cover">
                    @if (! empty($campus['badge']))<span class="site-badge absolute start-4 top-4">{{ $campus['badge'] }}</span>@endif
                </div>
            @endif
            <div class="p-6">
                <h3 class="text-xl font-semibold">{{ $campus['name'] ?? '' }}</h3>
                @if (! empty($campus['badge']) && ! ($campus['image'] ?? null))<div class="site-eyebrow-text text-xs font-semibold">{{ $campus['badge'] }}</div>@endif
                @if (! empty($campus['address']))<p class="mt-2 text-sm whitespace-pre-line text-slate-600"><span aria-hidden="true">📍</span> {{ $campus['address'] }}</p>@endif
                @if (! empty($campus['about']))<p class="mt-3 text-sm whitespace-pre-line text-slate-600">{{ $campus['about'] }}</p>@endif
                <div class="mt-5 flex flex-wrap gap-2">
                    @if (! empty($campus['phone']))<a class="site-btn site-accent" href="tel:{{ preg_replace('/[^\d+]/', '', $campus['phone']) }}">Call {{ $campus['phone'] }}</a>@endif
                    @if (! empty($campus['address']))<a class="site-btn site-btn-ghost" href="https://www.google.com/maps/search/?api=1&query={{ urlencode($campus['address']) }}" target="_blank" rel="noopener">Directions</a>@endif
                </div>
            </div>
        </article>
    @endforeach
</div>
