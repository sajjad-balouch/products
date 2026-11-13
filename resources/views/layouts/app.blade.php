<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ur' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }} · {{ auth()->user()?->school?->name ?? config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Nastaliq+Urdu:wght@400;600&display=swap" rel="stylesheet">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#0a2f2a">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-100 font-sans text-slate-900 antialiased" x-data="{ nav: false, toast: @js(session('toast', '')), toastError: false }" x-init="toast && setTimeout(() => toast = '', 6000)"
      @toast.window="toast = $event.detail.message; toastError = !!$event.detail.error; setTimeout(() => toast = '', toastError ? 8000 : 4000)">
@php($user = auth()->user())

@if ($user->isRole('parent', 'student'))
    <header class="sticky top-0 z-30 bg-brand-900 text-white">
        <div class="mx-auto flex max-w-3xl items-center justify-between gap-3 px-4 py-3">
            <div class="min-w-0">
                <div class="truncate text-sm font-semibold">{{ $user->school->name }}</div>
                <div class="truncate text-xs text-brand-100">{{ $user->name }}</div>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('locale', app()->getLocale() === 'ur' ? 'en' : 'ur') }}" class="rounded-md bg-white/10 px-2 py-1 text-xs">{{ app()->getLocale() === 'ur' ? 'English' : 'اردو' }}</a>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="rounded-md bg-white/10 px-2 py-1 text-xs whitespace-nowrap">{{ __('Sign out') }}</button></form>
            </div>
        </div>
    </header>
    <main class="mx-auto max-w-3xl px-4 py-4">{{ $slot }}</main>
@else
    <div class="lg:flex">
        <div x-show="nav" x-cloak class="fixed inset-0 z-30 bg-black/40 lg:hidden" @click="nav = false"></div>
        @php($roleLabel = ['super_admin' => 'Software owner', 'admin' => $user->school?->isBusiness() ? 'Owner' : 'Principal / Admin', 'accountant' => 'Accountant', 'teacher' => 'Teacher', 'website_editor' => 'Website editor'][$user->role] ?? ucfirst($user->role))
        @php($initials = collect(preg_split('/\s+/', trim($user->name)))->filter()->take(2)->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->implode(''))
        <aside class="fixed inset-y-0 start-0 z-40 flex w-64 -translate-x-full flex-col bg-brand-900 transition-transform lg:sticky lg:top-0 lg:h-screen lg:translate-x-0 no-print"
               :class="nav && 'translate-x-0!'">
            <div class="flex items-center gap-3 px-4 py-4">
                <div class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-white/10 text-white ring-1 ring-white/15"><x-icon name="school" class="h-6 w-6" /></div>
                <div class="min-w-0">
                    <div class="truncate text-base font-bold tracking-tight text-white">{{ config('app.name') }}</div>
                    <div class="truncate text-xs text-brand-100/80">{{ $user->school?->name ?? 'Owner panel' }}</div>
                </div>
                <button class="ms-auto rounded-lg p-1.5 text-brand-100 hover:bg-white/10 lg:hidden" @click="nav = false" aria-label="Close menu"><x-icon name="x-circle" /></button>
            </div>
            <nav class="flex-1 space-y-5 overflow-y-auto px-3 pb-4" aria-label="Main">
                @foreach (\App\Support\Navigation::for($user) as $group => $links)
                    <div>
                        @if ($group)<div class="mb-1.5 px-3 text-[11px] font-semibold tracking-wider text-brand-100/50 uppercase">{{ $group }}</div>@endif
                        <div class="space-y-0.5">
                            @foreach ($links as [$label, $route])
                                <a href="{{ route($route) }}" wire:navigate @class(['nav-link', 'nav-active' => request()->routeIs($route)]) @if (request()->routeIs($route)) aria-current="page" @endif>
                                    <x-icon :name="\App\Support\Navigation::icon($route)" class="h-[18px] w-[18px] shrink-0 opacity-90" />
                                    <span class="truncate">{{ $label }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </nav>
            <div class="border-t border-white/10 p-3">
                <div class="flex items-center gap-3 rounded-xl bg-white/5 p-2.5">
                    <div class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-accent-500 text-sm font-bold text-white">{{ $initials }}</div>
                    <div class="min-w-0 flex-1">
                        <div class="truncate text-sm font-medium text-white">{{ $user->name }}</div>
                        <div class="truncate text-xs text-brand-100/70">{{ $roleLabel }}</div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="rounded-lg p-2 text-brand-100 hover:bg-white/10 hover:text-white" title="Sign out" aria-label="Sign out"><x-icon name="logout" class="h-[18px] w-[18px]" /></button></form>
                </div>
            </div>
        </aside>

        <div class="min-w-0 flex-1">
            <header class="sticky top-0 z-20 flex items-center justify-between gap-3 border-b border-slate-200/80 bg-white/90 px-4 py-2.5 backdrop-blur no-print lg:px-6">
                <button class="flex items-center gap-2 rounded-lg border border-slate-200 px-2.5 py-1.5 text-sm font-medium text-slate-700 lg:hidden" @click="nav = true" aria-label="Open menu"><x-icon name="menu" /> Menu</button>
                <div class="hidden items-center gap-2 text-sm text-slate-500 lg:flex"><x-icon name="calendar" class="h-4 w-4" /> {{ now()->format('l, j F Y') }}</div>
                <div class="flex items-center gap-3">
                    <div class="hidden text-end sm:block">
                        <div class="text-sm font-medium text-slate-800">{{ $user->name }}</div>
                        <div class="text-xs text-slate-500">{{ $roleLabel }}</div>
                    </div>
                    <div class="grid h-9 w-9 place-items-center rounded-full bg-brand-600 text-sm font-bold text-white">{{ $initials }}</div>
                    <form method="POST" action="{{ route('logout') }}" class="lg:hidden">@csrf<button class="rounded-lg border border-slate-200 p-2 text-slate-600" aria-label="Sign out"><x-icon name="logout" class="h-4 w-4" /></button></form>
                </div>
            </header>
            @if (\App\Support\Impersonation::active())
                <form method="POST" action="{{ route('impersonation.stop') }}" class="flex flex-wrap items-center justify-between gap-2 bg-violet-700 px-4 py-2 text-sm text-white no-print">
                    @csrf
                    <span>You are using <strong>{{ $user->school?->name }}</strong> as {{ $user->name }}. Everything you do is recorded under this login and in the audit log.</span>
                    <button class="btn btn-sm">Back to owner panel</button>
                </form>
            @endif
            @php($school = $user->school)
            @if ($school && $user->isRole('admin') && in_array($school->status(), ['trial', 'active', 'grace'], true) && $school->daysLeft() !== null && $school->daysLeft() <= 7)
                <div class="bg-amber-100 px-4 py-2 text-sm text-amber-900 no-print">
                    @if ($school->status() === 'grace')
                        Your subscription ended on {{ $school->accessUntil()->format('j M Y') }}.{{ $school->lock_when_due ? ' The system will stop working on '.$school->accessUntil()->addDays($school->grace_days)->format('j M Y').' unless it is renewed.' : '' }}
                    @else
                        Your {{ $school->status() === 'trial' ? 'free period' : 'subscription' }} ends on {{ $school->accessUntil()->format('j M Y') }}{{ $school->daysLeft() > 0 ? ', in '.$school->daysLeft().' '.str('day')->plural($school->daysLeft()) : ', today' }}.
                    @endif
                    @if ($school->monthly_fee) Monthly fee: Rs {{ number_format($school->monthly_fee) }}. @endif
                    Please contact us to renew.
                </div>
            @endif
            <main class="p-4 lg:p-6">{{ $slot }}</main>
        </div>
    </div>
@endif

<div x-show="toast" x-cloak x-transition class="fixed end-4 bottom-4 z-50 max-w-sm rounded-lg px-4 py-3 text-sm text-white shadow-lg no-print"
     :class="toastError ? 'bg-red-700' : 'bg-brand-700'" x-text="toast" role="status"></div>
</body>
</html>
