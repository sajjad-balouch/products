<div data-slider data-interval="{{ (int) ($d['interval'] ?? 6) }}" class="site-slider text-white" role="group" aria-roledescription="carousel" aria-label="Pictures">
    @foreach ($items as $slide)
        @php($picture = $media($slide['image'] ?? null))
        <div data-slide @class(['site-slide', 'is-active' => $loop->first]) @unless ($loop->first) aria-hidden="true" @endunless>
            @if ($picture)<div class="site-slide-photo" style="background-image: url('{{ $picture }}')"></div>@endif
            <div class="site-slide-shade"></div>
            <div class="relative mx-auto flex h-full w-full max-w-6xl items-center px-14 py-20">
                <div class="site-slide-text max-w-2xl">
                    @if (! empty($slide['eyebrow']))<div class="site-slide-eyebrow mb-3">{{ $slide['eyebrow'] }}</div>@endif
                    @if (! empty($slide['heading']))
                        @if ($loop->first)<h1 class="text-4xl font-bold text-balance drop-shadow sm:text-6xl">{{ $slide['heading'] }}</h1>@else<h2 class="text-4xl font-bold text-balance drop-shadow sm:text-6xl">{{ $slide['heading'] }}</h2>@endif
                    @endif
                    @if (! empty($slide['text']))<p class="mt-5 max-w-xl text-lg whitespace-pre-line text-white/90">{{ $slide['text'] }}</p>@endif
                    @if (! empty($slide['button_label']) && ! empty($slide['button_url']))<a class="site-btn site-btn-lg site-accent mt-7" href="{{ $slide['button_url'] }}">{{ $slide['button_label'] }} <span aria-hidden="true">→</span></a>@endif
                </div>
            </div>
        </div>
    @endforeach
    @if (count($items) > 1)
        <button type="button" data-prev class="site-slider-arrow start-3" aria-label="Previous picture">‹</button>
        <button type="button" data-next class="site-slider-arrow end-3" aria-label="Next picture">›</button>
        <div class="absolute inset-x-0 bottom-5 z-10 flex justify-center gap-2">
            @foreach ($items as $slide)
                <button type="button" data-dot="{{ $loop->index }}" @class(['site-slider-dot', 'is-active' => $loop->first]) aria-label="Picture {{ $loop->iteration }}"></button>
            @endforeach
        </div>
    @endif
</div>
