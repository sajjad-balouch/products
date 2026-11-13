<div class="mx-auto max-w-3xl">
    @include('site.eyebrow')
    @if (! empty($d['heading']))<h2 class="mb-4 text-2xl font-bold sm:text-3xl">{{ $d['heading'] }}</h2>@endif
    <div class="site-prose">{!! \App\Support\SiteSchema::markdown($d['body'] ?? '') !!}</div>
</div>
