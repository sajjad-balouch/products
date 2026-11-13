<?php

namespace App\Livewire\Academics;

use App\Models\ClassSubject;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Subject;
use App\Models\Timetable as TimetableModel;
use App\Models\TimetableEntry;
use App\Models\TimetablePeriod;
use App\Services\TimetableService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('Timetable')]
class Timetable extends Component
{
    #[Url(as: 't')]
    public string $timetable_id = '';

    #[Url(as: 'class')]
    public string $section_id = '';

    #[Url]
    public string $view = 'class';

    #[Url(as: 'teacher')]
    public string $teacher_id = '';

    /** Read by the drag code to mark slots where the dragged teacher is busy. */
    public array $busyMap = [];

    /** Suggested periods waiting to be applied: placements by slot, and how many slots had no option. */
    public ?array $plan = null;

    // Slot editor
    public ?array $picked = null;

    public string $subject_id = '';

    public string $staff_id = '';

    // New or rename form: null, 'new' or 'rename'
    public ?string $timetableForm = null;

    public string $timetableName = '';

    public string $copyFrom = '';

    // Period form
    public bool $showPeriodForm = false;

    public ?int $editingPeriodId = null;

    public string $periodName = '';

    public string $periodStart = '';

    public string $periodEnd = '';

    public bool $periodIsBreak = false;

    // Copy a day
    public string $copyDayFrom = '1';

    public string $copyDayTo = 'all';

    public function mount(): void
    {
        $this->section_id = $this->section_id ?: (string) ($this->sections()->first()?->id ?? '');

        if (Auth::user()->isRole('teacher')) {
            $this->teacher_id = (string) (Auth::user()->staffProfile?->id ?? '');
        }
    }

    private function canEdit(): bool
    {
        return Auth::user()->isRole('admin');
    }

    /** Only admins can open a timetable other than the active one. */
    private function current(): TimetableModel
    {
        $service = app(TimetableService::class);

        if ($this->canEdit() && $this->timetable_id !== '') {
            $chosen = TimetableModel::find($this->timetable_id);
            if ($chosen) {
                return $chosen;
            }
        }

        return $service->active(Auth::user()->school_id);
    }

    private function sections()
    {
        $ids = Auth::user()->teachableSectionIds();

        return Section::with('schoolClass')->when($ids !== null, fn ($q) => $q->whereIn('id', $ids))->get()
            ->sortBy(fn ($s) => [$s->schoolClass->sort, $s->name])->values();
    }

    private function section(): Section
    {
        return Section::findOrFail($this->section_id);
    }

    private function period(int $id): TimetablePeriod
    {
        return TimetablePeriod::where('timetable_id', $this->current()->id)->findOrFail($id);
    }

    private function entry(int $id): TimetableEntry
    {
        return TimetableEntry::where('section_id', $this->section_id)
            ->whereIn('timetable_period_id', $this->current()->periods()->pluck('id'))->findOrFail($id);
    }

    /** Runs a change and shows any scheduling rule it broke as a message. */
    private function attempt(callable $change): bool
    {
        abort_unless($this->canEdit(), 403);

        try {
            $change();

            return true;
        } catch (InvalidArgumentException $e) {
            $this->dispatch('toast', message: $e->getMessage(), error: true);
        } catch (UniqueConstraintViolationException) {
            $this->dispatch('toast', message: 'Someone just changed this slot. Please try again.', error: true);
        }

        return false;
    }

    // Drag and drop

    public function placeEntry(TimetableService $service, int $periodId, int $day, int $subjectId, ?int $staffId = null): void
    {
        $this->attempt(fn () => $service->place($this->section(), $this->period($periodId), $day, $subjectId, $staffId));
    }

    public function moveEntry(TimetableService $service, int $entryId, int $periodId, int $day): void
    {
        $this->attempt(fn () => $service->move($this->entry($entryId), $this->period($periodId), $day));
    }

    public function removeEntry(int $entryId): void
    {
        $this->attempt(fn () => $this->entry($entryId)->delete());
    }

    // Tap to edit, which also works on phones where dragging does not

    public function pick(int $periodId, int $day): void
    {
        abort_unless($this->canEdit(), 403);

        $entry = TimetableEntry::where(['section_id' => $this->section_id, 'timetable_period_id' => $this->period($periodId)->id, 'day' => $day])->first();
        $this->picked = ['period' => $periodId, 'day' => $day];
        $this->subject_id = (string) $entry?->subject_id;
        $this->staff_id = (string) $entry?->staff_id;
        $this->resetValidation();
    }

    public function saveSlot(TimetableService $service): void
    {
        abort_unless($this->canEdit(), 403);
        $schoolId = Auth::user()->school_id;

        $this->validate([
            'subject_id' => ['required', Rule::exists('subjects', 'id')->where('school_id', $schoolId)],
            'staff_id' => ['nullable', Rule::exists('staff', 'id')->where('school_id', $schoolId)],
        ]);

        try {
            $service->place($this->section(), $this->period($this->picked['period']), $this->picked['day'], (int) $this->subject_id, $this->staff_id !== '' ? (int) $this->staff_id : null);
            $this->picked = null;
        } catch (InvalidArgumentException $e) {
            $this->addError('staff_id', $e->getMessage());
        }
    }

    public function clearSlot(): void
    {
        abort_unless($this->canEdit(), 403);

        TimetableEntry::where(['section_id' => $this->section_id, 'timetable_period_id' => $this->period($this->picked['period'])->id, 'day' => $this->picked['day']])->delete();
        $this->picked = null;
    }

    public function applySuggestion(TimetableService $service, int $subjectId, ?int $staffId = null): void
    {
        $picked = $this->picked;

        if ($picked && $this->attempt(fn () => $service->place($this->section(), $this->period($picked['period']), $picked['day'], $subjectId, $staffId))) {
            $this->picked = null;
        }
    }

    // Suggested periods for a whole class, shown on the grid until applied

    public function suggestPlan(TimetableService $service): void
    {
        $this->attempt(function () use ($service) {
            $plan = $service->suggestPlan($this->section(), $this->current());

            if ($plan['placements']) {
                $this->plan = $plan;
            } else {
                $this->plan = null;
                $this->dispatch('toast', message: $plan['unfilled'] ? "None of this class's subject teachers are free in its {$plan['unfilled']} empty periods. Click an empty box to see who is free." : 'This class has no empty periods, or no subjects assigned under Subject teachers.', error: true);
            }
        });
    }

    public function discardSuggestion(string $slot): void
    {
        abort_unless($this->canEdit(), 403);

        unset($this->plan['placements'][$slot]);

        if (empty($this->plan['placements'])) {
            $this->plan = null;
        }
    }

    public function applyPlan(TimetableService $service): void
    {
        $this->attempt(function () use ($service) {
            $result = $service->applyPlan($this->section(), $this->current(), $this->plan['placements'] ?? []);
            $this->plan = null;

            $message = "{$result['applied']} periods added.";
            if ($result['skipped']) {
                $message .= " {$result['skipped']} skipped because the slot or teacher was taken in the meantime.";
            }
            $this->dispatch('toast', message: $message, error: (bool) $result['skipped']);
        });
    }

    public function updatedSectionId(): void
    {
        $this->plan = null;
    }

    public function updatedTimetableId(): void
    {
        $this->plan = null;
    }

    // Whole-class tools

    public function copyDay(TimetableService $service): void
    {
        $this->plan = null;

        $this->attempt(function () use ($service) {
            $from = (int) $this->copyDayFrom;
            $to = $this->copyDayTo === 'all' ? array_keys(TimetableService::DAYS) : [(int) $this->copyDayTo];
            $result = $service->copyDay($this->section(), $this->current(), $from, $to);

            $message = "{$result['copied']} periods copied.";
            if ($result['skipped']) {
                $message .= ' Skipped because the teacher is busy: '.implode(', ', array_slice($result['skipped'], 0, 4)).(count($result['skipped']) > 4 ? ' and more.' : '.');
            }
            $this->dispatch('toast', message: $message, error: (bool) $result['skipped']);
        });
    }

    public function clearClass(): void
    {
        $this->plan = null;

        $this->attempt(function () {
            TimetableEntry::where('section_id', $this->section_id)->whereIn('timetable_period_id', $this->current()->periods()->pluck('id'))->delete();
            $this->dispatch('toast', message: 'This class is now empty on this timetable.');
        });
    }

    // Timetables

    public function openTimetableForm(string $mode): void
    {
        abort_unless($this->canEdit(), 403);
        abort_unless(in_array($mode, ['new', 'rename'], true), 422);

        $this->resetValidation();
        $this->timetableForm = $mode;
        $this->timetableName = $mode === 'rename' ? $this->current()->name : '';
        $this->copyFrom = (string) $this->current()->id;
    }

    public function saveTimetable(TimetableService $service): void
    {
        abort_unless($this->canEdit(), 403);
        $schoolId = Auth::user()->school_id;
        $current = $this->current();

        $this->validate([
            'timetableName' => ['required', 'string', 'max:80', Rule::unique('timetables', 'name')->where('school_id', $schoolId)->ignore($this->timetableForm === 'rename' ? $current->id : null)],
            'copyFrom' => ['nullable', Rule::exists('timetables', 'id')->where('school_id', $schoolId)],
        ], ['timetableName.unique' => 'A timetable with this name already exists.'], ['timetableName' => 'name']);

        if ($this->timetableForm === 'rename') {
            $current->update(['name' => $this->timetableName]);
        } else {
            $created = $this->copyFrom !== ''
                ? $service->duplicate(TimetableModel::findOrFail($this->copyFrom), $this->timetableName)
                : TimetableModel::create(['name' => $this->timetableName]);
            $this->timetable_id = (string) $created->id;
        }

        $this->timetableForm = null;
        $this->dispatch('toast', message: 'Timetable saved.');
    }

    public function activateTimetable(TimetableService $service): void
    {
        $this->attempt(function () use ($service) {
            $timetable = $this->current();
            $service->activate($timetable);
            $this->dispatch('toast', message: "{$timetable->name} is now live for teachers and parents.");
        });
    }

    public function deleteTimetable(TimetableService $service): void
    {
        $this->attempt(function () use ($service) {
            $service->delete($this->current());
            $this->timetable_id = '';
            $this->dispatch('toast', message: 'Timetable deleted.');
        });
    }

    // Periods

    public function openPeriodForm(?int $id = null): void
    {
        abort_unless($this->canEdit(), 403);

        $this->resetValidation();
        $period = $id ? $this->period($id) : null;
        $this->editingPeriodId = $period?->id;
        $this->periodName = (string) $period?->name;
        $this->periodStart = (string) $period?->starts_at;
        $this->periodEnd = (string) $period?->ends_at;
        $this->periodIsBreak = (bool) $period?->is_break;
        $this->showPeriodForm = true;
    }

    public function savePeriod(): void
    {
        abort_unless($this->canEdit(), 403);

        $this->validate([
            'periodName' => 'required|string|max:30',
            'periodStart' => 'required|date_format:H:i',
            'periodEnd' => 'required|date_format:H:i|after:periodStart',
            'periodIsBreak' => 'boolean',
        ], [], ['periodName' => 'name', 'periodStart' => 'start time', 'periodEnd' => 'end time']);

        $timetable = $this->current();

        $overlap = $timetable->periods()->where('id', '!=', (int) $this->editingPeriodId)
            ->where('starts_at', '<', $this->periodEnd)->where('ends_at', '>', $this->periodStart)->first();

        if ($overlap) {
            $this->addError('periodStart', "This overlaps {$overlap->name} ({$overlap->starts_at}-{$overlap->ends_at}).");

            return;
        }

        $values = ['name' => $this->periodName, 'starts_at' => $this->periodStart, 'ends_at' => $this->periodEnd, 'is_break' => $this->periodIsBreak];

        if ($this->editingPeriodId) {
            $period = $this->period($this->editingPeriodId);
            $period->update($values);
            if ($period->is_break) {
                $period->entries()->delete();
            }
        } else {
            $timetable->periods()->create($values + ['school_id' => $timetable->school_id]);
        }

        $this->showPeriodForm = false;
    }

    public function deletePeriod(int $id): void
    {
        abort_unless($this->canEdit(), 403);
        $this->period($id)->delete();
        $this->showPeriodForm = false;
    }

    public function render(TimetableService $service)
    {
        $current = $this->current();
        $this->timetable_id = $this->canEdit() ? (string) $current->id : '';
        $periods = $current->periods()->get();
        $periodIds = $periods->pluck('id');
        $sections = $this->sections();
        $sectionAllowed = $sections->contains('id', (int) $this->section_id);
        $canEdit = $this->canEdit();
        $section = $sectionAllowed ? $sections->firstWhere('id', (int) $this->section_id) : null;

        // Teachers see only their own week.
        $teacherId = Auth::user()->isRole('teacher') ? (string) (Auth::user()->staffProfile?->id ?? '') : $this->teacher_id;
        $teachers = Staff::where('is_teacher', true)->where('status', 'active')->orderBy('name')->get();
        $loads = $service->loads($current);

        $classEntries = $section
            ? TimetableEntry::with(['subject', 'staff'])->where('section_id', $section->id)->whereIn('timetable_period_id', $periodIds)->get()
            : collect();

        $this->busyMap = $canEdit && $section ? $service->busyMap($current, $section->id) : [];
        $slotCount = $periods->where('is_break', false)->count() * count(TimetableService::DAYS);

        $palette = collect();
        if ($canEdit && $section) {
            $palette = ClassSubject::with(['subject', 'staff'])->where('school_class_id', $section->school_class_id)->get()
                ->map(fn ($a) => [
                    'subject_id' => $a->subject_id,
                    'subject' => $a->subject->name,
                    'staff_id' => $a->staff_id,
                    'staff' => $a->staff?->name,
                    'placed' => $classEntries->where('subject_id', $a->subject_id)->count(),
                    'free' => $slotCount - count($this->busyMap['s'.$a->staff_id] ?? []),
                ]);
        }

        // Teachers busy in the slot being edited, so the picker can say where they are.
        $slotKey = $this->picked ? $this->picked['period'].'-'.$this->picked['day'] : null;
        $slotBusy = $teachers->mapWithKeys(fn ($t) => [$t->id => $this->busyMap['s'.$t->id][$slotKey] ?? null])->filter()->all();

        return view('livewire.academics.timetable', [
            'current' => $current,
            'school' => Auth::user()->school,
            'timetables' => $canEdit ? TimetableModel::orderByDesc('is_active')->orderBy('name')->get() : collect(),
            'sections' => $sections,
            'section' => $section,
            'periods' => $periods,
            'days' => TimetableService::DAYS,
            'entries' => $classEntries->keyBy(fn ($e) => $e->timetable_period_id.'-'.$e->day),
            'teacherEntries' => $teacherId !== ''
                ? TimetableEntry::with(['subject', 'section.schoolClass'])->where('staff_id', $teacherId)->whereIn('timetable_period_id', $periodIds)->get()->keyBy(fn ($e) => $e->timetable_period_id.'-'.$e->day)
                : collect(),
            'selectedTeacher' => $teacherId !== '' ? $teachers->firstWhere('id', (int) $teacherId) : null,
            'palette' => $palette,
            'slotCount' => $slotCount,
            'slotBusy' => $slotBusy,
            'slotOptions' => $this->picked && $section ? $service->slotOptions($section, $this->period($this->picked['period']), $this->picked['day']) : collect(),
            'slotFree' => $this->picked && $section ? $service->freeTeachers($section->school_id, $this->picked['period'], $this->picked['day'], $section->id)->pluck('name') : collect(),
            'loads' => $loads,
            'subjects' => Subject::orderBy('name')->get(),
            'teachers' => $teachers,
            'canEdit' => $canEdit,
            'isTeacher' => Auth::user()->isRole('teacher'),
        ]);
    }
}
