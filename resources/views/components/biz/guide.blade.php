@props(['key', 'compact' => false, 'forceOpen' => false])
@if ($compact)
    {{-- On one-screen forms the guide waits behind a small button and opens over the form. --}}
    <div class="relative" x-data="{ show: false }" x-on:click.outside="show = false" x-on:keydown.escape.window="show = false">
        <button type="button" class="btn btn-sm" x-on:click="show = ! show" :aria-expanded="show"><x-icon name="book-open" class="h-3.5 w-3.5" /> How it works · Kaise?</button>
        <div x-show="show" x-cloak x-transition.opacity class="absolute end-0 top-full z-40 mt-2 w-[min(38rem,92vw)] rounded-xl shadow-xl">
            <x-biz.guide :key="$key" :force-open="true" class="bg-sky-50!" />
        </div>
    </div>
@else
@php($guides = ['en' => \App\Support\Biz\Guides::get($key, 'en'), 'ur' => \App\Support\Biz\Guides::get($key, 'ur')])
@php($headings = ['en' => ['How to fill it in', 'What saving changes', 'Good to know', 'Hide', 'Show guide'], 'ur' => ['Kaise bharein', 'Save karne se kya hoga', 'Yaad rakhein', 'Chhupayein', 'Guide dekhein']])
@if ($guides['en'])
    {{-- Opens the first time; after that each form remembers if it was closed. The language is one choice for all forms. --}}
    <div {{ $attributes->class('rounded-xl border border-sky-200 bg-sky-50/70 text-sm text-slate-700') }} data-guide="{{ $key }}"
         x-data="{
             {{-- Forced open inside the compact pop-over; otherwise remembered per form. --}}
             open: {{ $forceOpen ? 'true' : 'null' }} ?? (() => { try { return localStorage.getItem('biz-guide-{{ $key }}') !== 'closed' } catch (e) { return true } })(),
             lang: (() => { try { return localStorage.getItem('biz-guide-lang') === 'ur' ? 'ur' : 'en' } catch (e) { return 'en' } })(),
             setLang(l) { this.lang = l; this.open = true; try { localStorage.setItem('biz-guide-lang', l) } catch (e) {} window.dispatchEvent(new CustomEvent('biz-guide-lang', { detail: l })) },
         }"
         @unless ($forceOpen) x-init="$watch('open', v => { try { localStorage.setItem('biz-guide-{{ $key }}', v ? 'open' : 'closed') } catch (e) {} })" @endunless
         x-on:biz-guide-lang.window="lang = $event.detail">
        <div class="flex items-center justify-between gap-2 px-3 py-2">
            <button type="button" class="flex min-w-0 items-center gap-2 text-start font-semibold text-sky-900" x-on:click="open = ! open" :aria-expanded="open">
                <x-icon name="book-open" class="h-4 w-4 shrink-0" />
                @foreach ($guides as $lang => $g)<span x-show="lang === '{{ $lang }}'" @if ($lang !== 'en') x-cloak @endif>{{ $g['title'] }}</span>@endforeach
            </button>
            <div class="flex shrink-0 items-center gap-2">
                <div class="flex rounded-md border border-sky-200 bg-white p-0.5 text-xs" role="group" aria-label="Guide language">
                    <button type="button" class="rounded px-1.5 py-0.5" :class="lang === 'en' ? 'bg-sky-600 text-white' : 'text-sky-800'" x-on:click="setLang('en')" :aria-pressed="lang === 'en'">English</button>
                    <button type="button" class="rounded px-1.5 py-0.5" :class="lang === 'ur' ? 'bg-sky-600 text-white' : 'text-sky-800'" x-on:click="setLang('ur')" :aria-pressed="lang === 'ur'">Urdu</button>
                </div>
                @foreach ($headings as $lang => $h)
                    <button type="button" class="text-xs font-medium text-sky-700" x-show="lang === '{{ $lang }}'" @if ($lang !== 'en') x-cloak @endif x-on:click="open = ! open" x-text="open ? @js($h[3]) : @js($h[4])"></button>
                @endforeach
            </div>
        </div>
        <div x-show="open" x-cloak class="border-t border-sky-200 px-3 py-2.5">
            @foreach ($guides as $lang => $g)
                <div class="space-y-2.5" x-show="lang === '{{ $lang }}'" @if ($lang !== 'en') x-cloak @endif>
                    @if ($g['how'])
                        <div>
                            <p class="text-xs font-semibold tracking-wide text-sky-900 uppercase">{{ $headings[$lang][0] }}</p>
                            <ol class="mt-1 list-decimal space-y-0.5 ps-5">@foreach ($g['how'] as $line)<li>{{ $line }}</li>@endforeach</ol>
                        </div>
                    @endif
                    @if ($g['impact'])
                        <div>
                            <p class="text-xs font-semibold tracking-wide text-sky-900 uppercase">{{ $headings[$lang][1] }}</p>
                            <ul class="mt-1 list-disc space-y-0.5 ps-5">@foreach ($g['impact'] as $line)<li>{{ $line }}</li>@endforeach</ul>
                        </div>
                    @endif
                    @if ($g['tips'])
                        <div>
                            <p class="text-xs font-semibold tracking-wide text-sky-900 uppercase">{{ $headings[$lang][2] }}</p>
                            <ul class="mt-1 list-disc space-y-0.5 ps-5">@foreach ($g['tips'] as $line)<li>{{ $line }}</li>@endforeach</ul>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endif
@endif
