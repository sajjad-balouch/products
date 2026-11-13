@php($pictures = collect($items)->filter(fn ($i) => ! empty($i['image'])))
@php($categories = $pictures->pluck('category')->filter()->countBy())
@include('site.heading')
<div data-gallery>
    @if ($categories->isNotEmpty())
        <div class="mb-8 flex flex-wrap justify-center gap-2" role="group" aria-label="Filter pictures">
            <button type="button" class="site-filter" data-filter="" aria-pressed="true">All ({{ $pictures->count() }})</button>
            @foreach ($categories as $category => $count)
                <button type="button" class="site-filter" data-filter="{{ $category }}" aria-pressed="false">{{ $category }} ({{ $count }})</button>
            @endforeach
        </div>
    @endif
    <div class="grid grid-cols-2 gap-4 md:grid-cols-3 lg:grid-cols-4">
        @foreach ($pictures as $item)
            <figure data-category="{{ $item['category'] ?? '' }}" class="site-gallery-item">
                <button type="button" class="site-zoom block w-full cursor-zoom-in rounded-2xl" data-full="{{ $media($item['image']) }}" aria-label="Open picture{{ ! empty($item['caption']) ? ': '.$item['caption'] : '' }}">
                    <img src="{{ $media($item['image']) }}" alt="{{ $item['caption'] ?? '' }}" loading="lazy" class="aspect-square w-full object-cover">
                    @if (! empty($item['caption']))<figcaption class="site-gallery-caption">{{ $item['caption'] }}</figcaption>@endif
                </button>
            </figure>
        @endforeach
    </div>
    <dialog data-lightbox class="m-auto max-w-[95vw] rounded-2xl bg-black p-0">
        <form method="dialog" class="text-end"><button class="px-4 py-2 text-sm text-white" aria-label="Close picture">Close ✕</button></form>
        <img alt="" class="max-h-[85vh] max-w-full object-contain">
    </dialog>
</div>
