<div class="site-cta text-center">
    @include('site.eyebrow')
    @if (! empty($d['heading']))<h2 class="site-h2">{{ $d['heading'] }}</h2>@endif
    @if (! empty($d['text']))<p class="site-muted mx-auto mt-3 max-w-2xl text-lg whitespace-pre-line">{{ $d['text'] }}</p>@endif
    @if (! empty($d['button_label']) && ! empty($d['button_url']))<a class="site-btn site-btn-lg site-accent mt-7" href="{{ $d['button_url'] }}">{{ $d['button_label'] }} <span aria-hidden="true">→</span></a>@endif
</div>
