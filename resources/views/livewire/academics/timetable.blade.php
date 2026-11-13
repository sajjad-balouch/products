@php
    $tones = ['bg-sky-50 border-sky-200', 'bg-emerald-50 border-emerald-200', 'bg-amber-50 border-amber-200', 'bg-violet-50 border-violet-200', 'bg-rose-50 border-rose-200', 'bg-teal-50 border-teal-200', 'bg-orange-50 border-orange-200', 'bg-indigo-50 border-indigo-200'];
    $tone = fn ($subjectId) => $tones[$subjectId % count($tones)];
@endphp
<div x-data="timetableBoard()" @dragend.window="end()">
    <style>@media print { @page { size: A4 landscape; margin: 10mm; } }</style>

    <div class="mb-3 hidden border-b-2 border-slate-800 pb-2 print:block">
        <div class="text-center">
            <div class="text-2xl font-bold">{{ $school->name }}</div>
            <div class="text-xs text-slate-600">{{ collect([$school->address, $school->phone, $school->email])->filter()->implode(' · ') }}</div>
        </div>
        <div class="mt-2 flex items-end justify-between gap-4">
            <div class="text-lg font-semibold">
                @if ($view === 'class' && $section)
                    Timetable for {{ $section->label }}
                @elseif ($view === 'teacher' && $selectedTeacher)
                    Timetable for {{ $selectedTeacher->name }}
                @endif
            </div>
            <div class="text-end text-xs text-slate-600">
                <div>{{ $current->name }}{{ $current->is_active ? '' : ' (Draft)' }}</div>
                <div>Printed {{ now()->format('j M Y') }}</div>
            </div>
        </div>
    </div>

    <x-page-header title="Timetable" :subtitle="$canEdit ? 'Drag a subject onto the grid, drag a period to move it, or drop it on another to swap. A teacher can take any number of periods, just not two classes at the same time.' : 'The timetable currently in use.'">
        <button class="btn" onclick="window.print()">Print</button>
    </x-page-header>

    @if ($canEdit)
        <div class="card-pad mb-4 flex flex-wrap items-center gap-2 no-print">
            <select class="input w-auto" aria-label="Timetable" wire:model.live="timetable_id">
                @foreach ($timetables as $t)<option value="{{ $t->id }}">{{ $t->name }}{{ $t->is_active ? ' (active)' : '' }}</option>@endforeach
            </select>
            @if ($current->is_active)
                <span class="badge badge-green">Live for teachers and parents</span>
            @else
                <span class="badge badge-amber">Draft</span>
                <button class="btn btn-primary" wire:click="activateTimetable" wire:confirm="Make {{ $current->name }} the live timetable for teachers and parents?">Make active</button>
            @endif
            <div class="ms-auto flex flex-wrap gap-2">
                <button class="btn" wire:click="openTimetableForm('new')">New timetable</button>
                <button class="btn" wire:click="openTimetableForm('rename')">Rename</button>
                <button class="btn" wire:click="openPeriodForm">Add period</button>
                @unless ($current->is_active)
                    <button class="btn btn-danger" wire:click="deleteTimetable" wire:confirm="Delete {{ $current->name }} and all its periods? This cannot be undone.">Delete</button>
                @endunless
            </div>
        </div>
    @endif

    <div class="mb-4 flex flex-wrap items-center gap-2 no-print">
        <div class="flex rounded-lg border border-slate-300 bg-white p-0.5">
            <button @class(['rounded-md px-3 py-1.5 text-sm', 'bg-brand-600 text-white' => $view === 'class', 'text-slate-600' => $view !== 'class']) wire:click="$set('view', 'class')">By class</button>
            <button @class(['rounded-md px-3 py-1.5 text-sm', 'bg-brand-600 text-white' => $view === 'teacher', 'text-slate-600' => $view !== 'teacher']) wire:click="$set('view', 'teacher')">{{ $isTeacher ? 'My week' : 'By teacher' }}</button>
        </div>
        @if ($view === 'class')
            <select class="input w-auto" aria-label="Class" wire:key="class-picker" wire:model.live="section_id">
                @foreach ($sections as $s)<option value="{{ $s->id }}">{{ $s->label }}</option>@endforeach
            </select>
            @if ($canEdit && $periods->isNotEmpty())
                <button class="btn" wire:click="suggestPlan" wire:loading.attr="disabled" wire:target="suggestPlan">Suggest periods</button>
            @endif
        @elseif (! $isTeacher)
            <select class="input w-auto" aria-label="Teacher" wire:key="teacher-picker" wire:model.live="teacher_id">
                <option value="">Select teacher</option>
                @foreach ($teachers as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach
            </select>
        @endif
    </div>

    @if ($periods->isEmpty())
        <div class="card-pad text-sm text-slate-500">
            This timetable has no periods yet.
            @if ($canEdit)<button class="text-brand-600 underline" wire:click="openPeriodForm">Add the first period</button>.@endif
        </div>
    @elseif ($view === 'teacher')
        <div class="card overflow-x-auto print:overflow-visible print:rounded-none print:shadow-none">
            @if ($selectedTeacher)
                <div class="border-b border-slate-200 px-4 py-3 text-sm">
                    <span class="font-semibold">{{ $selectedTeacher->name }}</span>:
                    {{ $teacherEntries->count() }} periods a week
                    @foreach ($days as $d => $label)
                        <span class="ms-2 text-slate-500">{{ $label }} {{ $loads->get($selectedTeacher->id)['days'][$d] ?? 0 }}</span>
                    @endforeach
                </div>
                <table class="w-full border-collapse text-sm">
                    @include('livewire.academics.timetable-head')
                    <tbody>
                    @foreach ($periods as $period)
                        <tr class="border-t border-slate-100" wire:key="tv-{{ $period->id }}">
                            @include('livewire.academics.timetable-period')
                            @if ($period->is_break)
                                <td colspan="{{ count($days) }}" class="bg-slate-50 text-center text-xs tracking-widest text-slate-400 uppercase">{{ $period->name }}</td>
                            @else
                                @foreach ($days as $day => $label)
                                    @php($entry = $teacherEntries->get($period->id.'-'.$day))
                                    <td class="p-1">
                                        @if ($entry)
                                            <div class="min-w-24 rounded-lg border p-1.5 text-xs {{ $tone($entry->subject_id) }}"><div class="font-medium">{{ $entry->section->label }}</div><div class="text-[11px] text-slate-600">{{ $entry->subject->name }}</div></div>
                                        @endif
                                    </td>
                                @endforeach
                            @endif
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @else
                <p class="p-6 text-sm text-slate-500">{{ $isTeacher ? 'Your login is not linked to a staff record yet.' : 'Choose a teacher to see their week.' }}</p>
            @endif
        </div>
    @else
        @if ($plan)
            <div class="mb-4 flex flex-wrap items-center gap-3 rounded-xl border border-brand-500 bg-brand-50 p-3 text-sm no-print">
                <p class="min-w-0 flex-1">
                    <span class="font-semibold">{{ count($plan['placements']) }} periods suggested</span> on the grid. Nothing is saved yet. Click one to drop it.
                    @if ($plan['unfilled'])
                        <span class="text-amber-800">{{ $plan['unfilled'] }} {{ $plan['unfilled'] === 1 ? 'box stays' : 'boxes stay' }} empty because this class's teachers are busy then. Click an empty box to see who is free.</span>
                    @endif
                </p>
                <button class="btn" wire:click="$set('plan', null)">Discard</button>
                <button class="btn btn-primary" wire:click="applyPlan" wire:loading.attr="disabled" @if ($current->is_active) wire:confirm="{{ $current->name }} is live, so teachers and parents will see these periods straight away. Apply them?" @endif>Apply all</button>
            </div>
        @endif

        <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_16rem] print:block">
            <div class="card overflow-x-auto print:overflow-visible print:rounded-none print:shadow-none">
                <table class="w-full border-collapse text-sm">
                    @include('livewire.academics.timetable-head')
                    <tbody>
                    @foreach ($periods as $period)
                        <tr class="border-t border-slate-100" wire:key="tp-{{ $period->id }}">
                            @include('livewire.academics.timetable-period')
                            @if ($period->is_break)
                                <td colspan="{{ count($days) }}" class="bg-slate-50 text-center text-xs tracking-widest text-slate-400 uppercase">{{ $period->name }}</td>
                            @else
                                @foreach ($days as $day => $label)
                                    @php($entry = $entries->get($period->id.'-'.$day))
                                    <td class="p-1" wire:key="c-{{ $period->id }}-{{ $day }}">
                                        @if ($canEdit)
                                            <div class="relative h-16 min-w-24 rounded-lg transition-colors"
                                                 :class="cellClass('{{ $period->id }}-{{ $day }}')"
                                                 data-slot="{{ $period->id }}-{{ $day }}"
                                                 @dragover.prevent="over = '{{ $period->id }}-{{ $day }}'"
                                                 @dragleave="if (over === '{{ $period->id }}-{{ $day }}') over = null"
                                                 @drop.prevent="drop({{ $period->id }}, {{ $day }})">
                                                @if ($entry)
                                                    <button draggable="true" wire:key="e-{{ $entry->id }}" data-entry="{{ $entry->id }}"
                                                            @dragstart="startMove($event, {{ $entry->id }}, {{ $entry->staff_id ?? 'null' }})"
                                                            wire:click="pick({{ $period->id }}, {{ $day }})"
                                                            aria-label="{{ $label }} {{ $period->name }}: {{ $entry->subject->name }}{{ $entry->staff ? ', '.$entry->staff->name : '' }}"
                                                            class="h-full w-full cursor-grab rounded-lg border p-1 text-xs active:cursor-grabbing {{ $tone($entry->subject_id) }}">
                                                        <div class="font-medium">{{ $entry->subject->name }}</div>
                                                        <div class="text-[11px] text-slate-600">{{ $entry->staff?->name ?? 'No teacher' }}</div>
                                                    </button>
                                                @elseif ($suggested = $plan['placements'][$period->id.'-'.$day] ?? null)
                                                    <button wire:click="discardSuggestion('{{ $period->id }}-{{ $day }}')" aria-label="Drop suggestion for {{ $label }} {{ $period->name }}: {{ $suggested['subject'] }}"
                                                            title="Click to drop this suggestion"
                                                            class="h-full w-full rounded-lg border-2 border-dashed p-1 text-xs print:invisible {{ $tone($suggested['subject_id']) }}">
                                                        <div class="font-medium">{{ $suggested['subject'] }}</div>
                                                        <div class="text-[11px] text-slate-600">{{ $suggested['staff'] ?? 'No teacher' }}</div>
                                                    </button>
                                                @else
                                                    <button wire:click="pick({{ $period->id }}, {{ $day }})" aria-label="{{ $label }} {{ $period->name }}: empty"
                                                            class="h-full w-full rounded-lg border border-dashed border-slate-200 text-slate-300 hover:border-brand-500 print:invisible">+</button>
                                                @endif
                                                <div x-show="busyIn('{{ $period->id }}-{{ $day }}')" x-cloak class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center rounded-lg bg-red-100 p-1 text-center text-[11px] text-red-800">
                                                    <span>Teaching</span>
                                                    <span class="font-medium" x-text="busyIn('{{ $period->id }}-{{ $day }}')"></span>
                                                </div>
                                            </div>
                                        @elseif ($entry)
                                            <div class="min-w-24 rounded-lg border p-1.5 text-center text-xs {{ $tone($entry->subject_id) }}"><div class="font-medium">{{ $entry->subject->name }}</div><div class="text-[11px] text-slate-600">{{ $entry->staff?->name }}</div></div>
                                        @endif
                                    </td>
                                @endforeach
                            @endif
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            @if ($canEdit)
                <aside class="space-y-4 no-print">
                    <div class="card-pad">
                        <h2 class="text-sm font-semibold">Drag from here</h2>
                        <div class="mt-2 space-y-1.5">
                            @forelse ($palette as $tile)
                                <div draggable="true" data-tile="{{ $tile['subject_id'] }}"
                                     @dragstart="startNew($event, {{ $tile['subject_id'] }}, {{ $tile['staff_id'] ?? 'null' }})"
                                     class="cursor-grab rounded-lg border p-2 text-xs active:cursor-grabbing {{ $tone($tile['subject_id']) }}">
                                    <div class="flex justify-between gap-2"><span class="font-medium">{{ $tile['subject'] }}</span><span class="text-slate-500">{{ $tile['placed'] }}/week</span></div>
                                    <div class="flex justify-between gap-2 text-[11px] text-slate-600">
                                        <span>{{ $tile['staff'] ?? 'No teacher assigned' }}</span>
                                        @if ($tile['staff_id'])<span @class(['shrink-0', 'font-medium text-red-700' => $tile['free'] === 0])>{{ $tile['free'] }} of {{ $slotCount }} free</span>@endif
                                    </div>
                                </div>
                            @empty
                                <p class="text-xs text-slate-500">This class has no subjects assigned. Use the box below, or add them under Subject teachers.</p>
                            @endforelse
                        </div>

                        <div class="mt-3 border-t border-slate-100 pt-3" x-data="{ customSubject: '', customStaff: '' }">
                            <p class="mb-1 text-xs font-medium text-slate-600">Any subject and teacher</p>
                            <select class="input mb-1 py-1! text-xs!" aria-label="Subject" x-model="customSubject">
                                <option value="">Subject</option>
                                @foreach ($subjects as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                            </select>
                            <select class="input mb-1 py-1! text-xs!" aria-label="Teacher" x-model="customStaff">
                                <option value="">No teacher</option>
                                @foreach ($teachers as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach
                            </select>
                            <div :draggable="customSubject !== ''" data-custom-tile
                                 @dragstart="startNew($event, Number(customSubject), customStaff ? Number(customStaff) : null)"
                                 :class="customSubject ? 'cursor-grab border-brand-500 bg-brand-50 text-brand-900' : 'border-dashed border-slate-200 text-slate-400'"
                                 class="rounded-lg border p-2 text-center text-xs" x-text="customSubject ? 'Drag me to the grid' : 'Choose a subject first'"></div>
                        </div>
                    </div>

                    <div class="rounded-xl border-2 border-dashed p-3 text-center text-xs transition-colors"
                         :class="over === 'trash' ? 'border-red-500 bg-red-50 text-red-700' : 'border-slate-300 text-slate-500'"
                         data-trash
                         @dragover.prevent="over = 'trash'" @dragleave="over = null" @drop.prevent="dropTrash()">
                        Drop here to remove
                    </div>

                    <div class="card-pad space-y-2">
                        <h2 class="text-sm font-semibold">Copy a day</h2>
                        <div class="flex items-center gap-1.5 text-xs">
                            <select class="input py-1! text-xs!" aria-label="Copy from" wire:model="copyDayFrom">@foreach ($days as $d => $l)<option value="{{ $d }}">{{ $l }}</option>@endforeach</select>
                            <span>to</span>
                            <select class="input py-1! text-xs!" aria-label="Copy to" wire:model="copyDayTo"><option value="all">All days</option>@foreach ($days as $d => $l)<option value="{{ $d }}">{{ $l }}</option>@endforeach</select>
                        </div>
                        <button class="btn btn-sm w-full" wire:click="copyDay" wire:confirm="This replaces this class's periods on the chosen days. Continue?">Copy</button>
                        <button class="btn btn-sm btn-danger w-full" wire:click="clearClass" wire:confirm="Empty this class on this timetable?">Empty this class</button>
                    </div>

                    <div class="card-pad">
                        <h2 class="text-sm font-semibold">Teacher load</h2>
                        <ul class="mt-2 space-y-1 text-xs">
                            @foreach ($teachers->sortByDesc(fn ($t) => $loads->get($t->id)['total'] ?? 0) as $t)
                                <li class="flex justify-between gap-2" wire:key="load-{{ $t->id }}">
                                    <button class="truncate text-start text-brand-600 hover:underline" wire:click="$set('teacher_id', '{{ $t->id }}'); $set('view', 'teacher')">{{ $t->name }}</button>
                                    <span class="shrink-0 text-slate-500">{{ $loads->get($t->id)['total'] ?? 0 }} periods</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </aside>
            @endif
        </div>
    @endif

    @if ($picked)
        <x-modal :title="$days[$picked['day']].' period'" close="$set('picked', null)">
            <div class="mb-4">
                <h3 class="text-sm font-semibold">Suggested for this period</h3>
                @if ($slotOptions->isNotEmpty())
                    <div class="mt-2 space-y-1.5">
                        @foreach ($slotOptions as $option)
                            <button type="button" wire:key="opt-{{ $option['subject_id'] }}-{{ $option['staff_id'] }}"
                                    wire:click="applySuggestion({{ $option['subject_id'] }}, {{ $option['staff_id'] ?? 'null' }})"
                                    class="w-full rounded-lg border p-2 text-start text-xs hover:border-brand-500 {{ $tone($option['subject_id']) }}">
                                <div class="font-medium">{{ $option['subject'] }} <span class="font-normal text-slate-600">{{ $option['staff'] ?? 'No teacher' }}</span></div>
                                <div class="text-[11px] text-slate-500">{{ $option['reason'] }}</div>
                            </button>
                        @endforeach
                    </div>
                @else
                    <p class="mt-1 text-xs text-slate-500">
                        {{ $slotFree->isEmpty() ? 'No teacher is free in this period.' : 'Free in this period: '.$slotFree->implode(', ').'. Choose a subject below.' }}
                    </p>
                @endif
            </div>

            <h3 class="mb-2 border-t border-slate-100 pt-3 text-sm font-semibold">Or choose yourself</h3>
            <form wire:submit="saveSlot" class="space-y-3">
                <x-field label="Subject" name="subject_id">
                    <select id="subject_id" class="input" wire:model="subject_id"><option value="">Select</option>@foreach ($subjects as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach</select>
                </x-field>
                <x-field label="Teacher" name="staff_id">
                    <select id="staff_id" class="input" wire:model="staff_id">
                        <option value="">Not set</option>
                        @foreach ($teachers->sortBy(fn ($t) => isset($slotBusy[$t->id])) as $t)
                            <option value="{{ $t->id }}" @disabled(isset($slotBusy[$t->id]))>{{ $t->name }}{{ isset($slotBusy[$t->id]) ? ' (teaching '.$slotBusy[$t->id].')' : '' }}</option>
                        @endforeach
                    </select>
                </x-field>
                <div class="flex justify-between gap-2">
                    <button type="button" class="btn btn-danger" wire:click="clearSlot">Clear</button>
                    <button class="btn btn-primary">Save</button>
                </div>
            </form>
        </x-modal>
    @endif

    @if ($timetableForm)
        <x-modal :title="$timetableForm === 'new' ? 'New timetable' : 'Rename timetable'" close="$set('timetableForm', null)">
            <form wire:submit="saveTimetable" class="space-y-3">
                <x-field label="Name" name="timetableName" hint="For example Winter timetable or Ramzan timetable">
                    <input id="timetableName" class="input" wire:model="timetableName">
                </x-field>
                @if ($timetableForm === 'new')
                    <x-field label="Start from" name="copyFrom">
                        <select id="copyFrom" class="input" wire:model="copyFrom">
                            <option value="">Blank timetable</option>
                            @foreach ($timetables as $t)<option value="{{ $t->id }}">Copy of {{ $t->name }}</option>@endforeach
                        </select>
                    </x-field>
                    <p class="text-xs text-slate-500">It stays a draft until you make it active, so nobody sees your changes while you work.</p>
                @endif
                <div class="flex justify-end gap-2">
                    <button type="button" class="btn" wire:click="$set('timetableForm', null)">Cancel</button>
                    <button class="btn btn-primary" wire:loading.attr="disabled">Save</button>
                </div>
            </form>
        </x-modal>
    @endif

    @if ($showPeriodForm)
        <x-modal :title="$editingPeriodId ? 'Edit period' : 'Add period'" close="$set('showPeriodForm', false)">
            <form wire:submit="savePeriod" class="space-y-3">
                <x-field label="Name" name="periodName" hint="For example Period 1 or Break"><input id="periodName" class="input" wire:model="periodName"></x-field>
                <div class="grid grid-cols-2 gap-3">
                    <x-field label="Starts at" name="periodStart"><input id="periodStart" class="input" type="time" wire:model="periodStart"></x-field>
                    <x-field label="Ends at" name="periodEnd"><input id="periodEnd" class="input" type="time" wire:model="periodEnd"></x-field>
                </div>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="periodIsBreak"> This is a break, with no classes</label>
                <div class="flex justify-between gap-2">
                    @if ($editingPeriodId)
                        <button type="button" class="btn btn-danger" wire:click="deletePeriod({{ $editingPeriodId }})" wire:confirm="Delete this period and every class's entry in it?">Delete</button>
                    @else
                        <span></span>
                    @endif
                    <button class="btn btn-primary" wire:loading.attr="disabled">Save</button>
                </div>
            </form>
        </x-modal>
    @endif
</div>
