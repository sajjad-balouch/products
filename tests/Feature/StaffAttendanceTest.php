<?php

namespace Tests\Feature;

use App\Livewire\Settings;
use App\Livewire\Staff\AttendanceRegister;
use App\Livewire\Staff\AttendanceSheet;
use App\Livewire\Staff\CheckIn;
use App\Livewire\Staff\PayrollRun;
use App\Models\Payroll;
use App\Models\Staff;
use App\Models\StaffAttendance;
use App\Models\User;
use App\Services\SchoolProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class StaffAttendanceTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Staff $teacher;

    private Staff $guard;

    private Staff $aya;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = app(SchoolProvisioner::class)->create(['school' => 'School A', 'type' => 'school', 'name' => 'Head', 'email' => 'head@example.test', 'phone' => '03000000000', 'password' => 'password']);
        $id = $this->admin->school_id;
        $this->teacher = Staff::create(['school_id' => $id, 'name' => 'Sara', 'category' => 'teaching', 'basic_salary' => 30000]);
        $this->guard = Staff::create(['school_id' => $id, 'name' => 'Akram', 'designation' => 'Night guard', 'category' => 'security', 'duty_starts_at' => '20:00', 'duty_ends_at' => '08:00', 'basic_salary' => 30000, 'is_teacher' => false]);
        $this->aya = Staff::create(['school_id' => $id, 'name' => 'Naseem', 'designation' => 'Aya', 'category' => 'support', 'basic_salary' => 24000, 'is_teacher' => false]);
    }

    private function sheet()
    {
        return Livewire::actingAs($this->admin)->test(AttendanceSheet::class);
    }

    public function test_every_kind_of_staff_is_on_the_sheet_and_can_be_filtered(): void
    {
        $this->sheet()->assertSee('Sara')->assertSee('Akram')->assertSee('Naseem')->assertSee('duty 20:00-08:00')
            ->set('category', 'security')->assertSee('Akram')->assertDontSee('Naseem')->assertDontSee('Sara');
    }

    public function test_arrival_time_after_duty_start_marks_late_using_each_persons_own_hours(): void
    {
        $sheet = $this->sheet()
            ->set("rows.{$this->teacher->id}.in", '08:25')
            ->set("rows.{$this->guard->id}.in", '19:55')
            ->set("rows.{$this->aya->id}.in", '08:05');

        $sheet->assertSet("rows.{$this->teacher->id}.status", 'L')
            ->assertSet("rows.{$this->guard->id}.status", 'P')
            ->assertSet("rows.{$this->aya->id}.status", 'P');

        $sheet->set("rows.{$this->teacher->id}.in", '07:55')->assertSet("rows.{$this->teacher->id}.status", 'P');
    }

    public function test_saving_keeps_times_and_drops_them_for_absent_or_leave(): void
    {
        $this->sheet()
            ->set("rows.{$this->teacher->id}.status", 'H')->set("rows.{$this->teacher->id}.in", '08:00')->set("rows.{$this->teacher->id}.out", '11:00')
            ->set("rows.{$this->aya->id}.status", 'A')->set("rows.{$this->aya->id}.in", '08:00')
            ->call('save')->assertHasNoErrors();

        $half = StaffAttendance::where('staff_id', $this->teacher->id)->first();
        $this->assertSame(['H', '08:00', '11:00'], [$half->status, $half->check_in, $half->check_out]);
        $absent = StaffAttendance::where('staff_id', $this->aya->id)->first();
        $this->assertSame(['A', null], [$absent->status, $absent->check_in]);
        $this->assertSame('P', StaffAttendance::where('staff_id', $this->guard->id)->value('status'));

        $this->sheet()->set("rows.{$this->aya->id}.status", 'X')->call('save')->assertHasErrors();
    }

    public function test_payroll_takes_off_absences_half_days_and_lates_by_the_schools_rule(): void
    {
        $this->admin->school->update(['lates_per_absent' => 3]);
        $month = now()->startOfMonth();
        $mark = fn ($day, $status) => StaffAttendance::create(['school_id' => $this->admin->school_id, 'staff_id' => $this->teacher->id, 'date' => $month->copy()->addDays($day), 'status' => $status]);
        $mark(0, 'A');
        $mark(1, 'H');
        foreach ([2, 3, 4, 5] as $d) {
            $mark($d, 'L');
        }
        $mark(6, 'V');

        Livewire::actingAs($this->admin)->test(PayrollRun::class)->call('generate');

        $slip = Payroll::where('staff_id', $this->teacher->id)->first();
        // 1 absent + half of 1 half day + 1 for every 3 lates (4 lates) = 2.5 days.
        $this->assertEquals(2.5, $slip->deducted_days);
        $this->assertSame([1, 1, 4], [$slip->absent_days, $slip->half_days, $slip->late_days]);
        $this->assertSame(2500, $slip->deduction);
        $this->assertSame(27500, $slip->net);

        $this->actingAs($this->admin)->get(route('print.payslip', $slip->id))->assertOk()->assertSee('2.5 days off pay')->assertSee('1 absent, 1 half day, 4 late');
    }

    public function test_lates_do_not_cut_pay_when_the_rule_is_off(): void
    {
        StaffAttendance::create(['school_id' => $this->admin->school_id, 'staff_id' => $this->aya->id, 'date' => now()->startOfMonth(), 'status' => 'L']);

        Livewire::actingAs($this->admin)->test(PayrollRun::class)->call('generate');

        $this->assertSame(0, Payroll::where('staff_id', $this->aya->id)->value('deduction'));
    }

    public function test_register_shows_the_month_with_totals(): void
    {
        $day = now()->startOfMonth();
        StaffAttendance::create(['school_id' => $this->admin->school_id, 'staff_id' => $this->guard->id, 'date' => $day, 'status' => 'A']);
        StaffAttendance::create(['school_id' => $this->admin->school_id, 'staff_id' => $this->guard->id, 'date' => $day->copy()->addDay(), 'status' => 'L', 'check_in' => '20:40']);

        Livewire::actingAs($this->admin)->test(AttendanceRegister::class)
            ->assertSee('Akram')->assertSee('Late, in 20:40')
            ->set('category', 'support')->assertDontSee('Akram')->assertSee('Naseem');

        $this->actingAs($this->admin)->get(route('staff.attendance.register'))->assertOk();
    }

    public function test_staff_can_check_themselves_in_only_when_the_school_allows_it(): void
    {
        $login = User::create(['school_id' => $this->admin->school_id, 'name' => 'Sara', 'username' => 'sara@example.test', 'role' => 'teacher', 'password' => Hash::make('password')]);
        $this->teacher->update(['user_id' => $login->id]);

        Livewire::actingAs($login)->test(CheckIn::class)->assertDontSee('Check in now')->call('checkIn')->assertForbidden();

        $this->admin->school->update(['staff_self_check_in' => true, 'staff_starts_at' => '08:00', 'staff_late_minutes' => 10]);
        $this->travelTo(today()->setTime(8, 25));

        $card = Livewire::actingAs($login->fresh())->test(CheckIn::class)->assertSee('Check in now')->call('checkIn');
        $record = StaffAttendance::where('staff_id', $this->teacher->id)->first();
        $this->assertSame(['L', '08:25', 'self'], [$record->status, $record->check_in, $record->source]);

        $card->call('checkIn')->assertDispatched('toast', fn ($n, $p) => str_contains($p['message'], 'already checked in'));
        $this->travelTo(today()->setTime(14, 5));
        $card->call('checkOut');
        $this->assertSame('14:05', $record->fresh()->check_out);

        $this->actingAs($login->fresh())->get(route('dashboard'))->assertSee('Checked in at 08:25 (late), checked out at 14:05');
    }

    public function test_settings_save_staff_hours_and_rules(): void
    {
        Livewire::actingAs($this->admin)->test(Settings::class)
            ->set('form.staff_starts_at', '07:30')->set('form.staff_late_minutes', '5')->set('form.lates_per_absent', '3')->set('form.staff_self_check_in', true)
            ->call('save')->assertHasNoErrors();

        $school = $this->admin->school->fresh();
        $this->assertSame(['07:30', 5, 3, true], [$school->staff_starts_at, $school->staff_late_minutes, $school->lates_per_absent, $school->staff_self_check_in]);
    }
}
