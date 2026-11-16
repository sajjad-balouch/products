<form wire:submit="save" class="space-y-5 rounded-3xl bg-white p-7 shadow-lift ring-1 ring-slate-200/70">
    <div>
        <div class="mb-4 grid h-12 w-12 place-items-center rounded-2xl bg-brand-50 text-brand-600 ring-1 ring-brand-600/10"><x-icon name="key" class="h-6 w-6" /></div>
        <h1 class="font-display text-2xl font-extrabold tracking-tight">{{ __('Choose a new password') }}</h1>
        <p class="text-sm text-slate-500">{{ __('Please replace the password the school gave you.') }}</p>
    </div>
    <x-field :label="__('Current password')" name="current"><input id="current" class="input" type="password" wire:model="current" autocomplete="current-password"></x-field>
    <x-field :label="__('New password')" name="password"><input id="password" class="input" type="password" wire:model="password" autocomplete="new-password"></x-field>
    <x-field :label="__('Confirm new password')" name="password_confirmation"><input id="password_confirmation" class="input" type="password" wire:model="password_confirmation" autocomplete="new-password"></x-field>
    <button class="btn btn-primary btn-lg w-full">{{ __('Save password') }}</button>
</form>
