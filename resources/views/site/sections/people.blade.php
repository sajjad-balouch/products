@include('site.heading')
<div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
    @foreach ($items as $item)
        <div class="text-center">
            @if ($picture = $media($item['image'] ?? null))
                <img src="{{ $picture }}" alt="{{ $item['name'] ?? '' }}" loading="lazy" class="mx-auto h-32 w-32 rounded-full object-cover">
            @endif
            <div class="mt-3 font-semibold">{{ $item['name'] ?? '' }}</div>
            @if (! empty($item['role']))<div class="site-muted text-sm">{{ $item['role'] }}</div>@endif
            @if (! empty($item['bio']))<p class="site-muted mt-2 text-sm whitespace-pre-line">{{ $item['bio'] }}</p>@endif
        </div>
    @endforeach
</div>
