<form wire:submit="save" class="space-y-4 rounded-2xl bg-white p-6 shadow-xl">
    <div>
        <h1 class="text-lg font-semibold">{{ __('Choose a new password') }}</h1>
        <p class="text-sm text-slate-500">{{ __('Please replace the password the school gave you.') }}</p>
    </div>
    <x-field :label="__('Current password')" name="current"><input id="current" class="input" type="password" wire:model="current" autocomplete="current-password"></x-field>
    <x-field :label="__('New password')" name="password"><input id="password" class="input" type="password" wire:model="password" autocomplete="new-password"></x-field>
    <x-field :label="__('Confirm new password')" name="password_confirmation"><input id="password_confirmation" class="input" type="password" wire:model="password_confirmation" autocomplete="new-password"></x-field>
    <button class="btn btn-primary w-full">{{ __('Save password') }}</button>
</form>
