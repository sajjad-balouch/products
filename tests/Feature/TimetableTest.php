<?php

namespace Tests\Feature;

use App\Livewire\Academics\Timetable as Board;
use App\Models\ClassSubject;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Staff;
use App\Models\Subject;
use App\Models\Timetable;
use App\Models\TimetableEntry;
use App\Models\TimetablePeriod;
use App\Models\User;
use App\Services\SchoolProvisioner;
use App\Services\TimetableService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;
use Livewire\Livewire;
use Tests\TestCase;

class TimetableTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Timetable $timetable;

    private Section $classA;

    private Section $classB;

    private Subject $math;

    private Staff $teacher;

    private TimetablePeriod $first;

    private TimetablePeriod $second;

    private TimetablePeriod $break;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = app(SchoolProvisioner::class)->create(['school' => 'School A', 'type' => 'school', 'name' => 'Head', 'email' => 'head@example.test', 'phone' => '03000000000', 'password' => 'password']);
        $schoolId = $this->admin->school_id;
        $this->timetable = Timetable::where('school_id', $schoolId)->firstOrFail();
        $classOne = SchoolClass::create(['school_id' => $schoolId, 'name' => 'One']);
        $classTwo = SchoolClass::create(['school_id' => $schoolId, 'name' => 'Two']);
        $this->classA = Section::create(['school_id' => $schoolId, 'school_class_id' => $classOne->id, 'name' => 'A']);
        $this->classB = Section::create(['school_id' => $schoolId, 'school_class_id' => $classTwo->id, 'name' => 'A']);
        $this->math = Subject::create(['school_id' => $schoolId, 'name' => 'Math']);
        $this->teacher = Staff::create(['school_id' => $schoolId, 'name' => 'Sara']);
        $this->first = $this->makePeriod('P1', '08:00', '08:45');
        $this->second = $this->makePeriod('P2', '08:45', '09:30');
        $this->break = $this->makePeriod('Break', '09:30', '10:00', true);
    }

    private function makePeriod(string $name, string $start, string $end, bool $break = false): TimetablePeriod
    {
        return TimetablePeriod::create(['school_id' => $this->admin->school_id, 'timetable_id' => $this->timetable->id, 'name' => $name, 'starts_at' => $start, 'ends_at' => $end, 'is_break' => $break]);
    }

    private function board()
    {
        return Livewire::actingAs($this->admin)->test(Board::class)->set('section_id', (string) $this->classA->id);
    }

    private function entry(Section $section, TimetablePeriod $period, int $day, ?Staff $staff = null, ?Subject $subject = null): TimetableEntry
    {
        return TimetableEntry::create(['school_id' => $section->school_id, 'section_id' => $section->id, 'timetable_period_id' => $period->id, 'day' => $day, 'subject_id' => ($subject ?? $this->math)->id, 'staff_id' => $staff?->id]);
    }

    public function test_one_teacher_can_take_many_periods_including_back_to_back(): void
    {
        $board = $this->board();

        foreach ([[$this->first, 1], [$this->second, 1], [$this->first, 2], [$this->second, 2]] as [$period, $day]) {
            $board->call('placeEntry', $period->id, $day, $this->math->id, $this->teacher->id);
        }

        $this->assertSame(4, TimetableEntry::count());
        $this->assertSame(4, app(TimetableService::class)->loads($this->timetable)[$this->teacher->id]['total']);
    }

    public function test_a_teacher_can_teach_two_classes_at_different_times(): void
    {
        $this->entry($this->classB, $this->first, 1, $this->teacher);

        $this->board()->call('placeEntry', $this->second->id, 1, $this->math->id, $this->teacher->id)->assertNotDispatched('toast');

        $this->assertSame(2, TimetableEntry::where('staff_id', $this->teacher->id)->count());
    }

    public function test_dropping_a_busy_teacher_is_refused_with_the_reason(): void
    {
        $this->entry($this->classB, $this->first, 1, $this->teacher);

        $this->board()->call('placeEntry', $this->first->id, 1, $this->math->id, $this->teacher->id)
            ->assertDispatched('toast', fn ($name, $p) => ($p['error'] ?? false) && str_contains($p['message'], 'Sara is already teaching'));

        $this->assertSame(1, TimetableEntry::count());
    }

    public function test_an_empty_class_still_shows_where_its_teacher_is_busy_and_who_is_free(): void
    {
        $this->teacher->update(['is_teacher' => true, 'status' => 'active']);
        Staff::create(['school_id' => $this->admin->school_id, 'name' => 'Omar', 'is_teacher' => true, 'status' => 'active']);
        ClassSubject::create(['school_id' => $this->admin->school_id, 'school_class_id' => $this->classA->school_class_id, 'subject_id' => $this->math->id, 'staff_id' => $this->teacher->id]);
        $this->entry($this->classB, $this->first, 1, $this->teacher);
        $slot = $this->first->id.'-1';

        $board = $this->board()->assertSet('busyMap.s'.$this->teacher->id.'.'.$slot, $this->classB->label)->assertSee('11 of 12 free');

        $board->call('placeEntry', $this->first->id, 1, $this->math->id, $this->teacher->id)
            ->assertDispatched('toast', fn ($name, $p) => str_contains($p['message'], 'teaching '.$this->classB->label) && str_contains($p['message'], 'Free then: Omar.'));

        $board->call('pick', $this->first->id, 1)->assertSee('Sara (teaching '.$this->classB->label.')');
        $board->set('picked', null)->call('pick', $this->second->id, 1)->assertDontSee('Sara (teaching');
    }

    public function test_drag_moves_a_period_to_an_empty_slot(): void
    {
        $entry = $this->entry($this->classA, $this->first, 1, $this->teacher);

        $this->board()->call('moveEntry', $entry->id, $this->second->id, 3);

        $entry->refresh();
        $this->assertSame($this->second->id, $entry->timetable_period_id);
        $this->assertSame(3, (int) $entry->day);
    }

    public function test_dropping_on_a_filled_slot_swaps_the_two(): void
    {
        $english = Subject::create(['school_id' => $this->admin->school_id, 'name' => 'English']);
        $a = $this->entry($this->classA, $this->first, 1, $this->teacher);
        $b = $this->entry($this->classA, $this->second, 2, null, $english);

        $this->board()->call('moveEntry', $a->id, $this->second->id, 2);

        $a->refresh();
        $b->refresh();
        $this->assertSame([$this->second->id, 2], [$a->timetable_period_id, (int) $a->day]);
        $this->assertSame([$this->first->id, 1], [$b->timetable_period_id, (int) $b->day]);
    }

    public function test_a_swap_that_would_double_book_changes_nothing(): void
    {
        $other = Staff::create(['school_id' => $this->admin->school_id, 'name' => 'Imran']);
        $a = $this->entry($this->classA, $this->first, 1, $this->teacher);
        $b = $this->entry($this->classA, $this->second, 1, $other);
        // Imran is busy elsewhere in P1, so he cannot swap into it.
        $this->entry($this->classB, $this->first, 1, $other);

        $this->board()->call('moveEntry', $a->id, $this->second->id, 1)
            ->assertDispatched('toast', fn ($name, $p) => $p['error'] ?? false);

        $this->assertSame($this->first->id, $a->fresh()->timetable_period_id);
        $this->assertSame($this->second->id, $b->fresh()->timetable_period_id);
    }

    public function test_nothing_can_be_placed_in_a_break(): void
    {
        $this->board()->call('placeEntry', $this->break->id, 1, $this->math->id, null)
            ->assertDispatched('toast', fn ($name, $p) => $p['error'] ?? false);

        $this->assertSame(0, TimetableEntry::count());
    }

    public function test_remove_deletes_only_this_classes_entry(): void
    {
        $mine = $this->entry($this->classA, $this->first, 1);
        $theirs = $this->entry($this->classB, $this->first, 1);

        $this->board()->call('removeEntry', $theirs->id)->assertNotFound();
        $this->assertNotNull($theirs->fresh());

        $this->board()->call('removeEntry', $mine->id);
        $this->assertNull($mine->fresh());
    }

    public function test_new_timetable_copies_periods_and_entries_without_touching_the_original(): void
    {
        $original = $this->entry($this->classA, $this->first, 1, $this->teacher);

        $this->board()->call('openTimetableForm', 'new')->set('timetableName', 'Winter')->call('saveTimetable')->assertHasNoErrors();

        $winter = Timetable::where('name', 'Winter')->firstOrFail();
        $this->assertFalse($winter->is_active);
        $this->assertSame(3, $winter->periods()->count());

        $copy = TimetableEntry::whereIn('timetable_period_id', $winter->periods()->pluck('id'))->firstOrFail();
        $copy->delete();
        $this->assertNotNull($original->fresh());
    }

    public function test_blank_timetable_starts_empty_and_the_same_teacher_slot_is_free_in_it(): void
    {
        $this->entry($this->classB, $this->first, 1, $this->teacher);

        $page = $this->board()->call('openTimetableForm', 'new')->set('timetableName', 'Ramzan')->set('copyFrom', '')->call('saveTimetable');
        $ramzan = Timetable::where('name', 'Ramzan')->firstOrFail();
        $this->assertSame(0, $ramzan->periods()->count());

        $page->call('openPeriodForm')->set('periodName', 'P1')->set('periodStart', '08:00')->set('periodEnd', '08:30')->call('savePeriod')->assertHasNoErrors();
        $period = $ramzan->periods()->firstOrFail();

        $page->call('placeEntry', $period->id, 1, $this->math->id, $this->teacher->id);
        $this->assertSame(1, TimetableEntry::where('timetable_period_id', $period->id)->count());
    }

    public function test_duplicate_names_and_overlapping_periods_are_refused(): void
    {
        $this->board()->call('openTimetableForm', 'new')->set('timetableName', $this->timetable->name)->call('saveTimetable')->assertHasErrors('timetableName');

        $this->board()->call('openPeriodForm')->set('periodName', 'X')->set('periodStart', '08:30')->set('periodEnd', '09:00')->call('savePeriod')->assertHasErrors('periodStart');
    }

    public function test_rename_and_edit_an_existing_period(): void
    {
        $board = $this->board()->call('openTimetableForm', 'rename')->set('timetableName', 'Summer')->call('saveTimetable')->assertHasNoErrors();
        $this->assertSame('Summer', $this->timetable->fresh()->name);

        $board->call('openPeriodForm', $this->first->id)->assertSet('periodName', 'P1')->set('periodEnd', '08:40')->call('savePeriod')->assertHasNoErrors();
        $this->assertSame('08:40', $this->first->fresh()->ends_at);
    }

    public function test_only_the_active_timetable_is_shown_to_teachers_and_it_can_be_switched(): void
    {
        $user = User::create(['school_id' => $this->admin->school_id, 'name' => 'Sara', 'username' => 'sara@example.test', 'role' => 'teacher', 'password' => Hash::make('password')]);
        $this->teacher->update(['user_id' => $user->id]);
        $this->classA->update(['class_teacher_id' => $this->teacher->id]);
        $this->entry($this->classA, $this->first, 1, $this->teacher);
        $draft = app(TimetableService::class)->duplicate($this->timetable, 'Draft Plan');
        $draft->periods()->first()->update(['name' => 'Draft Period']);

        Livewire::actingAs($user)->test(Board::class)->set('timetable_id', (string) $draft->id)->assertDontSee('Draft Period')->assertSee('P1');

        app(TimetableService::class)->activate($draft->fresh());

        $this->assertFalse($this->timetable->fresh()->is_active);
        Livewire::actingAs($user)->test(Board::class)->assertSee('Draft Period');
    }

    public function test_teachers_cannot_change_the_timetable(): void
    {
        $user = User::create(['school_id' => $this->admin->school_id, 'name' => 'T', 'username' => 't@example.test', 'role' => 'teacher', 'password' => Hash::make('password')]);

        Livewire::actingAs($user)->test(Board::class)->call('placeEntry', $this->first->id, 1, $this->math->id, null)->assertForbidden();
        Livewire::actingAs($user)->test(Board::class)->call('openTimetableForm', 'new')->assertForbidden();
        $this->assertSame(0, TimetableEntry::count());
    }

    public function test_another_schools_timetable_and_periods_cannot_be_reached(): void
    {
        $other = app(SchoolProvisioner::class)->create(['school' => 'School B', 'type' => 'school', 'name' => 'B', 'email' => 'b@example.test', 'phone' => '03000000000', 'password' => 'password']);
        $theirTimetable = Timetable::where('school_id', $other->school_id)->firstOrFail();
        $theirPeriod = TimetablePeriod::create(['school_id' => $other->school_id, 'timetable_id' => $theirTimetable->id, 'name' => 'Theirs', 'starts_at' => '08:00', 'ends_at' => '09:00']);

        $this->board()->call('placeEntry', $theirPeriod->id, 1, $this->math->id, null)->assertNotFound();
        $this->board()->call('openPeriodForm', $theirPeriod->id)->assertNotFound();
        $this->board()->set('timetable_id', (string) $theirTimetable->id)->assertDontSee('Theirs');
        $this->assertSame(0, TimetableEntry::count());
    }

    public function test_active_timetable_cannot_be_deleted_but_a_draft_can(): void
    {
        $this->expectNotToPerformAssertions();

        $draft = Timetable::create(['school_id' => $this->admin->school_id, 'name' => 'Draft']);
        app(TimetableService::class)->delete($draft);

        try {
            app(TimetableService::class)->delete($this->timetable->fresh());
            $this->fail('The active timetable was deleted.');
        } catch (InvalidArgumentException) {
            //
        }
    }

    public function test_copy_day_skips_busy_teachers_and_reports_them(): void
    {
        $this->entry($this->classA, $this->first, 1, $this->teacher);
        $this->entry($this->classB, $this->first, 2, $this->teacher);

        $result = app(TimetableService::class)->copyDay($this->classA, $this->timetable, 1, [2, 3]);

        $this->assertSame(1, $result['copied']);
        $this->assertSame(['Tue: Sara'], $result['skipped']);
        $this->assertSame(0, TimetableEntry::where(['section_id' => $this->classA->id, 'day' => 2])->count());
        $this->assertSame(1, TimetableEntry::where(['section_id' => $this->classA->id, 'day' => 3])->count());
    }

    public function test_palette_shows_the_classes_subject_teachers(): void
    {
        ClassSubject::create(['school_id' => $this->admin->school_id, 'school_class_id' => $this->classA->school_class_id, 'subject_id' => $this->math->id, 'staff_id' => $this->teacher->id]);

        $this->board()->assertSee('data-tile="'.$this->math->id.'"', false)->assertSee('Sara');
    }

    /** Math with Sara and English with Omar for class A, both active teachers. */
    private function assignTeachers(): array
    {
        $schoolId = $this->admin->school_id;
        $this->teacher->update(['is_teacher' => true, 'status' => 'active']);
        $omar = Staff::create(['school_id' => $schoolId, 'name' => 'Omar', 'is_teacher' => true, 'status' => 'active']);
        $english = Subject::create(['school_id' => $schoolId, 'name' => 'English']);
        $classId = $this->classA->school_class_id;
        ClassSubject::create(['school_id' => $schoolId, 'school_class_id' => $classId, 'subject_id' => $this->math->id, 'staff_id' => $this->teacher->id]);
        ClassSubject::create(['school_id' => $schoolId, 'school_class_id' => $classId, 'subject_id' => $english->id, 'staff_id' => $omar->id]);

        return [$omar, $english];
    }

    public function test_slot_options_skip_busy_teachers_and_prefer_a_subject_missing_that_day(): void
    {
        [$omar, $english] = $this->assignTeachers();
        $service = app(TimetableService::class);
        $this->actingAs($this->admin);

        $this->entry($this->classA, $this->second, 1, $omar, $english);
        $this->assertSame([$this->math->id, $english->id], $service->slotOptions($this->classA, $this->first, 1)->pluck('subject_id')->all());

        $this->entry($this->classB, $this->first, 1, $this->teacher);
        $this->assertSame([$english->id], $service->slotOptions($this->classA, $this->first, 1)->pluck('subject_id')->all());
    }

    public function test_slot_options_offer_a_free_stand_in_who_teaches_the_subject(): void
    {
        $this->assignTeachers();
        $schoolId = $this->admin->school_id;
        Staff::create(['school_id' => $schoolId, 'name' => 'Zed', 'is_teacher' => true, 'status' => 'active']);
        $asma = Staff::create(['school_id' => $schoolId, 'name' => 'Asma', 'is_teacher' => true, 'status' => 'active']);
        ClassSubject::create(['school_id' => $schoolId, 'school_class_id' => $this->classB->school_class_id, 'subject_id' => $this->math->id, 'staff_id' => $asma->id]);
        $this->entry($this->classB, $this->first, 1, $this->teacher);
        $this->actingAs($this->admin);

        $standIns = app(TimetableService::class)->slotOptions($this->classA, $this->first, 1)->where('subject_id', $this->math->id)->values();

        $this->assertSame('Asma', $standIns[0]['staff']);
        $this->assertStringContainsString('Sara is teaching '.$this->classB->label, $standIns[0]['reason']);
        $this->assertStringContainsString('teaches this subject too', $standIns[0]['reason']);
        $this->assertStringContainsString('Not a subject', $standIns[1]['reason']);
    }

    public function test_clicking_a_suggestion_fills_the_slot(): void
    {
        [$omar, $english] = $this->assignTeachers();

        $this->board()->call('pick', $this->first->id, 2)->assertSee('Suggested for this period')
            ->call('applySuggestion', $english->id, $omar->id)->assertSet('picked', null);

        $this->assertDatabaseHas('timetable_entries', ['section_id' => $this->classA->id, 'timetable_period_id' => $this->first->id, 'day' => 2, 'staff_id' => $omar->id]);
    }

    public function test_a_plan_spreads_subjects_avoids_clashes_and_saves_nothing_until_applied(): void
    {
        [$omar] = $this->assignTeachers();
        $this->entry($this->classB, $this->first, 1, $this->teacher);

        $board = $this->board()->call('suggestPlan');
        $placements = $board->get('plan')['placements'];

        $this->assertCount(12, $placements);
        $this->assertSame($omar->id, $placements[$this->first->id.'-1']['staff_id']);
        $this->assertSame(6, collect($placements)->where('staff_id', $omar->id)->count());
        $this->assertArrayNotHasKey($this->break->id.'-1', $placements);
        $this->assertSame(1, TimetableEntry::count());

        $board->call('discardSuggestion', $this->second->id.'-6')->call('applyPlan')->assertSet('plan', null);

        $this->assertSame(11, TimetableEntry::where('section_id', $this->classA->id)->count());
        $this->assertSame(0, TimetableEntry::where('section_id', $this->classA->id)->where(['timetable_period_id' => $this->first->id, 'day' => 1, 'staff_id' => $this->teacher->id])->count());
    }

    public function test_a_plan_leaves_a_box_empty_when_no_teacher_of_the_class_is_free_and_keeps_existing_periods(): void
    {
        $this->teacher->update(['is_teacher' => true, 'status' => 'active']);
        ClassSubject::create(['school_id' => $this->admin->school_id, 'school_class_id' => $this->classA->school_class_id, 'subject_id' => $this->math->id, 'staff_id' => $this->teacher->id]);
        $this->entry($this->classB, $this->first, 1, $this->teacher);
        $kept = $this->entry($this->classA, $this->second, 3, null);

        $plan = $this->board()->call('suggestPlan')->get('plan');

        $this->assertSame(1, $plan['unfilled']);
        $this->assertCount(10, $plan['placements']);
        $this->assertArrayNotHasKey($this->second->id.'-3', $plan['placements']);
        $this->assertNull($kept->fresh()->staff_id);
    }

    public function test_applying_an_old_plan_skips_slots_taken_since(): void
    {
        $this->assignTeachers();
        $board = $this->board()->call('suggestPlan');
        $slot = collect($board->get('plan')['placements'])->firstWhere('staff_id', $this->teacher->id);
        $this->entry($this->classB, TimetablePeriod::find($slot['period']), $slot['day'], $this->teacher);

        $board->call('applyPlan')->assertDispatched('toast', fn ($name, $p) => str_contains($p['message'], '1 skipped'));

        $this->assertSame(1, TimetableEntry::where(['timetable_period_id' => $slot['period'], 'day' => $slot['day'], 'staff_id' => $this->teacher->id])->count());
    }

    public function test_switching_class_drops_the_plan_and_teachers_cannot_ask_for_one(): void
    {
        $this->assignTeachers();
        $this->board()->call('suggestPlan')->set('section_id', (string) $this->classB->id)->assertSet('plan', null);

        $teacher = User::create(['school_id' => $this->admin->school_id, 'name' => 'T', 'username' => 't-plan', 'role' => 'teacher', 'password' => Hash::make('x')]);
        Livewire::actingAs($teacher)->test(Board::class)->call('suggestPlan')->assertForbidden();
    }

    public function test_print_header_names_the_school_and_the_class_or_teacher(): void
    {
        $this->admin->school->update(['address' => '12 Mall Road']);
        $this->teacher->update(['is_teacher' => true, 'status' => 'active']);

        $this->board()
            ->assertSee('School A')->assertSee('12 Mall Road')->assertSee('03000000000')->assertSee('head@example.test')
            ->assertSee('Timetable for '.$this->classA->label)->assertDontSee('Timetable for Sara')
            ->assertSee($this->timetable->name)->assertDontSee('(Draft)')
            ->set('section_id', (string) $this->classB->id)->assertSee('Timetable for '.$this->classB->label)
            ->set('teacher_id', (string) $this->teacher->id)->set('view', 'teacher')
            ->assertSee('Timetable for Sara')->assertDontSee('Timetable for '.$this->classB->label);

        $draft = Timetable::create(['school_id' => $this->admin->school_id, 'name' => 'Winter']);
        $this->board()->set('timetable_id', (string) $draft->id)->assertSee('Winter (Draft)');
    }
}
