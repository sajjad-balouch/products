<div>
    <div class="mb-6 text-center text-white">
        <div class="text-3xl font-bold tracking-tight">{{ config('app.name') }}</div>
        <p class="mt-1 text-sm text-brand-100">School and college management</p>
    </div>
    <form wire:submit="login" class="space-y-4 rounded-2xl bg-white p-6 shadow-xl">
        <x-field label="Email or phone number" name="username">
            <input id="username" class="input" wire:model="username" autocomplete="username" autofocus>
        </x-field>
        <x-field label="Password" name="password">
            <input id="password" class="input" type="password" wire:model="password" autocomplete="current-password">
        </x-field>
        <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" wire:model="remember"> Keep me signed in</label>
        <button class="btn btn-primary w-full" wire:loading.attr="disabled">Sign in</button>
        <p class="text-center text-sm text-slate-500">New school? <a class="font-medium text-brand-600 underline" href="{{ route('signup') }}">Start a free trial</a></p>
        <p class="text-center text-sm"><a class="text-slate-500 hover:underline" href="{{ route('home') }}">Back to the website</a></p>
    </form>
</div>
