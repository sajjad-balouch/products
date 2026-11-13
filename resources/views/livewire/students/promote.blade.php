<div class="mx-auto max-w-3xl">
    <x-page-header title="Promote students" subtitle="Move a whole class up. Tick anyone who is staying back. Fee and result history stays with each student." />
    <div class="card-pad grid gap-3 sm:grid-cols-2">
        <x-field label="From" name="from">
            <select id="from" class="input" wire:model.live="from">
                <option value="">Select</option>
                @foreach ($sections as $s)<option value="{{ $s->id }}">{{ $s->label }}</option>@endforeach
            </select>
        </x-field>
        <x-field label="To" name="to">
            <select id="to" class="input" wire:model="to">
                <option value="">Select</option>
                <option value="graduate">Graduate (leaves the school)</option>
                @foreach ($sections as $s)<option value="{{ $s->id }}">{{ $s->label }}</option>@endforeach
            </select>
        </x-field>
    </div>
    @if ($students->isNotEmpty())
        <div class="card mt-4 overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-200 p-3">
                <span class="text-sm font-medium">{{ $students->count() }} students. Tick those staying back.</span>
                <button class="btn btn-primary" wire:click="promote" wire:confirm="Move these students now?">Move students</button>
            </div>
            <ul class="divide-y divide-slate-100">
                @foreach ($students as $student)
                    <li class="flex items-center gap-3 px-4 py-2 text-sm" wire:key="p-{{ $student->id }}">
                        <input id="h{{ $student->id }}" type="checkbox" value="{{ $student->id }}" wire:model="held">
                        <label for="h{{ $student->id }}">{{ $student->name }} <span class="text-slate-400">{{ $student->admission_no }}</span></label>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
