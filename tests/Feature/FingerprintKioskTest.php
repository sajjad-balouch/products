<?php

namespace Tests\Feature;

use App\Livewire\Staff\Devices;
use App\Livewire\Staff\Fingerprints;
use App\Models\BiometricDevice;
use App\Models\FingerprintTemplate;
use App\Models\School;
use App\Models\Staff;
use App\Models\StaffAttendance;
use App\Models\User;
use App\Services\SchoolProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class FingerprintKioskTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private School $school;

    private Staff $teacher;

    protected function setUp(): void
    {
        parent::setUp();

        config(['services.fingerprint.url' => 'http://matcher.test', 'services.fingerprint.key' => 'a-long-shared-secret-key']);
        $this->admin = app(SchoolProvisioner::class)->create(['school' => 'School A', 'type' => 'school', 'name' => 'Head', 'email' => 'head@example.test', 'phone' => '03000000000', 'password' => 'password']);
        $this->school = $this->admin->school;
        $this->school->update(['staff_starts_at' => '08:00', 'staff_late_minutes' => 10]);
        $this->teacher = Staff::create(['school_id' => $this->school->id, 'name' => 'Sara']);
    }

    /** Adds a kiosk through the page and returns the one-time link. */
    private function kioskLink(): string
    {
        return Livewire::actingAs($this->admin)->test(Devices::class)->set('kioskName', 'Staff room')->call('addKiosk')->get('issued')['link'];
    }

    private function enrol(): void
    {
        FingerprintTemplate::create(['school_id' => $this->school->id, 'staff_id' => $this->teacher->id, 'finger' => 'right-index', 'template' => 'VEVNUExBVEU=']);
    }

    private function punch(array $cookies, string $ip = '127.0.0.1')
    {
        return $this->withCredentials()->withCookies($cookies)->withServerVariables(['REMOTE_ADDR' => $ip])
            ->postJson(route('kiosk.punch'), ['image' => base64_encode('png')], ['X-Kiosk' => '1']);
    }

    /** Opens the kiosk link like the kiosk computer would, and returns the cookie it got. */
    private function openKiosk(): array
    {
        $link = $this->kioskLink();
        $this->get($link)->assertRedirect(route('kiosk'))->assertCookie('attendance_kiosk');

        // The cookie the link set, before Laravel encrypts it. withCookies encrypts it again on the way in.
        parse_str((string) parse_url($link, PHP_URL_QUERY), $query);
        $device = BiometricDevice::where('type', 'kiosk')->latest('id')->first();

        return ['attendance_kiosk' => $device->id.'|'.hash('sha256', $query['key'])];
    }

    public function test_enrolling_a_finger_saves_the_template_from_the_matcher(): void
    {
        Http::fake(['matcher.test/extract' => Http::response(['template' => 'VEVNUExBVEU='])]);

        Livewire::actingAs($this->admin)->test(Fingerprints::class)
            ->set('staffId', (string) $this->teacher->id)->set('finger', 'left-thumb')
            ->call('enrol', base64_encode('png'))->call('enrol', base64_encode('png'))->assertSet('presses', 2);

        $this->assertSame(2, FingerprintTemplate::where(['staff_id' => $this->teacher->id, 'finger' => 'left-thumb'])->count());
        Http::assertSent(fn (HttpRequest $r) => $r->hasHeader('X-Matcher-Key', 'a-long-shared-secret-key') && $r['dpi'] == 500);

        $this->actingAs($this->admin)->get(route('staff.fingerprints'))->assertOk()->assertSee('Left thumb (2)');
    }

    public function test_a_matcher_problem_is_told_plainly_and_nothing_is_saved(): void
    {
        Http::fake(['matcher.test/extract' => Http::response(['error' => 'x'], 422)]);
        Livewire::actingAs($this->admin)->test(Fingerprints::class)->set('staffId', (string) $this->teacher->id)
            ->call('enrol', base64_encode('png'))->assertDispatched('toast', fn ($n, $p) => str_contains($p['message'], 'not clear'));

        config(['services.fingerprint.key' => null]);
        Livewire::actingAs($this->admin)->test(Fingerprints::class)->set('staffId', (string) $this->teacher->id)
            ->call('enrol', base64_encode('png'))->assertDispatched('toast', fn ($n, $p) => str_contains($p['message'], 'not set up'));

        $this->assertSame(0, FingerprintTemplate::count());
    }

    public function test_only_this_schools_active_staff_can_be_enrolled(): void
    {
        $other = app(SchoolProvisioner::class)->create(['school' => 'School B', 'type' => 'school', 'name' => 'B', 'email' => 'b@example.test', 'phone' => '03000000001', 'password' => 'password']);
        $theirs = Staff::create(['school_id' => $other->school_id, 'name' => 'Other']);

        Livewire::actingAs($this->admin)->test(Fingerprints::class)->set('staffId', (string) $theirs->id)->call('enrol', base64_encode('png'))->assertHasErrors('staffId');
        $this->assertSame(0, FingerprintTemplate::count());
    }

    public function test_the_kiosk_link_sets_up_the_computer_and_a_new_link_stops_the_old_one(): void
    {
        $cookies = $this->openKiosk();
        $this->withCookies($cookies)->get(route('kiosk'))->assertOk()->assertSee('School A')->assertSee('Staff room')->assertSee('websdk.client.min.js', false);

        $this->withCookies(['attendance_kiosk' => '1|not-the-key'])->get(route('kiosk'))->assertForbidden();
        $this->get(route('kiosk.open', ['KIOSK-NOPE', 'key' => 'x']))->assertForbidden();

        Livewire::actingAs($this->admin)->test(Devices::class)->call('newToken', BiometricDevice::where('type', 'kiosk')->first()->id);
        $this->withCookies($cookies)->get(route('kiosk'))->assertForbidden();
    }

    public function test_a_recognised_finger_marks_arrival_then_leaving(): void
    {
        $this->enrol();
        Http::fake(['matcher.test/identify' => Http::response(['id' => (string) $this->teacher->id, 'score' => 88.5])]);
        $cookies = $this->openKiosk();

        $this->travelTo(today()->setTime(8, 4));
        $this->punch($cookies)->assertOk()->assertJson(['name' => 'Sara', 'action' => 'Welcome', 'detail' => 'Arrived at 08:04']);
        $this->punch($cookies)->assertOk()->assertJson(['action' => 'Already marked']);

        $this->travelTo(today()->setTime(14, 10));
        $this->punch($cookies)->assertOk()->assertJson(['action' => 'Goodbye', 'detail' => 'Left at 14:10']);

        $day = StaffAttendance::where('staff_id', $this->teacher->id)->first();
        $this->assertSame(['P', '08:04', '14:10', 'device'], [$day->status, $day->check_in, $day->check_out, $day->source]);
        Http::assertSent(fn (HttpRequest $r) => str_ends_with($r->url(), '/identify') && $r['candidates'][0]['id'] === (string) $this->teacher->id && $r['threshold'] == 40);
    }

    public function test_an_unknown_finger_marks_nothing(): void
    {
        $this->enrol();
        Http::fake(['matcher.test/identify' => Http::response(['id' => null, 'score' => 3.2])]);

        $this->punch($this->openKiosk())->assertStatus(422)->assertJson(['message' => 'Not recognised. Press the same finger again, or see the office.']);
        $this->assertSame(0, StaffAttendance::count());
    }

    public function test_the_kiosk_only_compares_with_this_schools_fingers(): void
    {
        $other = app(SchoolProvisioner::class)->create(['school' => 'School B', 'type' => 'school', 'name' => 'B', 'email' => 'b@example.test', 'phone' => '03000000001', 'password' => 'password']);
        $theirs = Staff::create(['school_id' => $other->school_id, 'name' => 'Other']);
        FingerprintTemplate::create(['school_id' => $other->school_id, 'staff_id' => $theirs->id, 'finger' => 'right-index', 'template' => 'T1RIRVI=']);
        $this->enrol();
        Http::fake(['matcher.test/identify' => Http::response(['id' => (string) $theirs->id, 'score' => 90])]);

        $this->punch($this->openKiosk())->assertStatus(422);
        Http::assertSent(fn (HttpRequest $r) => count($r['candidates']) === 1 && $r['candidates'][0]['id'] === (string) $this->teacher->id);
    }

    public function test_the_kiosk_needs_its_header_its_school_network_and_the_feature(): void
    {
        $this->enrol();
        Http::fake(['matcher.test/identify' => Http::response(['id' => (string) $this->teacher->id, 'score' => 90])]);
        $cookies = $this->openKiosk();

        $this->withCredentials()->withCookies($cookies)->postJson(route('kiosk.punch'), ['image' => 'x'])->assertStatus(400);

        $this->school->update(['strict_attendance' => true, 'check_in_networks' => '39.45.12.0/24']);
        $this->punch($cookies, '8.8.8.8')->assertForbidden();
        $this->punch($cookies, '39.45.12.9')->assertOk();

        $this->school->update(['features_off' => ['biometric']]);
        $this->punch($cookies, '39.45.12.9')->assertForbidden();
    }

    public function test_a_kiosk_cannot_be_used_as_a_push_machine(): void
    {
        $this->kioskLink();
        $serial = BiometricDevice::where('type', 'kiosk')->value('serial_number');

        $this->call('POST', '/iclock/cdata?SN='.$serial.'&table=ATTLOG', [], [], [], [], "1\t2026-11-03 08:00:00")->assertNotFound();
    }

    public function test_in_local_mode_the_browser_sends_templates_and_never_pictures(): void
    {
        config(['services.fingerprint.mode' => 'local', 'services.fingerprint.key' => null]);
        Http::fake();
        $template = base64_encode(str_repeat('t', 300));

        $fresh = fn () => Livewire::actingAs($this->admin)->test(Fingerprints::class)->set('staffId', (string) $this->teacher->id);

        $page = $fresh()->assertDontSee('not set up')->assertSee('Download helper')->assertSee('Download fingerprint helper');
        $page->call('enrolTemplate', $template)->assertSet('presses', 1);
        $this->assertSame([['id' => (string) $this->teacher->id, 'template' => $template]], $page->call('candidates')->effects['returns'][0]);

        $fresh()->call('enrolTemplate', 'not base64 at all!!!')->assertStatus(422);
        $fresh()->call('enrol', base64_encode('png'))->assertStatus(422);
        $this->assertSame(1, FingerprintTemplate::count());
        Http::assertNothingSent();

        config(['services.fingerprint.mode' => 'server', 'services.fingerprint.key' => 'a-long-shared-secret-key']);
        Livewire::actingAs($this->admin)->test(Fingerprints::class)->set('staffId', (string) $this->teacher->id)->call('enrolTemplate', $template)->assertStatus(422);
    }

    public function test_in_local_mode_the_kiosk_gets_its_schools_fingers_and_reports_who_matched(): void
    {
        config(['services.fingerprint.mode' => 'local', 'services.fingerprint.key' => null]);
        $this->enrol();
        $other = app(SchoolProvisioner::class)->create(['school' => 'School B', 'type' => 'school', 'name' => 'B', 'email' => 'b@example.test', 'phone' => '03000000001', 'password' => 'password']);
        $theirs = Staff::create(['school_id' => $other->school_id, 'name' => 'Other']);
        FingerprintTemplate::create(['school_id' => $other->school_id, 'staff_id' => $theirs->id, 'finger' => 'right-index', 'template' => 'T1RIRVI=']);
        $noFinger = Staff::create(['school_id' => $this->school->id, 'name' => 'No finger']);
        $cookies = $this->openKiosk();

        $this->withCredentials()->withCookies($cookies)->getJson(route('kiosk.candidates'), ['X-Kiosk' => '1'])
            ->assertOk()->assertExactJson([['id' => (string) $this->teacher->id, 'template' => 'VEVNUExBVEU=']]);
        $this->withCredentials()->withCookies($cookies)->get(route('kiosk'))->assertOk()->assertSee('fingerHelper', false);

        $send = fn ($body) => $this->withCredentials()->withCookies($cookies)->postJson(route('kiosk.punch'), $body, ['X-Kiosk' => '1']);
        $send(['staff_id' => $theirs->id, 'score' => 90])->assertStatus(422);
        $send(['staff_id' => $noFinger->id, 'score' => 90])->assertStatus(422);
        $send(['staff_id' => $this->teacher->id, 'score' => 12])->assertStatus(422);
        $this->assertSame(0, StaffAttendance::count());

        $send(['staff_id' => $this->teacher->id, 'score' => 75])->assertOk()->assertJson(['name' => 'Sara', 'action' => 'Welcome']);
        $this->assertSame(1, StaffAttendance::where('staff_id', $this->teacher->id)->count());
    }

    public function test_server_mode_does_not_hand_out_fingers(): void
    {
        $this->enrol();
        $this->withCredentials()->withCookies($this->openKiosk())->getJson(route('kiosk.candidates'), ['X-Kiosk' => '1'])->assertStatus(400);
    }

    public function test_the_helper_download_is_set_up_for_this_website_without_the_server_key(): void
    {
        $exe = tempnam(sys_get_temp_dir(), 'exe');
        file_put_contents($exe, 'MZ fake program');
        config(['services.fingerprint.helper_exe' => $exe, 'app.url' => 'https://myschool.test']);

        $response = $this->actingAs($this->admin)->get(route('staff.fingerprint-helper'))->assertOk()->assertDownload('FingerprintHelper.zip');
        $zipPath = $response->baseResponse->getFile()->getPathname();
        $zip = new \ZipArchive;
        $zip->open($zipPath);

        foreach (['FingerprintHelper.exe', 'fingerprint-helper.json', 'install-startup.bat', 'uninstall-startup.bat', 'README.txt'] as $name) {
            $this->assertNotFalse($zip->locateName('FingerprintHelper/'.$name), $name);
        }
        $settings = json_decode($zip->getFromName('FingerprintHelper/fingerprint-helper.json'), true);
        $this->assertContains('https://myschool.test', $settings['AllowedOrigins']);
        $this->assertArrayNotHasKey('MatcherKey', $settings);
        $this->assertStringContainsString("\r\n", $zip->getFromName('FingerprintHelper/install-startup.bat'));
        $zip->close();

        config(['services.fingerprint.helper_exe' => '/no/such/file.exe']);
        $this->get(route('staff.fingerprint-helper'))->assertNotFound();

        $this->school->update(['features_off' => ['biometric']]);
        $this->actingAs($this->admin->fresh())->get(route('staff.fingerprint-helper'))->assertForbidden();
    }

    public function test_setup_command_writes_the_env_for_each_kind_of_hosting(): void
    {
        $env = tempnam(sys_get_temp_dir(), 'env');
        file_put_contents($env, "APP_NAME=Test\nFINGERPRINT_MATCHER_KEY=your-long-random-secret\n");
        config(['services.fingerprint.helper_exe' => sys_get_temp_dir().'/no-helper-here/FingerprintHelper.exe']);

        $this->artisan('fingerprint:setup', ['--mode' => 'server', '--file' => $env])->assertSuccessful();
        $written = file_get_contents($env);
        $this->assertStringContainsString('FINGERPRINT_MATCHER_MODE=server', $written);
        $this->assertStringNotContainsString('your-long-random-secret', $written);
        preg_match('/^FINGERPRINT_MATCHER_KEY=(.+)$/m', $written, $m);
        $this->assertSame(48, strlen(trim($m[1])));

        // Running it again keeps a real key.
        $this->artisan('fingerprint:setup', ['--mode' => 'server', '--file' => $env])->assertSuccessful();
        $this->assertStringContainsString('FINGERPRINT_MATCHER_KEY='.trim($m[1]), file_get_contents($env));

        $this->artisan('fingerprint:setup', ['--mode' => 'local', '--file' => $env])->assertSuccessful();
        $this->assertStringContainsString('FINGERPRINT_MATCHER_MODE=local', file_get_contents($env));
        $this->artisan('fingerprint:setup', ['--mode' => 'cloud', '--file' => $env])->assertFailed();
    }

    public function test_the_matcher_being_down_is_shown_on_the_kiosk(): void
    {
        $this->enrol();
        Http::fake(fn () => throw new ConnectionException('down'));

        $this->punch($this->openKiosk())->assertStatus(503)->assertJson(['message' => 'The fingerprint matcher is not running. Start it, then try again.']);
    }
}
