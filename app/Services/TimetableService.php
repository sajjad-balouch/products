<?php

namespace App\Services;

use App\Models\ClassSubject;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Subject;
use App\Models\Timetable;
use App\Models\TimetableEntry;
use App\Models\TimetablePeriod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * A teacher may take any number of periods. The one hard rule is that a teacher
 * cannot be in two classes in the same period of the same timetable.
 */
class TimetableService
{
    public const DAYS = [1 => 'Mon', 2 => 'Tue', 3 => 'Wed', 4 => 'Thu', 5 => 'Fri', 6 => 'Sat'];

    public function active(int $schoolId): Timetable
    {
        $active = Timetable::withoutGlobalScopes()->where('school_id', $schoolId)->where('is_active', true)->first();

        return $active ?? Timetable::create(['school_id' => $schoolId, 'name' => 'Main timetable', 'is_active' => true]);
    }

    public function activate(Timetable $timetable): void
    {
        DB::transaction(function () use ($timetable) {
            Timetable::withoutGlobalScopes()->where('school_id', $timetable->school_id)->update(['is_active' => false]);
            $timetable->update(['is_active' => true]);
        });
    }

    /** Copies periods and every class's entries, so a new timetable starts from an existing one. */
    public function duplicate(Timetable $source, string $name): Timetable
    {
        return DB::transaction(function () use ($source, $name) {
            $copy = Timetable::create(['school_id' => $source->school_id, 'name' => $name]);

            foreach ($source->periods()->with('entries')->get() as $period) {
                $newPeriod = $copy->periods()->create([
                    'school_id' => $source->school_id,
                    'name' => $period->name,
                    'starts_at' => $period->starts_at,
                    'ends_at' => $period->ends_at,
                    'is_break' => $period->is_break,
                    'sort' => $period->sort,
                ]);

                foreach ($period->entries as $entry) {
                    TimetableEntry::create([
                        'school_id' => $entry->school_id,
                        'section_id' => $entry->section_id,
                        'timetable_period_id' => $newPeriod->id,
                        'day' => $entry->day,
                        'subject_id' => $entry->subject_id,
                        'staff_id' => $entry->staff_id,
                    ]);
                }
            }

            return $copy;
        });
    }

    public function delete(Timetable $timetable): void
    {
        if ($timetable->is_active) {
            throw new InvalidArgumentException('The active timetable cannot be deleted. Make another one active first.');
        }

        $timetable->delete();
    }

    /** Fills a slot, replacing whatever the class had there. */
    public function place(Section $section, TimetablePeriod $period, int $day, int $subjectId, ?int $staffId): TimetableEntry
    {
        $this->assertSlot($period, $day);
        $this->assertTeacherFree($staffId, $period->id, $day, $section->id);

        $subject = Subject::where('school_id', $section->school_id)->findOrFail($subjectId);
        if ($staffId) {
            Staff::where('school_id', $section->school_id)->findOrFail($staffId);
        }

        return TimetableEntry::updateOrCreate(
            ['section_id' => $section->id, 'timetable_period_id' => $period->id, 'day' => $day],
            ['school_id' => $section->school_id, 'subject_id' => $subject->id, 'staff_id' => $staffId],
        );
    }

    /** Moves an entry within its class. Dropping on a filled slot swaps the two. */
    public function move(TimetableEntry $entry, TimetablePeriod $period, int $day): void
    {
        $this->assertSlot($period, $day);

        $from = ['timetable_period_id' => $entry->timetable_period_id, 'day' => $entry->day];

        if ($from === ['timetable_period_id' => $period->id, 'day' => $day]) {
            return;
        }

        $other = TimetableEntry::where(['section_id' => $entry->section_id, 'timetable_period_id' => $period->id, 'day' => $day])->first();

        $this->assertTeacherFree($entry->staff_id, $period->id, $day, $entry->section_id);
        if ($other) {
            $this->assertTeacherFree($other->staff_id, $from['timetable_period_id'], $from['day'], $entry->section_id);
        }

        DB::transaction(function () use ($entry, $other, $period, $day, $from) {
            // Day 0 parks the other entry so the unique slot rules hold during the swap.
            $other?->update(['day' => 0]);
            $entry->update(['timetable_period_id' => $period->id, 'day' => $day]);
            $other?->update($from);
        });
    }

    /** @return array{copied: int, skipped: array<int, string>} slots with a teacher clash stay as they were */
    public function copyDay(Section $section, Timetable $timetable, int $from, array $toDays): array
    {
        $periodIds = $timetable->periods()->pluck('id');
        $source = TimetableEntry::with('staff')->where('section_id', $section->id)->whereIn('timetable_period_id', $periodIds)->where('day', $from)->get();
        $result = ['copied' => 0, 'skipped' => []];

        DB::transaction(function () use ($source, $section, $periodIds, $toDays, $from, &$result) {
            foreach ($toDays as $day) {
                if ($day === $from || ! isset(self::DAYS[$day])) {
                    continue;
                }

                TimetableEntry::where('section_id', $section->id)->whereIn('timetable_period_id', $periodIds)->where('day', $day)->delete();

                foreach ($source as $entry) {
                    try {
                        $this->assertTeacherFree($entry->staff_id, $entry->timetable_period_id, $day, $section->id);
                    } catch (InvalidArgumentException) {
                        $result['skipped'][] = self::DAYS[$day].': '.$entry->staff->name;

                        continue;
                    }

                    TimetableEntry::create([
                        'school_id' => $section->school_id,
                        'section_id' => $section->id,
                        'timetable_period_id' => $entry->timetable_period_id,
                        'day' => $day,
                        'subject_id' => $entry->subject_id,
                        'staff_id' => $entry->staff_id,
                    ]);
                    $result['copied']++;
                }
            }
        });

        return $result;
    }

    /** Periods per teacher in a timetable. @return Collection<int, array{total: int, days: array<int, int>}> */
    public function loads(Timetable $timetable): Collection
    {
        return TimetableEntry::whereIn('timetable_period_id', $timetable->periods()->pluck('id'))->whereNotNull('staff_id')
            ->get(['staff_id', 'day'])->groupBy('staff_id')
            ->map(fn ($rows) => ['total' => $rows->count(), 'days' => $rows->countBy('day')->all()]);
    }

    /** The other class each teacher is in, by slot, for live drag hints. @return array<string, array<string, string>> */
    public function busyMap(Timetable $timetable, int $sectionId): array
    {
        return TimetableEntry::with('section.schoolClass')->whereIn('timetable_period_id', $timetable->periods()->pluck('id'))
            ->whereNotNull('staff_id')->where('section_id', '!=', $sectionId)
            ->get()->groupBy('staff_id')
            ->mapWithKeys(fn ($rows, $staffId) => ['s'.$staffId => $rows->mapWithKeys(fn ($e) => [$e->timetable_period_id.'-'.$e->day => $e->section->label])->all()])
            ->all();
    }

    /** Active teachers with no class in this slot. @return Collection<int, Staff> */
    public function freeTeachers(int $schoolId, int $periodId, int $day, int $sectionId): Collection
    {
        $busy = TimetableEntry::where(['timetable_period_id' => $periodId, 'day' => $day])->where('section_id', '!=', $sectionId)->whereNotNull('staff_id')->pluck('staff_id');

        return Staff::where('school_id', $schoolId)->where('is_teacher', true)->where('status', 'active')->whereNotIn('id', $busy)->orderBy('name')->get();
    }

    /**
     * What this class could have in one slot, best first: its own subject teachers who are
     * free, spreading subjects across the week, then other free teachers standing in.
     *
     * @return Collection<int, array{subject_id: int, subject: string, staff_id: ?int, staff: ?string, reason: string}>
     */
    public function slotOptions(Section $section, TimetablePeriod $period, int $day, int $limit = 5): Collection
    {
        $assignments = $this->assignments($section);
        $entries = $this->classEntries($section, $period->timetable)
            ->reject(fn ($e) => $e->timetable_period_id === $period->id && $e->day === $day);
        $current = TimetableEntry::where(['section_id' => $section->id, 'timetable_period_id' => $period->id, 'day' => $day])->first();
        $busy = TimetableEntry::with('section.schoolClass')->where(['timetable_period_id' => $period->id, 'day' => $day])
            ->where('section_id', '!=', $section->id)->whereNotNull('staff_id')->get()->keyBy('staff_id');
        $placed = fn ($subjectId) => $entries->where('subject_id', $subjectId)->count();

        $usual = $assignments->reject(fn ($a) => $a->staff_id && $busy->has($a->staff_id))
            ->sortBy(fn ($a) => [$a->staff_id ? 0 : 1, $entries->where('subject_id', $a->subject_id)->where('day', $day)->count(), $placed($a->subject_id)])
            ->map(function ($a) use ($entries, $placed, $day) {
                $today = $entries->where('subject_id', $a->subject_id)->where('day', $day)->count();
                $dayName = self::DAYS[$day];
                $spread = ($today ? "Already {$today} on {$dayName}" : "None on {$dayName} yet").', '.$placed($a->subject_id).' this week';

                return $this->option($a->subject, $a->staff, $a->staff_id ? $spread : "No teacher assigned. {$spread}");
            });

        // Subjects whose own teacher is busy can go to another free teacher, preferring one who teaches that subject elsewhere.
        $uncovered = $assignments->filter(fn ($a) => $a->staff_id && $busy->has($a->staff_id))->sortBy(fn ($a) => $placed($a->subject_id))->values();
        $free = $this->freeTeachers($section->school_id, $period->id, $day, $section->id)->whereNotIn('id', $assignments->pluck('staff_id')->filter());
        $teaches = ClassSubject::whereIn('staff_id', $free->pluck('id'))->get()->groupBy('staff_id')->map(fn ($rows) => $rows->pluck('subject_id'));

        $standIns = $uncovered->isEmpty() ? collect() : $free->map(function ($staff) use ($uncovered, $teaches, $busy) {
            $known = $teaches->get($staff->id, collect());
            $match = $uncovered->first(fn ($a) => $known->contains($a->subject_id));
            $a = $match ?? $uncovered->first();
            $why = "{$a->staff->name} is teaching {$busy->get($a->staff_id)->section->label}. ";

            return $this->option($a->subject, $staff, $why.($match ? "{$staff->name} teaches this subject too." : 'Not a subject they usually teach.'));
        })->sortBy(fn ($o) => str_contains($o['reason'], 'Not a subject') ? 1 : 0);

        return $usual->concat($standIns)
            ->reject(fn ($o) => $current && $o['subject_id'] === $current->subject_id && $o['staff_id'] === $current->staff_id)
            ->take($limit)->values();
    }

    /**
     * A proposal for every empty slot of a class, using its own subject teachers. Nothing is saved.
     * The slots with the fewest choices go first so they are not left with nothing.
     *
     * @return array{placements: array<string, array{period: int, day: int, subject_id: int, subject: string, staff_id: ?int, staff: ?string}>, unfilled: int}
     */
    public function suggestPlan(Section $section, Timetable $timetable): array
    {
        $assignments = $this->assignments($section);
        $entries = $this->classEntries($section, $timetable);
        $periods = $timetable->periods()->where('is_break', false)->get();
        $taken = TimetableEntry::whereIn('timetable_period_id', $periods->pluck('id'))->where('section_id', '!=', $section->id)->whereNotNull('staff_id')
            ->get(['staff_id', 'timetable_period_id', 'day'])
            ->groupBy(fn ($e) => $e->timetable_period_id.'-'.$e->day)->map(fn ($rows) => $rows->pluck('staff_id'));
        $filled = $entries->map(fn ($e) => $e->timetable_period_id.'-'.$e->day);

        $placed = $entries->countBy('subject_id')->all();
        $onDay = $entries->groupBy('subject_id')->map(fn ($rows) => $rows->countBy('day')->all())->all();

        $slots = [];
        foreach ($periods as $period) {
            foreach (array_keys(self::DAYS) as $day) {
                $key = $period->id.'-'.$day;
                if (! $filled->contains($key)) {
                    $busy = $taken->get($key, collect());
                    $slots[] = ['key' => $key, 'period' => $period->id, 'day' => $day, 'choices' => $assignments->reject(fn ($a) => $a->staff_id && $busy->contains($a->staff_id))];
                }
            }
        }
        usort($slots, fn ($a, $b) => $a['choices']->whereNotNull('staff_id')->count() <=> $b['choices']->whereNotNull('staff_id')->count());

        $placements = [];
        $unfilled = 0;
        foreach ($slots as $slot) {
            $pick = $slot['choices']->sortBy(fn ($a) => [$a->staff_id ? 0 : 1, $onDay[$a->subject_id][$slot['day']] ?? 0, $placed[$a->subject_id] ?? 0])->first();

            if (! $pick) {
                $unfilled++;

                continue;
            }

            $placed[$pick->subject_id] = ($placed[$pick->subject_id] ?? 0) + 1;
            $onDay[$pick->subject_id][$slot['day']] = ($onDay[$pick->subject_id][$slot['day']] ?? 0) + 1;
            $placements[$slot['key']] = ['period' => $slot['period'], 'day' => $slot['day']] + $this->option($pick->subject, $pick->staff);
        }

        return ['placements' => $placements, 'unfilled' => $unfilled];
    }

    /** Saves a proposal. Slots that were filled or became a clash since it was made are left alone. @return array{applied: int, skipped: int} */
    public function applyPlan(Section $section, Timetable $timetable, array $placements): array
    {
        $periods = $timetable->periods()->get()->keyBy('id');
        $result = ['applied' => 0, 'skipped' => 0];

        DB::transaction(function () use ($section, $periods, $placements, &$result) {
            foreach ($placements as $p) {
                $period = $periods->get($p['period']);
                $occupied = TimetableEntry::where(['section_id' => $section->id, 'timetable_period_id' => $p['period'], 'day' => $p['day']])->exists();

                try {
                    if (! $period || $occupied) {
                        throw new InvalidArgumentException('Slot is no longer open.');
                    }
                    $this->place($section, $period, $p['day'], $p['subject_id'], $p['staff_id']);
                    $result['applied']++;
                } catch (InvalidArgumentException) {
                    $result['skipped']++;
                }
            }
        });

        return $result;
    }

    private function assignments(Section $section): Collection
    {
        return ClassSubject::with(['subject', 'staff'])->where('school_class_id', $section->school_class_id)->get()
            ->reject(fn ($a) => $a->staff_id && $a->staff?->status !== 'active')->values();
    }

    private function classEntries(Section $section, Timetable $timetable): Collection
    {
        return TimetableEntry::where('section_id', $section->id)->whereIn('timetable_period_id', $timetable->periods()->pluck('id'))->get();
    }

    private function option(Subject $subject, ?Staff $staff, string $reason = ''): array
    {
        return ['subject_id' => $subject->id, 'subject' => $subject->name, 'staff_id' => $staff?->id, 'staff' => $staff?->name, 'reason' => $reason];
    }

    private function assertSlot(TimetablePeriod $period, int $day): void
    {
        if (! isset(self::DAYS[$day])) {
            throw new InvalidArgumentException('That day is not on the timetable.');
        }
        if ($period->is_break) {
            throw new InvalidArgumentException('A class cannot be placed in a break.');
        }
    }

    private function assertTeacherFree(?int $staffId, int $periodId, int $day, int $sectionId): void
    {
        if (! $staffId) {
            return;
        }

        $clash = TimetableEntry::with(['section.schoolClass', 'staff'])
            ->where(['staff_id' => $staffId, 'timetable_period_id' => $periodId, 'day' => $day])
            ->where('section_id', '!=', $sectionId)->first();

        if ($clash) {
            $free = $this->freeTeachers($clash->school_id, $periodId, $day, $sectionId)->pluck('name');

            throw new InvalidArgumentException("{$clash->staff->name} is already teaching {$clash->section->label} in this period. ".($free->isEmpty() ? 'No teacher is free then.' : 'Free then: '.$free->take(4)->implode(', ').'.'));
        }
    }
}
