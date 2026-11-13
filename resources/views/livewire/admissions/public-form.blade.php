<div class="rounded-2xl bg-white p-6 shadow-xl">
    <h1 class="text-xl font-semibold">{{ $school->name }}</h1>
    <p class="mb-4 text-sm text-slate-500">Online admission form</p>
    @if ($done)
        <p class="rounded-lg bg-emerald-50 p-4 text-sm text-emerald-900">Thank you. Your application has been received and the school office will contact you.</p>
    @else
        <form wire:submit="submit" class="space-y-3">
            <x-field label="Student name" name="student_name"><input id="student_name" class="input" wire:model="student_name"></x-field>
            <x-field label="Father or guardian name" name="guardian_name"><input id="guardian_name" class="input" wire:model="guardian_name"></x-field>
            <x-field label="Mobile number" name="phone"><input id="phone" class="input" inputmode="tel" wire:model.blur="phone"></x-field>
            <x-field label="Admission required in" name="school_class_id">
                <select id="school_class_id" class="input" wire:model="school_class_id"><option value="">Select class</option>@foreach ($classes as $c)<option value="{{ $c->id }}">{{ $c->label }}</option>@endforeach</select>
            </x-field>
            <x-field label="Anything you want to tell us" name="notes"><textarea id="notes" class="input" rows="2" wire:model="notes"></textarea></x-field>
            <div class="hidden" aria-hidden="true"><input tabindex="-1" autocomplete="off" wire:model="website"></div>
            <button class="btn btn-primary w-full" wire:loading.attr="disabled">Submit application</button>
        </form>
    @endif
</div>
