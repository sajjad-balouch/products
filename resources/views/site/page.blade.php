@php
    use App\Support\SiteSchema;

    $design = $site->setting('design');
    $header = $site->setting('header');
    $footer = $site->setting('footer');
    $contact = $site->setting('contact');
    $media = fn ($id) => $id ? route('site.media', $id) : null;
    $primary = $design['primary'] ?: '#0e6a5a';
    $accent = $design['accent'] ?: '#d9822b';
    $socials = collect(['facebook' => 'Facebook', 'instagram' => 'Instagram', 'youtube' => 'YouTube', 'linkedin' => 'LinkedIn', 'x' => 'X'])->filter(fn ($label, $key) => $footer[$key] ?? null);
    $linkFor = fn ($item) => $item->site_page_id ? $site->pageUrl($item->page) : $item->url;
@endphp
<!DOCTYPE html>
<html lang="{{ $design['direction'] === 'rtl' ? 'ur' : 'en' }}" dir="{{ $design['direction'] }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $page->is_home ? $site->name() : $page->title.' · '.$site->name() }}</title>
    <meta name="description" content="{{ $page->seo_description ?: $design['seo_description'] }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Nastaliq+Urdu:wght@400;600&family=Source+Serif+4:wght@400;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/site.js'])
    <style>
        :root {
            --site-primary: {{ $primary }};
            --site-on-primary: {{ SiteSchema::textOn($primary) }};
            --site-accent: {{ $accent }};
            --site-on-accent: {{ SiteSchema::textOn($accent) }};
        }
    </style>
</head>
<body id="top" class="site-body site-font-{{ $design['font'] }} bg-white text-slate-900 antialiased">
    @if ($canEdit)
        <div class="flex flex-wrap items-center justify-between gap-2 bg-slate-900 px-4 py-2 text-xs text-white">
            <span>
                You can edit this website.
                @unless ($site->is_published)<strong class="text-amber-300">The website is a draft, and only editors can see it.</strong>@endunless
                @unless ($page->is_published)<strong class="text-amber-300">This page is hidden from visitors.</strong>@endunless
            </span>
            <span class="flex gap-3">
                @if ($canEditPage)<a class="underline" href="{{ route('website.page', $page->id) }}">Edit this page</a>@endif
                <a class="underline" href="{{ route('website.pages') }}">Manage website</a>
            </span>
        </div>
    @endif

    @if ($header['topbar_show'] && $header['topbar_text'])
        <div class="site-accent px-4 py-1.5 text-center text-sm">{{ $header['topbar_text'] }}</div>
    @endif

    <header data-site-header @class(['site-header site-tone-'.$header['style'], 'sticky top-0 z-30' => $header['sticky']])>
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3">
            <a href="{{ $site->url() }}" class="flex min-w-0 items-center gap-2 font-bold">
                @if ($logo = $media($design['logo']))<img src="{{ $logo }}" alt="{{ $site->name() }}" class="h-10 w-auto">@endif
                @if ($design['show_name'] || ! $design['logo'])<span class="line-clamp-2 max-w-52 text-base leading-tight">{{ $site->name() }}</span>@endif
            </a>

            <nav class="hidden items-center gap-4 text-sm font-medium xl:flex" aria-label="Main">
                @foreach ($headerMenu as $item)
                    @if ($item->children->isEmpty())
                        <a href="{{ $linkFor($item) }}" class="site-navlink whitespace-nowrap" @if ($item->new_tab) target="_blank" rel="noopener" @endif>{{ $item->label }}</a>
                    @else
                        <div class="group relative">
                            <a href="{{ $linkFor($item) }}" class="site-navlink whitespace-nowrap" aria-haspopup="true">{{ $item->label }} <span aria-hidden="true">▾</span></a>
                            <div class="invisible absolute start-0 top-full z-40 w-56 pt-2 opacity-0 transition-opacity group-focus-within:visible group-focus-within:opacity-100 group-hover:visible group-hover:opacity-100">
                                <div class="rounded-xl border border-slate-200 bg-white p-1.5 font-normal text-slate-900 shadow-xl">
                                    @foreach ($item->children as $child)
                                        <a href="{{ $linkFor($child) }}" class="block rounded px-3 py-1.5 hover:bg-slate-100" @if ($child->new_tab) target="_blank" rel="noopener" @endif>{{ $child->label }}</a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </nav>

            <div class="flex shrink-0 items-center gap-2">
                @if ($header['login_show'] || $viewer)
                    <a class="site-btn site-btn-ghost hidden sm:inline-flex" href="{{ $viewer ? route($viewer->homeRoute()) : route('login') }}">{{ $viewer ? 'Dashboard' : ($header['login_label'] ?: 'Sign in') }}</a>
                @endif
                @if ($header['cta_show'] && $header['cta_label'] && $header['cta_url'])
                    <a class="site-btn site-accent hidden sm:inline-flex" href="{{ $header['cta_url'] }}">{{ $header['cta_label'] }}</a>
                @endif
                <details class="relative xl:hidden">
                    <summary class="site-btn site-btn-ghost cursor-pointer list-none">Menu</summary>
                    <div class="absolute end-0 z-40 mt-2 max-h-[75vh] w-64 overflow-y-auto rounded-lg border border-slate-200 bg-white p-2 text-sm text-slate-900 shadow-lg">
                        @foreach ($headerMenu as $item)
                            @if ($item->children->isEmpty() || $linkFor($item) !== '#')
                                <a href="{{ $linkFor($item) }}" class="block rounded px-3 py-2 hover:bg-slate-100">{{ $item->label }}</a>
                            @else
                                <div class="px-3 pt-2 pb-1 text-xs font-semibold text-slate-500">{{ $item->label }}</div>
                            @endif
                            @foreach ($item->children as $child)
                                <a href="{{ $linkFor($child) }}" class="block rounded py-1.5 ps-6 pe-3 text-slate-600 hover:bg-slate-100">{{ $child->label }}</a>
                            @endforeach
                        @endforeach
                        @if ($header['login_show'] || $viewer)
                            <a class="block rounded px-3 py-2 hover:bg-slate-100 sm:hidden" href="{{ $viewer ? route($viewer->homeRoute()) : route('login') }}">{{ $viewer ? 'Dashboard' : ($header['login_label'] ?: 'Sign in') }}</a>
                        @endif
                        @if ($header['cta_show'] && $header['cta_label'] && $header['cta_url'])
                            <a class="block rounded px-3 py-2 font-medium hover:bg-slate-100 sm:hidden" href="{{ $header['cta_url'] }}">{{ $header['cta_label'] }}</a>
                        @endif
                    </div>
                </details>
            </div>
        </div>
    </header>

    <main>
        @forelse ($sections as $section)
            @php
                $d = $section->data ?? [];
                $anchor = \Illuminate\Support\Str::slug($d['_anchor'] ?? '') ?: 'section-'.$section->id;
                // Visitors never see a heading with nothing under it.
                $isEmpty = (array_key_exists('items', $d) && ! $d['items'] && $section->type !== 'notices') || ($section->type === 'campuses' && ! $site->campuses())
                    || ($section->type === 'plans' && ($site->school_id || ! \App\Models\Plan::exists()));
            @endphp
            @continue($isEmpty)
            @if ($section->type === 'slider')
                <section id="{{ $anchor }}" class="scroll-mt-20">@include('site.sections.slider', ['d' => $d, 'items' => $d['items'] ?? []])</section>
            @else
                <section id="{{ $anchor }}" class="scroll-mt-20 site-bg-{{ $d['_bg'] ?? 'white' }} site-pad-{{ $d['_spacing'] ?? 'normal' }}">
                    <div class="site-reveal mx-auto max-w-6xl px-4">
                        @include('site.sections.'.$section->type, ['d' => $d, 'items' => $d['items'] ?? []])
                    </div>
                </section>
            @endif
        @empty
            <section class="site-pad-large"><div class="mx-auto max-w-6xl px-4"><h1 class="text-3xl font-bold">{{ $page->title }}</h1></div></section>
        @endforelse
    </main>

    @if ($footer['show'])
        @php
            $showMenu = $footer['show_menu'] && $footerMenu->isNotEmpty();
            $showContact = $footer['show_contact'] && ($contact['phone'] || $contact['email'] || $contact['whatsapp'] || ($contact['address'] && ! $site->campuses()));
            $showCampuses = $footer['show_campuses'] && $site->campuses();
        @endphp
        <footer class="site-footer site-tone-{{ $footer['style'] }}">
            <div class="mx-auto grid max-w-6xl gap-10 px-4 py-14 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <a href="{{ $site->url() }}" class="flex items-center gap-2 text-lg font-bold">
                        @if ($logo = $media($design['logo']))<img src="{{ $logo }}" alt="" class="h-10 w-auto rounded bg-white p-1">@endif
                        <span>{{ $site->name() }}</span>
                    </a>
                    @if ($footer['about'] && $footer['about'] !== $site->name())<p class="site-muted mt-3 max-w-xs text-sm whitespace-pre-line">{{ $footer['about'] }}</p>@endif
                    @if ($footer['show_social'] && $socials->isNotEmpty())
                        <div class="mt-4 flex flex-wrap gap-2">
                            @foreach ($socials as $key => $label)
                                <a class="site-social" href="{{ $footer[$key] }}" target="_blank" rel="noopener">{{ $label }}</a>
                            @endforeach
                        </div>
                    @endif
                </div>
                @if ($showMenu)
                    <nav aria-label="Footer">
                        <h2 class="site-footer-title">Quick links</h2>
                        <ul class="space-y-2 text-sm">
                            @foreach ($footerMenu as $item)
                                <li><a class="site-footer-link" href="{{ $linkFor($item) }}" @if ($item->new_tab) target="_blank" rel="noopener" @endif>{{ $item->label }}</a></li>
                            @endforeach
                        </ul>
                    </nav>
                @endif
                @if ($showContact)
                    <div class="space-y-2 text-sm">
                        <h2 class="site-footer-title">Contact</h2>
                        @include('site.contact-details', ['contact' => $contact])
                    </div>
                @endif
                @if ($showCampuses)
                    <div class="space-y-3 text-sm">
                        <h2 class="site-footer-title">{{ count($site->campuses()) > 1 ? 'Our campuses' : 'Visit us' }}</h2>
                        @foreach ($site->campuses() as $campus)
                            <div>
                                <div class="font-semibold">{{ $campus['name'] }}</div>
                                @if ($campus['address'])<div class="site-muted whitespace-pre-line">{{ $campus['address'] }}</div>@endif
                                @if ($campus['phone'])<a class="site-footer-link" href="tel:{{ preg_replace('/[^\d+]/', '', $campus['phone']) }}">{{ $campus['phone'] }}</a>@endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
            <div class="border-t border-current/10">
                <div class="site-muted mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-2 px-4 py-5 text-xs">
                    <span>{{ $footer['copyright'] ?: '© '.now()->year.' '.$site->name().'. All rights reserved.' }}</span>
                    <a class="site-footer-link" href="#top">Back to top ↑</a>
                </div>
            </div>
        </footer>
    @endif

    @php($popup = $site->setting('popup'))
    @if ($popup['show'] && ($popup['heading'] || $popup['text'] || $popup['image']))
        <dialog data-popup="{{ $site->id }}-{{ md5(json_encode($popup)) }}" class="m-auto max-h-[90dvh] w-[calc(100%-2rem)] max-w-md overflow-y-auto overscroll-contain rounded-2xl p-0 text-slate-900 shadow-2xl" aria-labelledby="popup-heading">
            @if ($picture = $media($popup['image']))<img src="{{ $picture }}" alt="" class="max-h-64 w-full object-cover">@endif
            <div class="p-5 text-center">
                @if ($popup['heading'])<h2 id="popup-heading" class="text-xl font-bold">{{ $popup['heading'] }}</h2>@endif
                @if ($popup['text'])<p class="mt-2 text-sm whitespace-pre-line text-slate-600">{{ $popup['text'] }}</p>@endif
                <form method="dialog" class="mt-4 flex flex-wrap justify-center gap-2">
                    @if ($popup['button_label'] && $popup['button_url'])<a class="site-btn site-accent" href="{{ $popup['button_url'] }}">{{ $popup['button_label'] }}</a>@endif
                    <button class="site-btn site-btn-ghost">Close</button>
                </form>
            </div>
        </dialog>
    @endif
</body>
</html>
