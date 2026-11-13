@include('site.heading')
<div class="flex flex-wrap justify-center gap-x-14 gap-y-6 text-center">
    @foreach ($items as $item)
        <div>
            <div class="text-4xl font-bold">{{ $item['value'] ?? '' }}</div>
            <div class="site-muted mt-1 text-sm">{{ $item['label'] ?? '' }}</div>
        </div>
    @endforeach
</div>
