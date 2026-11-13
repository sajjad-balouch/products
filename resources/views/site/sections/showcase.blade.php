@php($phone = $media($d['image2'] ?? null))
@include('site.heading')
@if ($picture = $media($d['image'] ?? null))
    <figure class="relative mx-auto max-w-5xl">
        <div class="site-glow site-glow-a opacity-60" aria-hidden="true"></div>
        @include('site.browser', ['src' => $picture, 'address' => $d['address'] ?? '', 'alt' => $d['caption'] ?? ($d['heading'] ?? ''), 'class' => 'relative'])
        @if ($phone)<img src="{{ $phone }}" alt="" loading="lazy" class="site-phone site-float absolute -start-4 -bottom-8 hidden w-40 sm:block lg:-start-12 lg:w-48">@endif
        @if (! empty($d['caption']))<figcaption class="site-muted mt-6 text-center text-sm">{{ $d['caption'] }}</figcaption>@endif
    </figure>
@endif
