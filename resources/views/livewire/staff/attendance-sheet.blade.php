<div class="mx-auto max-w-4xl">
    <x-page-header title="Staff attendance" subtitle="Teachers, office, attendants, drivers and guards. Everyone starts present; tap only those who are late, on a half day, absent or on leave.">
        <a class="btn" href="{{ route('staff.attendance.register') }}" wire:navigate>Monthly register</a>
    </x-page-header>

    <div class="card-pad mb-4 flex flex-wrap items-end gap-3">
        <x-field label="Date" name="date"><input id="date" class="input w-auto" type="date" max="{{ now()->toDateString() }}" wire:model.live="date"></x-field>
        <div class="flex flex-wrap gap-1 rounded-lg border border-slate-300 bg-white p-0.5">
            <button @class(['rounded-md px-3 py-1.5 text-sm', 'bg-brand-600 text-white' => $category === '', 'text-slate-600' => $category !== '']) wire:click="$set('category', '')">All ({{ $total }})</button>
            @foreach ($categories as $key => $c)
                <button wire:key="cat-{{ $key }}" @class(['rounded-md px-3 py-1.5 text-sm', 'bg-brand-600 text-white' => $category === $key, 'text-slate-600' => $category !== $key]) wire:click="$set('category', '{{ $key }}')">{{ $c['label'] }} ({{ $c['count'] }})</button>
            @endforeach
        </div>
        <p class="w-full text-xs text-slate-500">Duty starts at {{ $school->staff_starts_at }} unless a person has their own hours. Arriving more than {{ $school->staff_late_minutes }} minutes after that counts as late. Change this in Settings.</p>
    </div>

    <div class="card overflow-hidden">
        <div class="flex flex-wrap items-center justify-between gap-2 border-b border-slate-200 p-3 text-sm">
            <span>
                @foreach ($statuses as $code => $label)<span class="me-3">{{ $label }} <strong>{{ $counts[$code] ?? 0 }}</strong></span>@endforeach
                @if ($saved->isNotEmpty())<span class="badge badge-blue">Saved</span>@endif
            </span>
            <button class="btn btn-sm" wire:click="allPresent">Mark all present</button>
        </div>
        <ul class="divide-y divide-slate-100">
            @forelse ($staff as $person)
                @php($row = $rows[$person->id] ?? ['status' => 'P', 'in' => '', 'out' => ''])
                <li class="flex flex-wrap items-center gap-3 px-3 py-2.5" wire:key="sa-{{ $person->id }}">
                    <div class="min-w-0 flex-1 text-sm">
                        <div class="truncate font-medium">{{ $person->name }}</div>
                        <div class="text-xs text-slate-500">{{ $person->designation ?: explode(' (', \App\Models\Staff::CATEGORIES[$person->category] ?? '')[0] }} · duty {{ $person->dutyStart($school) }}-{{ $person->dutyEnd($school) }}
                            @if (($saved[$person->id]->source ?? null) === 'self')<span class="badge badge-blue ms-1">Checked in themselves</span>@endif
                            @if (($saved[$person->id]->source ?? null) === 'device')<span class="badge badge-blue ms-1">From the machine</span>@endif
                            @if ($locked->contains($person->id))<span class="badge badge-slate ms-1" title="Strict attendance is on">Locked</span>@endif
                        </div>
                    </div>
                    <div class="flex shrink-0 gap-1" role="group" aria-label="Attendance for {{ $person->name }}">
                        @foreach (['P' => ['bg-emerald-600', 'P'], 'L' => ['bg-amber-500', 'L'], 'H' => ['bg-violet-600', '½'], 'A' => ['bg-red-600', 'A'], 'V' => ['bg-sky-600', 'Lv']] as $code => [$color, $text])
                            <button type="button" title="{{ $statuses[$code] }}" aria-label="{{ $statuses[$code] }}" aria-pressed="{{ $row['status'] === $code ? 'true' : 'false' }}"
                                    wire:click="$set('rows.{{ $person->id }}.status', '{{ $code }}')" @disabled($locked->contains($person->id))
                                    @class(['h-9 w-9 rounded-lg border text-sm font-semibold', "$color border-transparent text-white" => $row['status'] === $code, 'border-slate-200 bg-white text-slate-500' => $row['status'] !== $code])>{{ $text }}</button>
                        @endforeach
                    </div>
                    @unless (in_array($row['status'], ['A', 'V'], true))
                        <div class="flex shrink-0 items-center gap-1 text-xs text-slate-500">
                            <label class="sr-only" for="in-{{ $person->id }}">Arrived at</label>
                            <input id="in-{{ $person->id }}" class="input w-28 py-1.5!" type="time" title="Arrived at" @disabled($locked->contains($person->id)) wire:model.live.blur="rows.{{ $person->id }}.in">
                            <span aria-hidden="true">to</span>
                            <label class="sr-only" for="out-{{ $person->id }}">Left at</label>
                            <input id="out-{{ $person->id }}" class="input w-28 py-1.5!" type="time" title="Left at" @disabled($locked->contains($person->id)) wire:model.blur="rows.{{ $person->id }}.out">
                        </div>
                    @endunless
                    @error('rows.'.$person->id.'.in')<p class="err w-full">{{ $message }}</p>@enderror
                </li>
            @empty
                <li class="p-6 text-center text-sm text-slate-500">No staff in this group. Add staff and choose their group under Staff.</li>
            @endforelse
        </ul>
        <div class="sticky bottom-0 border-t border-slate-200 bg-white p-3">
            <button class="btn btn-primary w-full" wire:click="save" wire:loading.attr="disabled" wire:target="save">Save attendance for everyone</button>
        </div>
    </div>
</div>
