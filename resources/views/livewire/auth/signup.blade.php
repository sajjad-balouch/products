<div>
    <div class="mb-7">
        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-600/15"><x-icon name="check-circle" class="h-3.5 w-3.5" /> Free trial · No payment details needed</span>
        <h1 class="font-display mt-4 text-[30px] leading-tight font-extrabold tracking-tight text-slate-900">Create your account</h1>
        <p class="mt-1.5 text-[15px] text-slate-500">Ready in a minute. Your data stays yours.</p>
    </div>
    @php($business = $type === 'business')
    <form wire:submit="register" class="space-y-4">
        <x-field label="What will you run?" name="type">
            <select id="type" class="input" wire:model.live="type">
                <optgroup label="School management">
                    <option value="school">School</option>
                    <option value="college">College</option>
                    <option value="both">School and college</option>
                </optgroup>
                <optgroup label="Business">
                    <option value="business">Shop or business (installments and accounts)</option>
                </optgroup>
            </select>
        </x-field>
        @if ($business)
            <fieldset>
                <legend class="label">What does your business sell?</legend>
                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach (\App\Support\BusinessKinds::KINDS as $key => [$label, $icon, $about])
                        <label @class(['flex cursor-pointer items-start gap-2.5 rounded-xl border p-2.5 transition', 'border-brand-500 bg-brand-50 ring-1 ring-brand-500' => $kind === $key, 'border-slate-200 hover:border-slate-300' => $kind !== $key])>
                            <input type="radio" class="sr-only" value="{{ $key }}" wire:model.live="kind">
                            <span @class(['icon-chip h-8! w-8! shrink-0', 'tone-brand' => $kind === $key, 'tone-slate' => $kind !== $key])><x-icon :name="$icon" class="h-4 w-4" /></span>
                            <span class="min-w-0"><span class="block text-sm font-semibold">{{ $label }}</span><span class="block text-xs leading-snug text-slate-500">{{ $about }}</span></span>
                        </label>
                    @endforeach
                </div>
                @error('kind')<p class="err">{{ $message }}</p>@enderror
                <p class="mt-1 text-xs text-slate-500">Your products, brands and dashboard are set up for this. You can change it later.</p>
            </fieldset>
        @endif
        <x-field :label="$business ? 'Business name' : 'School or college name'" name="school"><input id="school" class="input" wire:model="school"></x-field>
        <x-field label="Your name" name="name"><input id="name" class="input" wire:model="name"></x-field>
        <x-field label="Email (used to sign in)" name="email"><input id="email" class="input" type="email" wire:model="email" autocomplete="username"></x-field>
        <x-field label="Phone" name="phone"><input id="phone" class="input" wire:model="phone"></x-field>
        <x-field label="Password" name="password" hint="At least 8 characters"><input id="password" class="input" type="password" wire:model="password" autocomplete="new-password"></x-field>
        <button class="btn btn-primary btn-lg w-full" wire:loading.attr="disabled">{{ $business ? 'Create my business account' : 'Create my school' }}</button>
        <p class="text-center text-sm text-slate-500">Already registered? <a class="font-semibold text-brand-700 hover:underline" href="{{ route('login') }}">Sign in</a></p>
    </form>
</div>
