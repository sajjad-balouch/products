<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'ur' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700;800&family=Noto+Nastaliq+Urdu:wght@400;600&display=swap" rel="stylesheet">
    <meta name="theme-color" content="#061f1b">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
@php($split = request()->routeIs('login', 'signup'))
<body class="min-h-screen bg-surface font-sans text-slate-900 antialiased">
@if ($split)
    {{-- Sign in and sign up: the product on one side, the form on the other. --}}
    <div class="grid min-h-screen lg:grid-cols-[1.05fr_1fr]">
        <aside class="relative hidden overflow-hidden bg-brand-950 p-12 text-white lg:flex lg:flex-col">
            <div class="bg-dots pointer-events-none absolute inset-0 opacity-70"></div>
            <div class="pointer-events-none absolute -start-32 -top-32 h-[28rem] w-[28rem] rounded-full bg-brand-500/30 blur-3xl"></div>
            <div class="pointer-events-none absolute -end-24 bottom-0 h-96 w-96 rounded-full bg-accent-500/20 blur-3xl"></div>
            <div class="relative flex items-center gap-3">
                <div class="grid h-11 w-11 place-items-center rounded-2xl bg-gradient-to-br from-accent-400 to-accent-600 shadow-lg shadow-accent-600/30 ring-1 ring-white/20"><x-icon name="school" class="h-6 w-6" /></div>
                <span class="font-display text-xl font-extrabold tracking-tight">{{ config('app.name') }}</span>
            </div>
            <div class="relative my-auto max-w-lg py-12">
                <span class="inline-flex items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-brand-50 ring-1 ring-white/15"><span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span> Schools · Colleges · Shops and businesses</span>
                <h1 class="font-display mt-6 text-[44px] leading-[1.08] font-extrabold tracking-tight">Run your whole institution <span class="text-gradient">from one place.</span></h1>
                <p class="mt-4 text-lg leading-relaxed text-brand-100/80">Fees, attendance, results, installments, accounts and your own website. Works on any phone, even with weak internet.</p>
                <ul class="mt-8 space-y-3.5">
                    @foreach ([['wallet', 'Fees and installments that remind people by themselves'], ['check-square', 'Attendance and results, sent to parents on WhatsApp'], ['chart', 'Clear accounts and reports, without an accountant']] as [$icon, $line])
                        <li class="flex items-center gap-3 text-[15px] text-brand-50"><span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-white/10 ring-1 ring-white/15"><x-icon :name="$icon" class="h-[18px] w-[18px] text-accent-400" /></span>{{ $line }}</li>
                    @endforeach
                </ul>
            </div>
            <div class="relative flex items-center justify-between gap-6 rounded-2xl bg-white/[.06] p-5 ring-1 ring-white/10 backdrop-blur">
                <div>
                    <div class="flex gap-0.5 text-accent-400">@for ($i = 0; $i < 5; $i++)<svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2.5l2.9 6.1 6.6.8-4.9 4.6 1.3 6.6L12 17.3l-5.9 3.3 1.3-6.6L2.5 9.4l6.6-.8z"/></svg>@endfor</div>
                    <p class="mt-2 text-sm text-brand-50/90">“Fee collection used to take a week of calls. Now parents get the reminder and pay.”</p>
                </div>
                <div class="hidden shrink-0 text-end xl:block"><div class="font-display text-3xl font-extrabold">24/7</div><div class="text-xs text-brand-100/60">on any device</div></div>
            </div>
        </aside>
        <main class="flex min-h-screen flex-col px-5 py-8 sm:px-10">
            <div class="flex items-center justify-between">
                <a href="{{ route('home') }}" class="flex items-center gap-2.5 lg:invisible">
                    <span class="grid h-9 w-9 place-items-center rounded-xl bg-gradient-to-br from-accent-400 to-accent-600 text-white"><x-icon name="school" class="h-5 w-5" /></span>
                    <span class="font-display text-lg font-extrabold tracking-tight">{{ config('app.name') }}</span>
                </a>
                <a href="{{ route('home') }}" class="text-sm font-medium text-slate-500 hover:text-slate-800">← Back to the website</a>
            </div>
            <div class="mx-auto flex w-full max-w-[440px] flex-1 flex-col justify-center py-10">{{ $slot }}</div>
            <p class="text-center text-xs text-slate-400">© {{ now()->year }} {{ config('app.name') }}. All rights reserved.</p>
        </main>
    </div>
@else
    <div class="relative flex min-h-screen items-center justify-center overflow-hidden px-4 py-10">
        <div class="pointer-events-none absolute inset-x-0 top-0 h-72 bg-gradient-to-b from-brand-900 to-brand-800"></div>
        <div class="bg-dots pointer-events-none absolute inset-x-0 top-0 h-72 opacity-60"></div>
        <div class="relative w-full max-w-lg">{{ $slot }}</div>
    </div>
@endif
</body>
</html>
