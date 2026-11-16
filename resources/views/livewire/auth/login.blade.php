<div x-data="{ show: false }">
    <div class="mb-8">
        <h1 class="font-display text-[30px] leading-tight font-extrabold tracking-tight text-slate-900">Welcome back</h1>
        <p class="mt-1.5 text-[15px] text-slate-500">Sign in to your school, college or business.</p>
    </div>
    <form wire:submit="login" class="space-y-5">
        <x-field label="Email or phone number" name="username">
            <div class="relative">
                <x-icon name="users" class="pointer-events-none absolute start-3.5 top-1/2 h-[18px] w-[18px] -translate-y-1/2 text-slate-400" />
                <input id="username" class="input py-3 ps-11 text-[15px]" wire:model="username" autocomplete="username" autofocus placeholder="you@example.com or 0300 1234567">
            </div>
        </x-field>
        <x-field label="Password" name="password">
            <div class="relative">
                <x-icon name="key" class="pointer-events-none absolute start-3.5 top-1/2 h-[18px] w-[18px] -translate-y-1/2 text-slate-400" />
                <input id="password" class="input py-3 ps-11 pe-16 text-[15px]" :type="show ? 'text' : 'password'" wire:model="password" autocomplete="current-password" placeholder="••••••••">
                <button type="button" @click="show = !show" class="absolute inset-y-0 end-0 px-4 text-xs font-semibold text-brand-700 hover:text-brand-600" x-text="show ? 'Hide' : 'Show'"></button>
            </div>
        </x-field>
        <label class="flex items-center gap-2.5 text-sm text-slate-600"><input type="checkbox" wire:model="remember"> Keep me signed in on this device</label>
        <button class="btn btn-primary btn-lg w-full" wire:loading.attr="disabled">
            <svg wire:loading wire:target="login" class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".3" stroke-width="3"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
            Sign in
        </button>
    </form>
    <div class="mt-8 rounded-2xl border border-slate-200 bg-white p-4 text-center text-sm text-slate-600 shadow-soft">
        New school or business? <a class="font-semibold text-brand-700 hover:underline" href="{{ route('signup') }}">Start a free trial →</a>
    </div>
</div>
