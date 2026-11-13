@include('site.heading')
<div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
    @foreach ($items as $item)
        <div class="site-card flex flex-col">
            <h3 class="font-semibold">{{ $item['name'] ?? '' }}</h3>
            <div class="mt-2 text-3xl font-bold">{{ $item['price'] ?? '' }}</div>
            @if (! empty($item['note']))<div class="text-sm text-slate-500">{{ $item['note'] }}</div>@endif
            <ul class="mt-4 flex-1 list-disc space-y-1 ps-5 text-sm text-slate-600">
                @foreach (array_filter(array_map('trim', explode("\n", $item['lines'] ?? ''))) as $line)<li>{{ $line }}</li>@endforeach
            </ul>
            @if (! empty($item['button_label']) && ! empty($item['button_url']))<a class="site-btn site-accent mt-4 justify-center" href="{{ $item['button_url'] }}">{{ $item['button_label'] }}</a>@endif
        </div>
    @endforeach
</div>
