@php($picture = $media($d['image'] ?? null))
<div @class(['grid items-center gap-12', 'md:grid-cols-2' => $picture, 'mx-auto max-w-3xl' => ! $picture])>
    <div @class(['md:order-2' => $picture && ($d['image_side'] ?? 'right') === 'left'])>
        @include('site.eyebrow')
        @if (! empty($d['heading']))<h2 class="site-h2 mb-5">{{ $d['heading'] }}</h2>@endif
        <div class="site-prose site-muted">{!! \App\Support\SiteSchema::markdown($d['body'] ?? '') !!}</div>
        @php($tags = array_filter(array_map('trim', explode("\n", $d['highlights'] ?? ''))))
        @if ($tags)
            <div class="mt-6 grid gap-2 sm:grid-cols-2">
                @foreach ($tags as $tag)<div class="site-tag"><span class="site-tag-dot" aria-hidden="true">✓</span>{{ $tag }}</div>@endforeach
            </div>
        @endif
        @if (! empty($d['button_label']) && ! empty($d['button_url']))<a class="site-btn site-btn-lg site-accent mt-7" href="{{ $d['button_url'] }}">{{ $d['button_label'] }}</a>@endif
    </div>
    @if ($picture)
        <div @class(['site-photo-frame', 'site-photo-frame-left' => ($d['image_side'] ?? 'right') === 'left'])>
            <img src="{{ $picture }}" alt="{{ $d['heading'] ?? '' }}" loading="lazy" class="site-photo aspect-[4/3] w-full object-cover">
        </div>
    @endif
</div>
