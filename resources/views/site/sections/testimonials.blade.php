@include('site.heading')
<div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
    @foreach ($items as $item)
        <blockquote class="site-card">
            <p class="text-sm whitespace-pre-line">{{ $item['quote'] ?? '' }}</p>
            <footer class="mt-3 text-sm font-semibold">{{ $item['name'] ?? '' }} @if (! empty($item['role']))<span class="font-normal text-slate-500">{{ $item['role'] }}</span>@endif</footer>
        </blockquote>
    @endforeach
</div>
