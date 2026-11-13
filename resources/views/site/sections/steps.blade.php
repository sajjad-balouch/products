@include('site.heading')
<ol class="site-steps grid gap-8 md:grid-cols-3">
    @foreach ($items as $item)
        <li class="relative text-center">
            <div class="site-step-number mx-auto">{{ $loop->iteration }}</div>
            <h3 class="mt-5 text-lg font-semibold">{{ $item['title'] ?? '' }}</h3>
            @if (! empty($item['text']))<p class="site-muted mx-auto mt-2 max-w-xs text-sm whitespace-pre-line">{{ $item['text'] }}</p>@endif
        </li>
    @endforeach
</ol>
