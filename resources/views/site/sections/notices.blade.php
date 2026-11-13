@include('site.heading')
<div class="mx-auto grid max-w-5xl gap-5 md:grid-cols-3">
    @forelse (collect($items)->sortByDesc('date') as $item)
        <article class="site-card site-card-hover">
            @if (! empty($item['date']))
                @php($when = \Illuminate\Support\Carbon::parse($item['date']))
                <time class="site-date" datetime="{{ $item['date'] }}"><span class="text-2xl font-bold">{{ $when->format('j') }}</span><span class="text-xs uppercase">{{ $when->format('M Y') }}</span></time>
            @endif
            <h3 class="mt-4 font-semibold">{{ $item['title'] ?? '' }}</h3>
            @if (! empty($item['body']))<p class="mt-2 text-sm whitespace-pre-line text-slate-600">{{ $item['body'] }}</p>@endif
        </article>
    @empty
        @if (! empty($d['empty']))<p class="site-muted text-center text-sm md:col-span-3">{{ $d['empty'] }}</p>@endif
    @endforelse
</div>
