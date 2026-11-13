@php($picture = $media($d['image'] ?? null))
<div class="grid items-center gap-12 md:grid-cols-5">
    @if ($picture)
        <figure @class(['md:col-span-2', 'md:order-2' => ($d['image_side'] ?? 'left') === 'right'])>
            <div class="site-photo-frame mx-auto max-w-sm">
                <img src="{{ $picture }}" alt="{{ $d['name'] ?? '' }}" loading="lazy" class="site-photo aspect-[4/5] w-full object-cover">
            </div>
        </figure>
    @endif
    <div @class(['md:col-span-3' => $picture, 'mx-auto max-w-3xl md:col-span-5' => ! $picture])>
        @include('site.eyebrow')
        @if (! empty($d['heading']))<h2 class="site-h2 mb-5">{{ $d['heading'] }}</h2>@endif
        <div class="site-quote">
            <span class="site-quote-mark" aria-hidden="true">“</span>
            <div class="site-prose">{!! \App\Support\SiteSchema::markdown($d['body'] ?? '') !!}</div>
        </div>
        @if (! empty($d['name']) || ! empty($d['role']))
            <div class="mt-6 border-s-4 border-[var(--site-accent)] ps-4">
                @if (! empty($d['name']))<div class="text-lg font-semibold">{{ $d['name'] }}</div>@endif
                @if (! empty($d['role']))<div class="site-muted text-sm">{{ $d['role'] }}</div>@endif
            </div>
        @endif
        @if (! empty($d['motto']))<div class="site-eyebrow-text mt-4 text-sm font-semibold italic">{{ $d['motto'] }}</div>@endif
    </div>
</div>
