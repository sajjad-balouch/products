@include('site.heading')
<div class="mx-auto max-w-3xl space-y-2">
    @foreach ($items as $item)
        <details class="site-card">
            <summary class="cursor-pointer font-medium">{{ $item['question'] ?? '' }}</summary>
            <p class="mt-2 text-sm text-slate-600 whitespace-pre-line">{{ $item['answer'] ?? '' }}</p>
        </details>
    @endforeach
</div>
