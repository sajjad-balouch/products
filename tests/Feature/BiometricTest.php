<?php

namespace Tests\Feature;

use App\Livewire\Settings;
use App\Livewire\Staff\AttendanceSheet;
use App\Livewire\Staff\CheckIn;
use App\Livewire\Staff\Devices;
use App\Models\AuditLog;
use App\Models\BiometricDevice;
use App\Models\BiometricPunch;
use App\Models\Plan;
use App\Models\School;
use App\Models\Staff;
use App\Models\StaffAttendance;
use App\Models\User;
use App\Services\SchoolProvisioner;
use App\Support\Navigation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class BiometricTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private School $school;

    private Staff $teacher;

    private Staff $guard;

    private BiometricDevice $device;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = app(SchoolProvisioner::class)->create(['school' => 'School A', 'type' => 'school', 'name' => 'Head', 'email' => 'head@example.test', 'phone' => '03000000000', 'password' => 'password']);
        $this->school = $this->admin->school;
        $this->school->update(['staff_starts_at' => '08:00', 'staff_late_minutes' => 10]);
        $id = $this->school->id;
        $this->teacher = Staff::create(['school_id' => $id, 'name' => 'Sara', 'biometric_id' => '7']);
        $this->guard = Staff::create(['school_id' => $id, 'name' => 'Akram', 'category' => 'security', 'duty_starts_at' => '20:00', 'duty_ends_at' => '08:00', 'biometric_id' => '9', 'is_teacher' => false]);
        $this->device = BiometricDevice::create(['school_id' => $id, 'name' => 'Main gate', 'serial_number' => 'ZK123', 'token_hash' => hash('sha256', 'secret-token')]);
    }

    private function push(string $body, string $serial = 'ZK123', string $ip = '127.0.0.1')
    {
        return $this->call('POST', '/iclock/cdata?SN='.$serial.'&table=ATTLOG&Stamp=1', [], [], [], ['REMOTE_ADDR' => $ip, 'CONTENT_TYPE' => 'text/plain'], $body);
    }

    private function day(Staff $staff, string $date): ?StaffAttendance
    {
        return StaffAttendance::where('staff_id', $staff->id)->whereDate('date', $date)->first();
    }

    public function test_basic_plan_has_staff_attendance_but_payroll_and_machines_are_separate(): void
    {
        $basic = Plan::where('code', 'basic')->first();
        $this->assertContains('staff', $basic->features);
        $this->assertNotContains('payroll', $basic->features);
        $this->assertContains('payroll', Plan::where('code', 'pro')->first()->features);

        $basic->applyTo($this->school);
        $admin = $this->admin->fresh();
        $routes = collect(Navigation::for($admin))->flatten(1)->pluck(1);
        $this->assertContains('staff.attendance', $routes);
        $this->assertContains('staff.attendance.register', $routes);
        $this->assertNotContains('staff.payroll', $routes);
        $this->assertNotContains('staff.devices', $routes);

        $this->actingAs($admin)->get(route('staff.attendance'))->assertOk();
        $this->get(route('staff.payroll'))->assertForbidden();
        $this->get(route('staff.devices'))->assertForbidden();
    }

    public function test_a_zkteco_machine_gets_its_settings_and_its_punches_become_attendance(): void
    {
        $this->get('/iclock/cdata?SN=ZK123&options=all')->assertOk()->assertSee('GET OPTION FROM: ZK123')->assertSee('Realtime=1');
        $this->get('/iclock/getrequest?SN=ZK123')->assertOk()->assertSee('OK');

        $body = "7\t2026-11-03 08:14:05\t0\t1\t0\t0\n7\t2026-11-03 08:14:40\t0\t1\t0\t0\n7\t2026-11-03 14:02:11\t1\t1\t0\t0\n";
        $this->push($body)->assertOk()->assertSee('OK: 3');

        $day = $this->day($this->teacher, '2026-11-03');
        $this->assertSame(['L', '08:14', '14:02', 'device'], [$day->status, $day->check_in, $day->check_out, $day->source]);
        $this->assertNotNull($this->device->fresh()->last_seen_at);

        // Machines resend; nothing is counted twice.
        $this->push($body)->assertSee('OK: 0');
        $this->assertSame(3, BiometricPunch::count());

        $this->push("7\t2026-11-04 07:58:00\t0\t1\n")->assertSee('OK: 1');
        $this->assertSame(['P', '07:58', null], [$this->day($this->teacher, '2026-11-04')->status, $this->day($this->teacher, '2026-11-04')->check_in, $this->day($this->teacher, '2026-11-04')->check_out]);
    }

    public function test_unknown_or_switched_off_machines_and_other_records_are_handled(): void
    {
        $this->push("7\t2026-11-03 08:00:00", 'NOPE')->assertNotFound();

        $this->call('POST', '/iclock/cdata?SN=ZK123&table=OPERLOG', [], [], [], [], "OPLOG 4\t0\t2026-11-03")->assertOk()->assertSee('OK');
        $this->assertSame(0, BiometricPunch::count());

        $this->device->update(['is_active' => false]);
        $this->push("7\t2026-11-03 08:00:00")->assertNotFound();

        $this->device->update(['is_active' => true]);
        $this->school->update(['features_off' => ['biometric']]);
        $this->push("7\t2026-11-03 08:00:00")->assertNotFound();
        $this->assertSame(0, BiometricPunch::count());
    }

    public function test_a_night_shift_ending_next_morning_belongs_to_the_evening_it_started(): void
    {
        $this->push("9\t2026-11-03 19:58:00\n9\t2026-11-04 08:03:00\n")->assertSee('OK: 2');

        $night = $this->day($this->guard, '2026-11-03');
        $this->assertSame(['P', '19:58', '08:03'], [$night->status, $night->check_in, $night->check_out]);
        $this->assertNull($this->day($this->guard, '2026-11-04'));
    }

    public function test_punches_for_an_unknown_id_wait_until_it_is_linked(): void
    {
        $this->push("15\t2026-11-03 08:01:00\n15\t2026-11-03 13:30:00\n");
        $this->assertSame(2, BiometricPunch::whereNull('staff_id')->count());

        $aya = Staff::create(['school_id' => $this->school->id, 'name' => 'Naseem', 'category' => 'support', 'is_teacher' => false]);
        Livewire::actingAs($this->admin)->test(Devices::class)->assertSee('Machine ids not linked')
            ->set('links.15', (string) $aya->id)->call('link', '15');

        $this->assertSame('15', $aya->fresh()->biometric_id);
        $this->assertSame(0, BiometricPunch::whereNull('staff_id')->count());
        $this->assertSame(['08:01', '13:30'], [$this->day($aya, '2026-11-03')->check_in, $this->day($aya, '2026-11-03')->check_out]);
    }

    public function test_other_software_can_send_punches_with_the_token(): void
    {
        $payload = ['punches' => [['user_id' => '7', 'time' => '2026-11-05 08:05:00'], ['user_id' => '7', 'time' => '2026-11-05 14:00:00']]];

        $this->postJson('/api/biometric/punches', $payload, ['X-Device-Token' => 'wrong'])->assertUnauthorized();
        $this->postJson('/api/biometric/punches', $payload, ['X-Device-Token' => 'secret-token'])->assertOk()->assertJson(['received' => 2, 'saved' => 2]);

        $this->assertSame(['P', '08:05', '14:00'], [$this->day($this->teacher, '2026-11-05')->status, $this->day($this->teacher, '2026-11-05')->check_in, $this->day($this->teacher, '2026-11-05')->check_out]);
    }

    public function test_a_log_file_from_a_machine_can_be_uploaded(): void
    {
        $file = UploadedFile::fake()->createWithContent('attlog.dat', "    7\t2026-11-06 08:20:00\t1\t0\t0\t0\n9,06/11/2026 19:55\nnot a punch line\n");

        Livewire::actingAs($this->admin)->test(Devices::class)->set('file', $file)->call('import')
            ->assertDispatched('toast', fn ($n, $p) => str_contains($p['message'], '2 punches read, 2 new'));

        $this->assertSame('L', $this->day($this->teacher, '2026-11-06')->status);
        $this->assertSame('19:55', $this->day($this->guard, '2026-11-06')->check_in);
    }

    public function test_adding_a_machine_shows_its_token_once_and_only_the_school_sees_it(): void
    {
        $page = Livewire::actingAs($this->admin)->test(Devices::class)->set('name', 'Staff room')->set('serial', 'ZK999')->call('add')->assertHasNoErrors();
        $token = $page->get('issued')['token'];
        $this->assertSame(hash('sha256', $token), BiometricDevice::where('serial_number', 'ZK999')->value('token_hash'));
        $page->set('name', 'Copy')->set('serial', 'ZK999')->call('add')->assertHasErrors('serial');

        $other = app(SchoolProvisioner::class)->create(['school' => 'School B', 'type' => 'school', 'name' => 'B', 'email' => 'b@example.test', 'phone' => '03000000001', 'password' => 'password']);
        Livewire::actingAs($other)->test(Devices::class)->assertDontSee('ZK123')->call('toggle', $this->device->id)->assertNotFound();
    }

    public function test_strict_mode_accepts_machines_only_from_the_school_network(): void
    {
        $this->school->update(['strict_attendance' => true, 'check_in_networks' => "39.45.12.0/24\n110.93.1.5"]);

        $this->push("7\t2026-11-03 08:00:00", 'ZK123', '8.8.8.8')->assertNotFound();
        $this->push("7\t2026-11-03 08:00:00", 'ZK123', '39.45.12.77')->assertOk()->assertSee('OK: 1');
    }

    public function test_strict_mode_lets_staff_check_in_only_on_the_school_network_or_grounds(): void
    {
        $login = User::create(['school_id' => $this->school->id, 'name' => 'Sara', 'username' => 'sara@example.test', 'role' => 'teacher', 'password' => Hash::make('password')]);
        $this->teacher->update(['user_id' => $login->id]);
        $this->school->update(['staff_self_check_in' => true, 'strict_attendance' => true]);

        $card = fn () => Livewire::actingAs($login->fresh())->test(CheckIn::class);

        $card()->call('checkIn')->assertDispatched('toast', fn ($n, $p) => str_contains($p['message'], 'has not set its network'));

        $this->school->update(['check_in_networks' => '39.45.12.0/24']);
        $card()->call('checkIn')->assertDispatched('toast', fn ($n, $p) => str_contains($p['message'], 'only check in at school'));
        $this->assertNull($this->day($this->teacher, today()->toDateString()));

        // The school grounds: 31.5204, 74.3587 with 150 metres around it.
        $this->school->update(['check_in_lat' => 31.5204, 'check_in_lng' => 74.3587, 'check_in_radius' => 150]);
        $card()->call('checkIn', 31.5300, 74.3700)->assertDispatched('toast', fn ($n, $p) => str_contains($p['message'], 'only check in at school'));
        $card()->call('checkIn', 31.5208, 74.3590);
        $this->assertSame('self', $this->day($this->teacher, today()->toDateString())->source);

        $this->school->update(['check_in_networks' => '127.0.0.1']);
        $card()->call('checkOut');
        $this->assertNotNull($this->day($this->teacher, today()->toDateString())->check_out);
    }

    public function test_strict_mode_locks_machine_times_on_the_sheet_but_not_otherwise(): void
    {
        $today = today()->toDateString();
        $this->push("7\t$today 08:02:00\n");

        $this->school->update(['strict_attendance' => true]);
        Livewire::actingAs($this->admin)->test(AttendanceSheet::class)->assertSee('Locked')
            ->set("rows.{$this->teacher->id}.status", 'A')->call('save');
        $this->assertSame(['P', '08:02'], [$this->day($this->teacher, $today)->status, $this->day($this->teacher, $today)->check_in]);

        $this->school->update(['strict_attendance' => false]);
        Livewire::actingAs($this->admin->fresh())->test(AttendanceSheet::class)->set("rows.{$this->teacher->id}.status", 'V')->call('save');
        $this->assertSame('V', $this->day($this->teacher, $today)->status);

        $this->assertTrue(AuditLog::withoutGlobalScopes()->where('subject_type', 'StaffAttendance')->where('action', 'updated')->exists());
    }

    public function test_settings_check_school_network_addresses(): void
    {
        $settings = Livewire::actingAs($this->admin)->test(Settings::class);

        $settings->set('form.check_in_networks', 'not-an-ip')->call('save')->assertHasErrors('form.check_in_networks');
        $settings->set('form.check_in_networks', '39.45.12.0/24, 110.93.1.5')->set('form.strict_attendance', true)->set('form.check_in_lat', '31.5204')->set('form.check_in_lng', '74.3587')->call('save')->assertHasNoErrors();
        $this->assertTrue($this->school->fresh()->strict_attendance);

        $settings->call('addThisNetwork')->assertSet('form.check_in_networks', "39.45.12.0/24\n110.93.1.5\n127.0.0.1");
    }
}
