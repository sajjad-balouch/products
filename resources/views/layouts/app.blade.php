<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ur' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }} · {{ auth()->user()?->school?->name ?? config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&family=Noto+Nastaliq+Urdu:wght@400;600&display=swap" rel="stylesheet">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#0a2f2a">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-surface font-sans text-slate-900 antialiased" x-data="{ nav: false, toast: @js(session('toast', '')), toastError: false }" x-init="toast && setTimeout(() => toast = '', 6000)"
      @toast.window="toast = $event.detail.message; toastError = !!$event.detail.error; setTimeout(() => toast = '', toastError ? 8000 : 4000)">
@php($user = auth()->user())
@php($initials = collect(preg_split('/\s+/', trim($user->name)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode(''))

@if ($user->isRole('parent', 'student'))
    {{-- Parents and students: a phone-first app with one header. --}}
    <header class="sticky top-0 z-30 overflow-hidden bg-brand-900 text-white shadow-lift">
        <div class="bg-dots pointer-events-none absolute inset-0 opacity-50"></div>
        <div class="pointer-events-none absolute -end-10 -top-16 h-40 w-40 rounded-full bg-brand-400/30 blur-2xl"></div>
        <div class="relative mx-auto flex max-w-3xl items-center justify-between gap-3 px-4 py-3">
            <div class="flex min-w-0 items-center gap-3">
                <div class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl bg-white/10 ring-1 ring-white/20"><x-icon name="school" class="h-5 w-5" /></div>
                <div class="min-w-0">
                    <div class="font-display truncate text-[15px] font-bold">{{ $user->school->name }}</div>
                    <div class="truncate text-xs text-brand-100/80">{{ $user->name }}</div>
                </div>
            </div>
            <div class="flex items-center gap-1.5">
                <a href="{{ route('locale', app()->getLocale() === 'ur' ? 'en' : 'ur') }}" class="rounded-full bg-white/10 px-3 py-1.5 text-xs font-semibold ring-1 ring-white/15 hover:bg-white/20">{{ app()->getLocale() === 'ur' ? 'English' : 'اردو' }}</a>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="grid h-8 w-8 place-items-center rounded-full bg-white/10 ring-1 ring-white/15 hover:bg-white/20" title="{{ __('Sign out') }}" aria-label="{{ __('Sign out') }}"><x-icon name="logout" class="h-4 w-4" /></button></form>
            </div>
        </div>
    </header>
    <main class="mx-auto max-w-3xl px-4 py-5">{{ $slot }}</main>
@else
    @php($badges = \App\Support\Badges::for($user))
    @php($menu = \App\Support\Navigation::for($user))
    @php($roleLabel = ['super_admin' => 'Software owner', 'admin' => $user->school?->isBusiness() ? 'Owner' : 'Principal / Admin', 'accountant' => 'Accountant', 'teacher' => 'Teacher', 'website_editor' => 'Website editor'][$user->role] ?? ucfirst($user->role))
    {{-- Where this page sits in the menu, for the bar at the top. --}}
    @php($here = collect($menu)->flatMap(fn ($links, $group) => collect($links)->map(fn ($l) => ['group' => $group, 'label' => $l[0], 'route' => $l[1]]))->first(fn ($l) => request()->routeIs($l['route'])))
    {{-- Red counts: drawn now, then refreshed every minute without reloading the page. --}}
    <div class="lg:flex" x-data="{
            badges: @js($badges), list: @js(\App\Support\Badges::list($user)), bell: false, me: false, q: '', title: document.title.replace(/^\(\d+\+?\)\s*/, ''),
            get total() { return Object.values(this.badges).reduce((s, n) => s + n, 0) },
            show(n) { return n > 99 ? '99+' : n },
            match(label) { return !this.q || label.toLowerCase().includes(this.q.trim().toLowerCase()) },
            paint() { document.title = (this.total ? '(' + this.show(this.total) + ') ' : '') + this.title },
            async refresh() { try { const r = await fetch(@js(route('badges')), { headers: { Accept: 'application/json' } }); if (r.ok) { const b = await r.json(); this.badges = b.badges; this.list = b.list; this.paint() } } catch (e) {} },
        }" x-init="paint(); setInterval(() => document.visibilityState === 'visible' && refresh(), 60000); document.addEventListener('visibilitychange', () => document.visibilityState === 'visible' && refresh())"
        @keydown.window.slash="if (! ['INPUT', 'TEXTAREA', 'SELECT'].includes(document.activeElement.tagName)) { $event.preventDefault(); nav = true; $nextTick(() => $refs.menuSearch.focus()) }">
        <div x-show="nav" x-cloak x-transition.opacity class="fixed inset-0 z-30 bg-slate-950/50 backdrop-blur-sm lg:hidden" @click="nav = false"></div>

        {{-- ===== Sidebar ===== --}}
        <aside class="fixed inset-y-0 start-0 z-40 flex w-[272px] -translate-x-full flex-col overflow-hidden bg-brand-950 transition-transform duration-300 lg:sticky lg:top-0 lg:h-screen lg:translate-x-0 no-print"
               :class="nav && 'translate-x-0!'">
            <div class="pointer-events-none absolute inset-0 bg-gradient-to-b from-brand-900 via-brand-950 to-[#04161300]"></div>
            <div class="pointer-events-none absolute -start-24 -top-24 h-64 w-64 rounded-full bg-brand-500/20 blur-3xl"></div>
            <div class="relative flex items-center gap-3 px-5 pt-5 pb-4">
                <div class="grid h-10 w-10 shrink-0 place-items-center rounded-2xl bg-gradient-to-br from-accent-400 to-accent-600 text-white shadow-lg shadow-accent-600/30 ring-1 ring-white/20"><x-icon name="school" class="h-5 w-5" /></div>
                <div class="min-w-0">
                    <div class="font-display truncate text-[17px] font-extrabold tracking-tight text-white">{{ config('app.name') }}</div>
                    <div class="truncate text-xs text-brand-100/60">{{ $user->school?->name ?? 'Owner panel' }}</div>
                </div>
                <button class="ms-auto grid h-8 w-8 place-items-center rounded-lg text-brand-100 hover:bg-white/10 lg:hidden" @click="nav = false" aria-label="Close menu"><x-icon name="x-circle" class="h-5 w-5" /></button>
            </div>
            <div class="relative px-4 pb-3">
                <div class="relative">
                    <x-icon name="search" class="pointer-events-none absolute start-3 top-1/2 h-4 w-4 -translate-y-1/2 text-brand-100/50" />
                    <input x-ref="menuSearch" x-model="q" @keydown.escape="q = ''; $el.blur()" type="search" placeholder="Search the menu"
                           class="w-full rounded-xl border border-white/10 bg-white/[.06] py-2 ps-9 pe-8 text-sm text-white placeholder:text-brand-100/45 focus:border-white/25 focus:bg-white/10 focus:outline-none">
                    <kbd class="pointer-events-none absolute end-2.5 top-1/2 -translate-y-1/2 rounded-md border border-white/15 px-1.5 text-[10px] font-semibold text-brand-100/50" x-show="!q">/</kbd>
                </div>
            </div>
            <nav class="thin-scroll relative flex-1 space-y-5 overflow-y-auto px-3 pb-4" aria-label="Main">
                @foreach ($menu as $group => $links)
                    <div x-show="{{ collect($links)->map(fn ($l) => 'match('.\Illuminate\Support\Js::from($l[0]).')')->implode(' || ') }}">
                        @if ($group)<div x-show="!q" class="mb-1.5 px-3 text-[10.5px] font-bold tracking-[.12em] text-brand-100/40 uppercase">{{ $group }}</div>@endif
                        <div class="space-y-0.5">
                            @foreach ($links as [$label, $route])
                                <a href="{{ route($route) }}" wire:navigate x-show="match(@js($label))" @class(['nav-link', 'nav-active' => request()->routeIs($route)]) @if (request()->routeIs($route)) aria-current="page" @endif>
                                    <x-icon :name="\App\Support\Navigation::icon($route)" class="h-[18px] w-[18px] shrink-0" />
                                    <span class="truncate">{{ $label }}</span>
                                    <span x-show="badges[@js($route)]" @if (! isset($badges[$route])) x-cloak @endif class="ms-auto grid h-5 min-w-5 shrink-0 place-items-center rounded-full bg-red-500 px-1.5 text-[11px] leading-none font-bold text-white shadow-[0_0_0_3px_rgb(6_31_27)]" x-text="show(badges[@js($route)])">{{ isset($badges[$route]) ? \App\Support\Badges::text($badges[$route]) : '' }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
                <p x-show="q && !@js(collect($menu)->flatten(1)->pluck(0)->all()).some((l) => match(l))" x-cloak class="px-3 text-sm text-brand-100/50">Nothing matches “<span x-text="q"></span>”.</p>
            </nav>
            <div class="relative border-t border-white/[.08] p-3">
                <div class="flex items-center gap-3 rounded-2xl bg-white/[.05] p-2.5 ring-1 ring-white/[.06]">
                    <div class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-gradient-to-br from-accent-400 to-accent-600 text-sm font-bold text-white">{{ $initials }}</div>
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-sm font-semibold text-white">{{ $user->name }}</div>
                        <div class="truncate text-xs text-brand-100/60">{{ $roleLabel }}</div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="grid h-8 w-8 place-items-center rounded-lg text-brand-100/70 hover:bg-white/10 hover:text-white" title="Sign out" aria-label="Sign out"><x-icon name="logout" class="h-[18px] w-[18px]" /></button></form>
                </div>
            </div>
        </aside>

        <div class="min-w-0 flex-1">
            {{-- ===== Top bar ===== --}}
            <header class="sticky top-0 z-20 flex h-16 items-center justify-between gap-3 border-b border-slate-200/70 bg-white/75 px-4 backdrop-blur-xl backdrop-saturate-150 no-print lg:px-8">
                <div class="flex min-w-0 items-center gap-3">
                    <button class="relative grid h-10 w-10 place-items-center rounded-xl border border-slate-200 bg-white text-slate-700 shadow-soft lg:hidden" @click="nav = true" aria-label="Open menu"><x-icon name="menu" />
                        <span x-show="total" @if (! $badges) x-cloak @endif class="absolute -top-1 -end-1 h-3 w-3 rounded-full bg-red-500 ring-2 ring-white"></span>
                    </button>
                    <nav class="flex min-w-0 items-center gap-1.5 text-sm" aria-label="You are here">
                        @if ($here && $here['group'] && $here['group'] !== $here['label'])
                            <span class="hidden truncate text-slate-400 sm:inline">{{ $here['group'] }}</span>
                            <x-icon name="chevron-right" class="hidden h-3.5 w-3.5 shrink-0 text-slate-300 sm:block" />
                        @endif
                        <span class="truncate font-semibold text-slate-800">{{ $here['label'] ?? ($title ?? config('app.name')) }}</span>
                    </nav>
                </div>
                <div class="flex items-center gap-1.5 sm:gap-2.5">
                    <div class="hidden items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-medium text-slate-500 shadow-soft xl:flex"><x-icon name="calendar" class="h-3.5 w-3.5 text-brand-600" /> {{ now()->format('D, j M Y') }}</div>
                    <button type="button" class="hidden h-10 items-center gap-2 rounded-xl border border-slate-200 bg-white px-3 text-sm text-slate-400 shadow-soft hover:border-slate-300 md:flex" @click="nav = true; $nextTick(() => $refs.menuSearch.focus())">
                        <x-icon name="search" class="h-4 w-4" /> <span class="pe-6">Go to…</span> <kbd class="rounded-md border border-slate-200 bg-slate-50 px-1.5 text-[10px] font-semibold text-slate-500">/</kbd>
                    </button>

                    {{-- The bell: everything waiting, with a link to each --}}
                    <div class="relative" @click.outside="bell = false">
                        <button type="button" @click="bell = !bell; me = false; bell && refresh()" class="relative grid h-10 w-10 place-items-center rounded-xl border border-slate-200 bg-white text-slate-600 shadow-soft hover:border-slate-300 hover:text-slate-900" aria-label="Notifications">
                            <x-icon name="bell" class="h-5 w-5" />
                            <span x-show="total" @if (! $badges) x-cloak @endif class="absolute -top-1.5 -end-1.5 grid h-5 min-w-5 place-items-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white ring-2 ring-white" x-text="show(total)">{{ $badges ? \App\Support\Badges::text(array_sum($badges)) : '' }}</span>
                        </button>
                        <div x-show="bell" x-cloak x-transition.origin.top.right class="absolute end-0 z-40 mt-2 w-80 overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-slate-900/10">
                            <div class="flex items-center justify-between border-b border-slate-100 px-4 py-3">
                                <span class="font-display text-sm font-bold text-slate-900">Waiting for you</span>
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600" x-show="total" x-text="total + ' in all'"></span>
                            </div>
                            <div class="p-1.5">
                                <template x-for="item in list" :key="item.route">
                                    <a :href="item.url" wire:navigate @click="bell = false" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm text-slate-700 hover:bg-slate-50">
                                        <span class="grid h-8 min-w-8 place-items-center rounded-xl bg-red-50 px-1.5 text-xs font-bold text-red-600 ring-1 ring-red-100" x-text="show(item.count)"></span>
                                        <span class="flex-1 font-medium first-letter:uppercase" x-text="item.label.replace(/^\d+ /, '')"></span>
                                        <x-icon name="chevron-right" class="h-4 w-4 text-slate-300" />
                                    </a>
                                </template>
                                <div x-show="!list.length" class="flex flex-col items-center gap-2 px-4 py-7 text-center text-sm text-slate-500">
                                    <span class="grid h-11 w-11 place-items-center rounded-2xl bg-emerald-50 text-emerald-600"><x-icon name="check-circle" class="h-6 w-6" /></span>
                                    All done. Nothing is waiting.
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Me: name, password, sign out --}}
                    <div class="relative" @click.outside="me = false">
                        <button type="button" @click="me = !me; bell = false" class="flex items-center gap-2.5 rounded-xl py-1 ps-1 pe-2 hover:bg-slate-100" aria-label="My account">
                            <span class="grid h-9 w-9 place-items-center rounded-xl bg-gradient-to-br from-brand-500 to-brand-700 text-sm font-bold text-white shadow-soft">{{ $initials }}</span>
                            <span class="hidden text-start leading-tight sm:block">
                                <span class="block max-w-36 truncate text-sm font-semibold text-slate-800">{{ $user->name }}</span>
                                <span class="block text-xs text-slate-500">{{ $roleLabel }}</span>
                            </span>
                            <x-icon name="chevron-right" class="hidden h-3.5 w-3.5 rotate-90 text-slate-400 sm:block" />
                        </button>
                        <div x-show="me" x-cloak x-transition.origin.top.right class="absolute end-0 z-40 mt-2 w-60 overflow-hidden rounded-2xl bg-white p-1.5 shadow-2xl ring-1 ring-slate-900/10">
                            <div class="px-3 py-2.5">
                                <div class="truncate text-sm font-semibold text-slate-900">{{ $user->name }}</div>
                                <div class="truncate text-xs text-slate-500">{{ $user->username }}</div>
                            </div>
                            <div class="my-1 h-px bg-slate-100"></div>
                            <a href="{{ route('password.change') }}" wire:navigate class="flex items-center gap-2.5 rounded-xl px-3 py-2 text-sm text-slate-700 hover:bg-slate-50"><x-icon name="key" class="h-4 w-4 text-slate-400" /> Change password</a>
                            <form method="POST" action="{{ route('logout') }}">@csrf<button class="flex w-full items-center gap-2.5 rounded-xl px-3 py-2 text-sm text-red-600 hover:bg-red-50"><x-icon name="logout" class="h-4 w-4" /> Sign out</button></form>
                        </div>
                    </div>
                </div>
            </header>

            @if (\App\Support\Impersonation::active())
                <form method="POST" action="{{ route('impersonation.stop') }}" class="flex flex-wrap items-center justify-between gap-2 bg-gradient-to-r from-violet-700 to-indigo-700 px-4 py-2.5 text-sm text-white no-print lg:px-8">
                    @csrf
                    <span class="flex items-center gap-2"><x-icon name="shield" class="h-4 w-4 shrink-0" /> You are using <strong>{{ $user->school?->name }}</strong> as {{ $user->name }}. Everything you do is recorded under this login and in the audit log.</span>
                    <button class="btn btn-sm">Back to owner panel</button>
                </form>
            @endif
            @php($school = $user->school)
            @if ($school && $user->isRole('admin') && in_array($school->status(), ['trial', 'active', 'grace'], true) && $school->daysLeft() !== null && $school->daysLeft() <= 7)
                <div class="flex items-center gap-2 border-b border-amber-200 bg-amber-50 px-4 py-2.5 text-sm text-amber-900 no-print lg:px-8">
                    <x-icon name="alert-circle" class="h-4 w-4 shrink-0 text-amber-600" />
                    <span>
                    @if ($school->status() === 'grace')
                        Your subscription ended on {{ $school->accessUntil()->format('j M Y') }}.{{ $school->lock_when_due ? ' The system will stop working on '.$school->accessUntil()->addDays($school->grace_days)->format('j M Y').' unless it is renewed.' : '' }}
                    @else
                        Your {{ $school->status() === 'trial' ? 'free period' : 'subscription' }} ends on {{ $school->accessUntil()->format('j M Y') }}{{ $school->daysLeft() > 0 ? ', in '.$school->daysLeft().' '.str('day')->plural($school->daysLeft()) : ', today' }}.
                    @endif
                    @if ($school->monthly_fee) Monthly fee: Rs {{ number_format($school->monthly_fee) }}. @endif
                    Please contact us to renew.
                    </span>
                </div>
            @endif
            <main class="mx-auto max-w-[1440px] p-4 sm:p-6 lg:p-8">{{ $slot }}</main>
        </div>
    </div>
@endif

<div x-show="toast" x-cloak x-transition:enter="transition duration-200" x-transition:enter-start="translate-y-2 opacity-0" x-transition:leave="transition duration-150" x-transition:leave-end="opacity-0"
     class="fixed end-4 bottom-4 z-50 flex max-w-sm items-start gap-3 rounded-2xl bg-slate-900 px-4 py-3 text-sm text-white shadow-2xl ring-1 ring-white/10 no-print" role="status">
    <span class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full" :class="toastError ? 'bg-red-500' : 'bg-emerald-500'">
        <svg x-show="!toastError" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"><path d="M20 6L9 17l-5-5"/></svg>
        <svg x-show="toastError" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3.5" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
    </span>
    <span x-text="toast"></span>
</div>
</body>
</html>
