@php
    $product = ($d['product'] ?? '') === 'business' ? 'business' : 'school';
    $plans = \App\Models\Plan::where('product', $product)->orderBy('sort')->get();
    $most = $plans->max(fn ($p) => count($p->features));
@endphp
@include('site.heading')
<div @class(['mx-auto grid gap-6', 'max-w-4xl md:grid-cols-2' => $plans->count() <= 2, 'lg:grid-cols-3' => $plans->count() > 2])>
    @foreach ($plans as $plan)
        @php($top = $plans->count() > 1 && count($plan->features) === $most)
        <div @class(['site-plan', 'site-plan-top' => $top])>
            @if ($top)<span class="site-badge absolute -top-3 start-6">Everything included</span>@endif
            <h3 class="text-xl font-bold">{{ $plan->name }}</h3>
            @if ($plan->description)<p class="site-muted mt-1 text-sm">{{ $plan->description }}</p>@endif
            <div class="mt-5">
                @if ($plan->monthly_price)
                    <span class="text-4xl font-extrabold">Rs {{ number_format($plan->monthly_price) }}</span><span class="site-muted text-sm"> / month</span>
                @else
                    <span class="text-2xl font-bold">Ask for the price</span>
                @endif
            </div>
            <ul class="mt-6 flex-1 space-y-2 text-sm">
                @foreach (\App\Models\School::featuresFor($product) as $key => [$label])
                    @if (in_array($key, $plan->features, true))
                        <li class="flex gap-2"><span class="site-check" aria-hidden="true">✓</span>{{ $label }}</li>
                    @else
                        <li class="site-plan-missing flex gap-2"><span aria-hidden="true">–</span><span><span class="sr-only">Not included: </span>{{ $label }}</span></li>
                    @endif
                @endforeach
            </ul>
            @if (! empty($d['button_label']) && ! empty($d['button_url']))
                <a @class(['site-btn site-btn-lg mt-8 w-full', 'site-accent' => $top, 'site-btn-ghost' => ! $top]) href="{{ $d['button_url'] }}">{{ $d['button_label'] }}</a>
            @endif
        </div>
    @endforeach
</div>
@if (! empty($d['note']))<p class="site-muted mt-8 text-center text-sm">{{ $d['note'] }}</p>@endif
