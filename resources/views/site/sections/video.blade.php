@include('site.heading')
@if ($videoId = \App\Support\SiteSchema::youtubeId($d['url'] ?? ''))
    <div class="mx-auto max-w-3xl">
        <iframe class="aspect-video w-full rounded-2xl" src="https://www.youtube-nocookie.com/embed/{{ $videoId }}" title="{{ $d['heading'] ?? 'Video' }}" loading="lazy" allowfullscreen></iframe>
        @if (! empty($d['caption']))<p class="site-muted mt-2 text-center text-sm">{{ $d['caption'] }}</p>@endif
    </div>
@endif
