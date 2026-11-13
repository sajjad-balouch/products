<div class="mx-auto max-w-3xl">
    <x-page-header title="Mark attendance" subtitle="Everyone starts as present. Tap only the students who are absent, late or on leave." />
    <div class="card-pad mb-4 grid gap-3 sm:grid-cols-2">
        <x-field label="Class" name="section_id">
            <select id="section_id" class="input" wire:model.live="section_id">
                @forelse ($sections as $s)<option value="{{ $s->id }}">{{ $s->label }}</option>@empty<option value="">No class is assigned to you</option>@endforelse
            </select>
        </x-field>
        <x-field label="Date" name="date"><input id="date" class="input" type="date" max="{{ now()->toDateString() }}" wire:model.live="date"></x-field>
    </div>

    @if ($students->isNotEmpty())
        <div class="card overflow-hidden">
            <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 p-3 text-sm">
                <span>Present {{ $counts['P'] ?? 0 }} · Absent {{ $counts['A'] ?? 0 }} · Late {{ $counts['L'] ?? 0 }} · Leave {{ $counts['V'] ?? 0 }}
                    @if ($alreadyMarked)<span class="badge badge-blue ms-1">Already saved</span>@endif
                </span>
            </div>
            <ul class="divide-y divide-slate-100">
                @foreach ($students as $student)
                    <li class="flex items-center justify-between gap-3 px-3 py-2" wire:key="st-{{ $student->id }}">
                        <div class="min-w-0 text-sm"><div class="truncate font-medium">{{ $student->name }}</div><div class="text-xs text-slate-500">{{ $student->roll_no ? 'Roll '.$student->roll_no : $student->admission_no }}</div></div>
                        <div class="flex shrink-0 gap-1" role="group" aria-label="Attendance for {{ $student->name }}">
                            @foreach (['P' => ['Present', 'bg-emerald-600'], 'A' => ['Absent', 'bg-red-600'], 'L' => ['Late', 'bg-amber-500'], 'V' => ['Leave', 'bg-sky-600']] as $code => [$label, $color])
                                <button type="button" title="{{ $label }}" aria-label="{{ $label }}" aria-pressed="{{ ($statuses[$student->id] ?? '') === $code ? 'true' : 'false' }}"
                                        wire:click="$set('statuses.{{ $student->id }}', '{{ $code }}')"
                                        @class(['h-9 w-9 rounded-lg border text-sm font-semibold', "$color border-transparent text-white" => ($statuses[$student->id] ?? '') === $code, 'border-slate-200 bg-white text-slate-500' => ($statuses[$student->id] ?? '') !== $code])>{{ $code === 'V' ? 'Lv' : $code }}</button>
                            @endforeach
                        </div>
                    </li>
                @endforeach
            </ul>
            <div class="sticky bottom-0 border-t border-slate-200 bg-white p-3">
                <button class="btn btn-primary w-full" wire:click="save" wire:loading.attr="disabled" wire:target="save">Save attendance</button>
            </div>
        </div>
    @else
        <div class="card-pad text-sm text-slate-500">No students in this class.</div>
    @endif
</div>
