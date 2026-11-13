@if (! empty($d['heading']) || ! empty($d['intro']) || ! empty($d['_eyebrow']))
    <div class="mx-auto mb-12 max-w-2xl text-center">
        @include('site.eyebrow')
        @if (! empty($d['heading']))<h2 class="site-h2">{{ $d['heading'] }}</h2>@endif
        <div class="site-bar mx-auto mt-4" aria-hidden="true"></div>
        @if (! empty($d['intro']))<p class="site-muted mt-4 text-lg whitespace-pre-line">{{ $d['intro'] }}</p>@endif
    </div>
@endif
