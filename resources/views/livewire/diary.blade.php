<div class="mx-auto max-w-3xl">
    <x-page-header title="Daily diary" subtitle="Homework and class notes. Parents read these in the portal." />
    <form wire:submit="save" class="card-pad space-y-3">
        <div class="grid gap-3 sm:grid-cols-3">
            <x-field label="Class" name="section_id">
                <select id="section_id" class="input" wire:model="section_id"><option value="">Select</option>@foreach ($sections as $s)<option value="{{ $s->id }}">{{ $s->label }}</option>@endforeach</select>
            </x-field>
            <x-field label="Subject" name="subject_id">
                <select id="subject_id" class="input" wire:model="subject_id"><option value="">General</option>@foreach ($subjects as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select>
            </x-field>
            <x-field label="Date" name="date"><input id="date" class="input" type="date" wire:model="date"></x-field>
        </div>
        <x-field label="Homework or note" name="body"><textarea id="body" class="input" rows="3" wire:model="body"></textarea></x-field>
        <button class="btn btn-primary">Post to parents</button>
    </form>
    <div class="mt-4 space-y-2">
        @foreach ($entries as $entry)
            <div class="card-pad text-sm" wire:key="d-{{ $entry->id }}">
                <div class="flex items-start justify-between gap-2">
                    <div class="text-xs text-slate-500">{{ $entry->date->format('j M') }} · {{ $entry->section->label }} · {{ $entry->subject?->name ?? 'General' }}</div>
                    <button class="text-xs text-red-700 underline" wire:click="remove({{ $entry->id }})" wire:confirm="Remove this entry?">Remove</button>
                </div>
                <p class="mt-1 whitespace-pre-line">{{ $entry->body }}</p>
            </div>
        @endforeach
    </div>
</div>
