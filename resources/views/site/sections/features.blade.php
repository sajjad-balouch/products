@include('site.heading')
@if (($d['style'] ?? 'cards') === 'bento')
    {{-- Mixed sizes: the 1st and 4th cards of every six take two columns. --}}
    <div class="grid gap-5 md:grid-cols-3">
        @foreach ($items as $item)
            @php($picture = $media($item['image'] ?? null))
            <div @class(['site-bento site-card-hover', 'md:col-span-2' => in_array($loop->index % 6, [0, 3], true)])>
                <div class="p-6 pb-0">
                    <h3 class="text-lg font-semibold">{{ $item['title'] ?? '' }}</h3>
                    @if (! empty($item['text']))<div class="site-prose mt-2 text-sm text-slate-600">{!! \App\Support\SiteSchema::markdown($item['text']) !!}</div>@endif
                    @if (! empty($item['link']))<a class="site-link mt-3 inline-block text-sm font-semibold" href="{{ $item['link'] }}">Read more <span aria-hidden="true">→</span></a>@endif
                </div>
                @if ($picture)
                    <div class="site-bento-shot site-zoom"><img src="{{ $picture }}" alt="" loading="lazy" class="w-full object-cover object-top"></div>
                @else
                    <div class="site-bento-blank" aria-hidden="true">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</div>
                @endif
            </div>
        @endforeach
    </div>
@else
    <div @class(['grid gap-6 sm:grid-cols-2', 'lg:grid-cols-3' => ($d['columns'] ?? '3') === '3', 'lg:grid-cols-4' => ($d['columns'] ?? '3') === '4'])>
        @foreach ($items as $item)
            <div class="site-card site-card-hover flex flex-col overflow-hidden">
                @if ($picture = $media($item['image'] ?? null))
                    <div class="site-zoom -mx-6 -mt-6 mb-5"><img src="{{ $picture }}" alt="" loading="lazy" class="aspect-[16/10] w-full object-cover"></div>
                @else
                    <div class="site-card-icon" aria-hidden="true">{{ str_pad((string) $loop->iteration, 2, '0', STR_PAD_LEFT) }}</div>
                @endif
                <h3 class="text-lg font-semibold">{{ $item['title'] ?? '' }}</h3>
                @if (! empty($item['text']))<div class="site-prose mt-2 flex-1 text-sm text-slate-600">{!! \App\Support\SiteSchema::markdown($item['text']) !!}</div>@endif
                @if (! empty($item['link']))<a class="site-link mt-4 text-sm font-semibold" href="{{ $item['link'] }}">Read more <span aria-hidden="true">→</span></a>@endif
            </div>
        @endforeach
    </div>
@endif
