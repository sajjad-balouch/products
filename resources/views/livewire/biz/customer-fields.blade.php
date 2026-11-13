{{-- Customer details, bound to form.* on the screen that includes it. --}}
<div class="grid gap-3 sm:grid-cols-2">
    <x-field label="Full name" name="form.name"><input id="form.name" class="input" wire:model="form.name"></x-field>
    <x-field label="Father's / husband's name" name="form.father_name"><input id="form.father_name" class="input" wire:model="form.father_name"></x-field>
    <x-field label="Mobile" name="form.phone"><input id="form.phone" class="input" type="tel" wire:model="form.phone" placeholder="0300-1234567"></x-field>
    <x-field label="Second phone" name="form.phone2"><input id="form.phone2" class="input" type="tel" wire:model="form.phone2"></x-field>
    <x-field label="CNIC" name="form.cnic"><input id="form.cnic" class="input" wire:model="form.cnic" placeholder="36302-1234567-1" inputmode="numeric"></x-field>
    <x-field label="Occupation" name="form.occupation"><input id="form.occupation" class="input" wire:model="form.occupation"></x-field>
    <x-field label="Home address" name="form.address" class="sm:col-span-2"><input id="form.address" class="input" wire:model="form.address"></x-field>
    <x-field label="Notes" name="form.notes" class="sm:col-span-2"><textarea id="form.notes" class="input" rows="2" wire:model="form.notes"></textarea></x-field>
</div>
