@php
    $layout = $d['layout'] ?? 'center';
    $picture = $media($d['image'] ?? null);
    $buttons = array_filter([[$d['button_label'] ?? '', $d['button_url'] ?? '', 'site-accent'], [$d['button2_label'] ?? '', $d['button2_url'] ?? '', 'site-btn-ghost']], fn ($b) => $b[0] && $b[1]);
@endphp
@if ($layout === 'product')
    @php
        $heading = e($d['heading'] ?? '');
        $highlight = trim($d['highlight'] ?? '');
        // Both parts are escaped first, so the only markup added is the span.
        if ($highlight !== '' && str_contains($heading, e($highlight))) {
            $heading = \Illuminate\Support\Str::replaceFirst(e($highlight), '<span class="site-gradient-text">'.e($highlight).'</span>', $heading);
        }
        $chips = array_filter(array_map('trim', explode("\n", $d['chips'] ?? '')));
        $phone = $media($d['image2'] ?? null);
    @endphp
    <div class="site-product-hero relative">
        <div class="site-glow site-glow-a" aria-hidden="true"></div>
        <div class="site-glow site-glow-b" aria-hidden="true"></div>
        <div class="relative mx-auto max-w-4xl text-center">
            @include('site.eyebrow')
            <h1 class="text-4xl font-extrabold tracking-tight text-balance sm:text-6xl">{!! $heading !!}</h1>
            @if (! empty($d['text']))<p class="site-muted mx-auto mt-6 max-w-2xl text-lg whitespace-pre-line sm:text-xl">{{ $d['text'] }}</p>@endif
            @if ($buttons)<div class="mt-9 flex flex-wrap justify-center gap-3">@foreach ($buttons as [$label, $url, $class])<a class="site-btn site-btn-lg {{ $class }}" href="{{ $url }}">{{ $label }}</a>@endforeach</div>@endif
            @if ($chips)
                <ul class="mt-7 flex flex-wrap justify-center gap-x-6 gap-y-2 text-sm">
                    @foreach ($chips as $chip)<li class="flex items-center gap-1.5"><span class="site-check" aria-hidden="true">✓</span>{{ $chip }}</li>@endforeach
                </ul>
            @endif
        </div>
        @if ($picture)
            <div class="site-hero-shot relative mx-auto mt-14 max-w-5xl">
                @include('site.browser', ['src' => $picture, 'address' => parse_url(config('app.url'), PHP_URL_HOST).'/dashboard', 'eager' => true, 'class' => 'site-tilt'])
                @if ($phone)<img src="{{ $phone }}" alt="" class="site-phone site-float absolute -end-2 -bottom-10 hidden w-44 sm:block lg:-end-10 lg:w-52">@endif
            </div>
        @endif
    </div>
@elseif ($layout === 'cover' && $picture)
    <div class="relative overflow-hidden rounded-3xl bg-cover bg-center px-6 py-24 text-center text-white shadow-xl" style="background-image: linear-gradient(rgb(0 0 0 / .55), rgb(0 0 0 / .55)), url('{{ $picture }}')">
        @include('site.eyebrow')
        <h1 class="mx-auto max-w-4xl text-4xl font-bold text-balance sm:text-5xl">{{ $d['heading'] ?? '' }}</h1>
        @if (! empty($d['text']))<p class="mx-auto mt-4 max-w-2xl text-lg whitespace-pre-line">{{ $d['text'] }}</p>@endif
        <div class="mt-8 flex flex-wrap justify-center gap-3">@foreach ($buttons as [$label, $url, $class])<a class="site-btn site-btn-lg {{ $class }}" href="{{ $url }}">{{ $label }}</a>@endforeach</div>
    </div>
@elseif ($layout === 'split' && $picture)
    <div class="grid items-center gap-12 md:grid-cols-2">
        <div>
            @include('site.eyebrow')
            <h1 class="text-4xl font-bold text-balance sm:text-5xl">{{ $d['heading'] ?? '' }}</h1>
            @if (! empty($d['text']))<p class="site-muted mt-5 text-lg whitespace-pre-line">{{ $d['text'] }}</p>@endif
            <div class="mt-8 flex flex-wrap gap-3">@foreach ($buttons as [$label, $url, $class])<a class="site-btn site-btn-lg {{ $class }}" href="{{ $url }}">{{ $label }}</a>@endforeach</div>
        </div>
        <div class="site-photo-frame">
            <img src="{{ $picture }}" alt="" class="site-photo aspect-[4/3] w-full object-cover">
        </div>
    </div>
@else
    <div class="text-center">
        @include('site.eyebrow')
        <h1 class="mx-auto max-w-4xl text-4xl font-bold text-balance sm:text-5xl">{{ $d['heading'] ?? '' }}</h1>
        @if (! empty($d['text']))<p class="site-muted mx-auto mt-5 max-w-2xl text-lg whitespace-pre-line">{{ $d['text'] }}</p>@endif
        @if ($buttons)<div class="mt-8 flex flex-wrap justify-center gap-3">@foreach ($buttons as [$label, $url, $class])<a class="site-btn site-btn-lg {{ $class }}" href="{{ $url }}">{{ $label }}</a>@endforeach</div>@endif
        @if ($picture)<img src="{{ $picture }}" alt="" class="site-photo mx-auto mt-12 max-h-[28rem] w-full max-w-4xl object-cover">@endif
    </div>
@endif
