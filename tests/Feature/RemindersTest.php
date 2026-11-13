<?php

namespace Tests\Feature;

use App\Livewire\Messages;
use App\Livewire\Platform\Messaging;
use App\Livewire\ReminderSettings;
use App\Models\Biz\Customer;
use App\Models\FeeHead;
use App\Models\FeeStructure;
use App\Models\FeeVoucher;
use App\Models\GatewayDevice;
use App\Models\Guardian;
use App\Models\Message;
use App\Models\MessagingSetting;
use App\Models\Plan;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use App\Services\Biz\Installments;
use App\Services\FeeService;
use App\Services\Reminders;
use App\Services\SchoolProvisioner;
use App\Services\Sms;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class RemindersTest extends TestCase
{
    use RefreshDatabase;

    /** A school or business whose plan includes automatic SMS / WhatsApp. */
    private function account(string $type, string $name, bool $withSms = true): User
    {
        $user = app(SchoolProvisioner::class)->create(['school' => $name, 'type' => $type, 'name' => 'Owner', 'email' => strtolower(str_replace(' ', '', $name)).'@example.test', 'phone' => '03000000001', 'password' => 'password']);
        if ($withSms) {
            $user->school->update(['features_off' => []]);
        }

        return $user->fresh();
    }

    private function messaging(string $driver, array $config = [], int $limit = 500): void
    {
        MessagingSetting::current()->update(['driver' => $driver, 'config' => $config, 'school_daily_limit' => $limit]);
        Sms::flush();
    }

    private function useHttpSms(array $extra = []): void
    {
        $this->messaging('http', $extra + ['url' => 'https://sms.example.test/send?user=demo&to={to}&text={message}', 'method' => 'GET', 'number_format' => '92']);
    }

    /** A school with one family, its fee due on the 10th of September 2026. */
    private function schoolWithFeeDue(bool $withSms = true): School
    {
        $school = $this->account('school', 'Green School', $withSms)->school;
        $class = SchoolClass::create(['school_id' => $school->id, 'name' => 'One', 'sort' => 1]);
        $head = FeeHead::where('school_id', $school->id)->where('name', 'Tuition Fee')->firstOrFail();
        FeeStructure::create(['school_id' => $school->id, 'school_class_id' => $class->id, 'fee_head_id' => $head->id, 'amount' => 2500]);
        $section = Section::create(['school_id' => $school->id, 'school_class_id' => $class->id, 'name' => 'A']);
        $guardian = Guardian::create(['school_id' => $school->id, 'phone' => '03001112222', 'name' => 'Rashid Khan']);
        Student::create(['school_id' => $school->id, 'guardian_id' => $guardian->id, 'section_id' => $section->id, 'admission_no' => 'G1', 'name' => 'Ali Rashid']);
        app(FeeService::class)->generateMonthly($school, Carbon::parse('2026-09-01'), Carbon::parse('2026-09-10'));
        $school->update(['reminder_settings' => ['enabled' => true]]);

        return $school->fresh();
    }

    private function owner(): User
    {
        return User::create(['name' => 'Platform owner', 'username' => 'owner@example.test', 'role' => 'super_admin', 'password' => Hash::make('password')]);
    }

    public function test_reminder_days_follow_the_settings(): void
    {
        $s = Reminders::DEFAULTS; // 3 and 1 days before, on the day, every 3 days late up to 3 times
        $due = Carbon::parse('2026-09-10');
        $at = fn (string $day) => Reminders::stage($s, $due, Carbon::parse($day));

        $this->assertNull($at('2026-09-05'));
        $this->assertSame('before-3', $at('2026-09-07'));
        $this->assertNull($at('2026-09-08'));
        $this->assertSame('before-1', $at('2026-09-09'));
        $this->assertSame('due', $at('2026-09-10'));
        $this->assertNull($at('2026-09-11'));
        $this->assertSame('late-3', $at('2026-09-13'));
        $this->assertSame('late-9', $at('2026-09-19'));
        $this->assertNull($at('2026-09-22'), 'No more than 3 late reminders');

        $this->assertNull(Reminders::stage(['days_before' => [], 'on_due' => false, 'late_every' => 0, 'late_times' => 3], $due, Carbon::parse('2026-09-10')));
    }

    public function test_fee_reminder_is_sent_once_through_an_sms_company_api(): void
    {
        $this->useHttpSms(['success_contains' => 'OK']);
        Http::fake(['sms.example.test/*' => Http::response('OK: queued 1')]);
        $school = $this->schoolWithFeeDue();

        $result = app(Reminders::class)->run($school, Carbon::parse('2026-09-07'));
        $this->assertSame(['sent' => 1, 'failed' => 0, 'waiting' => 0, 'skipped' => 0], $result);

        Http::assertSent(function (Request $r) {
            parse_str(parse_url($r->url(), PHP_URL_QUERY), $q);

            return $q['user'] === 'demo' && $q['to'] === '923001112222'
                && str_contains($q['text'], 'Ali') && str_contains($q['text'], 'Rs 2,500') && str_contains($q['text'], '10 Sep') && str_contains($q['text'], 'Green School');
        });
        $message = Message::withoutGlobalScopes()->sole();
        $this->assertSame(['sms', 'sent', 'fee'], [$message->channel, $message->status, $message->category]);

        // The same day again sends nothing; the next reminder day sends the next one.
        $this->assertSame(1, app(Reminders::class)->run($school, Carbon::parse('2026-09-07'))['skipped']);
        $this->assertSame(1, app(Reminders::class)->run($school, Carbon::parse('2026-09-09'))['sent']);
        Http::assertSentCount(2);
    }

    public function test_paid_fees_get_no_reminder_and_late_ones_get_the_late_message(): void
    {
        $school = $this->schoolWithFeeDue();

        $late = app(Reminders::class)->due($school, Carbon::parse('2026-09-13'))->sole();
        $this->assertStringContainsString('3 din se baqi', $late['body']);

        $voucher = FeeVoucher::withoutGlobalScopes()->sole();
        app(FeeService::class)->recordPayment($voucher, $voucher->balance);
        $this->assertCount(0, app(Reminders::class)->due($school, Carbon::parse('2026-09-13')));
    }

    public function test_a_failed_message_is_kept_with_the_reason(): void
    {
        $this->useHttpSms(['success_contains' => 'OK']);
        Http::fake(['sms.example.test/*' => Http::response('ERROR: low balance')]);
        $school = $this->schoolWithFeeDue();

        $this->assertSame(1, app(Reminders::class)->run($school, Carbon::parse('2026-09-10'))['failed']);
        $message = Message::withoutGlobalScopes()->sole();
        $this->assertSame('failed', $message->status);
        $this->assertStringContainsString('low balance', $message->error);
    }

    public function test_without_the_plan_feature_reminders_wait_in_the_whatsapp_outbox(): void
    {
        $this->useHttpSms();
        Http::fake();
        $school = $this->schoolWithFeeDue(withSms: false);

        $this->assertSame(1, app(Reminders::class)->run($school, Carbon::parse('2026-09-10'))['waiting']);
        $this->assertSame(['whatsapp', 'queued'], [Message::withoutGlobalScopes()->sole()->channel, Message::withoutGlobalScopes()->sole()->status]);
        Http::assertNothingSent();
    }

    public function test_new_schools_do_not_get_sms_until_a_plan_includes_it(): void
    {
        $school = $this->account('school', 'New School', withSms: false)->school;
        $this->assertFalse($school->hasFeature('sms'));

        $plan = Plan::create(['code' => 'gold', 'name' => 'Gold', 'product' => 'school', 'monthly_price' => 5000, 'features' => array_keys(School::featuresFor('school'))]);
        $plan->applyTo($school);
        $this->assertTrue($school->fresh()->hasFeature('sms'));
        $this->assertArrayHasKey('sms', School::featuresFor('business'));
    }

    public function test_switched_off_schools_get_nothing(): void
    {
        $this->messaging('log');
        $school = $this->schoolWithFeeDue();
        $school->update(['reminder_settings' => ['enabled' => false]]);

        $this->assertSame(0, app(Reminders::class)->runAll(Carbon::parse('2026-09-10'))['schools']);
        $this->assertSame(0, Message::withoutGlobalScopes()->count());
    }

    public function test_the_daily_limit_stops_more_messages(): void
    {
        $this->messaging('log', [], limit: 1);
        $school = $this->schoolWithFeeDue();
        Message::withoutGlobalScopes()->create(['school_id' => $school->id, 'phone' => '03001234567', 'channel' => 'sms', 'body' => 'x', 'status' => 'sent']);

        $this->assertSame(1, app(Reminders::class)->run($school, Carbon::parse('2026-09-10'))['failed']);
        $this->assertStringContainsString('limit', Message::withoutGlobalScopes()->where('status', 'failed')->sole()->error);
    }

    public function test_installment_reminders_for_a_business(): void
    {
        $this->messaging('log');
        $owner = $this->account('business', 'Talib Motors');
        $shop = $owner->school;
        $this->actingAs($owner);
        $customer = Customer::create(['code' => 'C-1', 'name' => 'Aslam', 'phone' => '03214445555']);
        $contract = app(Installments::class)->sell($shop, $customer, [
            'item' => 'Bike', 'sale_date' => '2026-08-01', 'cash_price' => 100000, 'cost_price' => 85000, 'markup_percent' => 20,
            'down_payment' => 20000, 'installments' => 6, 'frequency' => 'monthly', 'first_due_on' => '2026-09-01',
            'late_fee' => 0, 'grace_days' => 0, 'rounding' => 100,
        ]);
        auth()->logout();
        $shop->update(['reminder_settings' => ['enabled' => true]]);

        $before = app(Reminders::class)->due($shop->fresh(), Carbon::parse('2026-08-29'))->sole();
        $this->assertSame('inst:'.$contract->id.':'.$contract->schedule->sortBy('due_on')->first()->id.':before-3', $before['key']);
        $this->assertStringContainsString('Rs 16,000', $before['body']);
        $this->assertStringContainsString($contract->number, $before['body']);

        $this->assertSame(1, app(Reminders::class)->runAll(Carbon::parse('2026-09-04'))['sent']); // 3 days late
        $this->assertSame($customer->id, Message::withoutGlobalScopes()->sole()->customer_id);
        $this->assertStringContainsString('late', Message::withoutGlobalScopes()->sole()->body);

        $this->actingAs($owner)->get(route('biz.settings'))->assertOk()->assertSee('Automatic reminders')->assertSee('{plan}');
        $this->get(route('messages.index'))->assertOk();
        Livewire::test(Messages::class)->set('tab', 'sent')->assertSee('Aslam')->assertSee('SMS');
    }

    public function test_your_own_gateway_phone_picks_up_and_reports(): void
    {
        $this->messaging('gateway');
        $school = $this->schoolWithFeeDue();
        [$phone, $token] = GatewayDevice::register('Office phone', '03009990000', 300);

        $this->assertSame(1, app(Reminders::class)->run($school, Carbon::parse('2026-09-10'))['sent']);
        $message = Message::withoutGlobalScopes()->sole();
        $this->assertSame(['sms', 'queued'], [$message->channel, $message->status]);

        $this->postJson('/sms-gateway/pull', ['battery' => 80])->assertUnauthorized();
        $pull = $this->withToken($token)->postJson('/sms-gateway/pull', ['battery' => 80, 'max' => 5])->assertOk()->json();
        $this->assertSame([['id' => $message->id, 'to' => '+923001112222', 'body' => $message->body]], $pull['messages']);
        $this->assertSame('sending', $message->fresh()->status);
        $this->assertSame([], $this->withToken($token)->postJson('/sms-gateway/pull')->json('messages'), 'A message goes to one phone only');

        // Failed once: back in line; then sent.
        $this->withToken($token)->postJson('/sms-gateway/report', ['results' => [['id' => $message->id, 'status' => 'failed', 'error' => 'No signal']]])->assertOk();
        $this->assertSame('queued', $message->fresh()->status);
        $this->withToken($token)->postJson('/sms-gateway/pull')->assertOk();
        $this->withToken($token)->postJson('/sms-gateway/report', ['results' => [['id' => $message->id, 'status' => 'sent']]])->assertJson(['accepted' => 1]);
        $this->assertSame('sent', $message->fresh()->status);
        $this->assertSame(80, $phone->fresh()->last_status['battery']);
        $this->assertTrue($phone->fresh()->isOnline());
    }

    public function test_school_owner_saves_settings_and_sends_a_test(): void
    {
        $this->useHttpSms();
        Http::fake(['*' => Http::response('sent')]);
        $owner = $this->account('school', 'Blue School');
        $this->actingAs($owner);

        Livewire::test(ReminderSettings::class)
            ->set('s.enabled', true)->set('s.days_before', ['7', '1'])->set('s.late_every', '2')->set('s.late_times', '4')
            ->set('s.fee', 'Fee {amount} by {due_date}. {school}')
            ->call('save')->assertHasNoErrors()
            ->set('testPhone', '0300 9998888')->call('sendTest');

        $saved = $owner->school->fresh()->reminder_settings;
        $this->assertSame([true, [7, 1], 2, 4], [$saved['enabled'], $saved['days_before'], $saved['late_every'], $saved['late_times']]);
        $this->assertSame('Fee {amount} by {due_date}. {school}', $saved['fee']);
        Http::assertSent(fn (Request $r) => str_contains($r->url(), 'to=923009998888') && str_contains(urldecode($r->url()), 'Blue School'));

        $this->get(route('settings'))->assertOk()->assertSee('Automatic reminders');
    }

    public function test_failed_messages_can_be_retried_and_the_outbox_sent_all_at_once(): void
    {
        $this->useHttpSms();
        Http::fake(['*' => Http::response('done')]);
        $owner = $this->account('school', 'Red School');
        $failed = Message::withoutGlobalScopes()->create(['school_id' => $owner->school_id, 'phone' => '03001234567', 'category' => 'fee', 'channel' => 'sms', 'body' => 'Hi', 'status' => 'failed', 'error' => 'timeout']);
        $waiting = Message::withoutGlobalScopes()->create(['school_id' => $owner->school_id, 'phone' => '03007654321', 'category' => 'absent', 'body' => 'Absent today', 'status' => 'queued']);
        $this->actingAs($owner);

        Livewire::test(Messages::class)->set('tab', 'failed')->assertSee('timeout')->call('retry', $failed->id)
            ->set('tab', 'queued')->assertSee('Send all by SMS')->call('sendAllAutomatically');
        $this->assertSame(['sent', 'sent'], [$failed->fresh()->status, $waiting->fresh()->status]);
        $this->assertSame('sms', $waiting->fresh()->channel);
    }

    public function test_platform_owner_sets_up_messaging(): void
    {
        $owner = $this->owner();
        $this->actingAs($owner)->get(route('platform.messaging'))->assertOk()->assertSee('How messages go out');
        $this->actingAs($this->account('school', 'Some School'))->get(route('platform.messaging'))->assertForbidden();

        $this->actingAs($owner);
        Livewire::test(Messaging::class)
            ->call('choose', 'http')->call('applyPreset', 'ultramsg')
            ->set('config.url', 'https://api.ultramsg.com/inst1/messages/chat')->set('token', 'secret-token')->set('dailyLimit', '50')
            ->call('save')->assertHasNoErrors()
            ->assertSet('hasToken', true)->assertSet('token', '');

        $s = MessagingSetting::current();
        $this->assertSame(['http', 'secret-token', 'whatsapp', 50], [$s->driver, $s->get('token'), $s->get('kind'), $s->school_daily_limit]);
        $this->assertStringNotContainsString('secret-token', (string) \DB::table('messaging_settings')->value('config'), 'The token is stored encrypted');

        Http::fake(['*' => Http::response(['sent' => 'true'])]);
        Livewire::test(Messaging::class)->set('testPhone', '03001234567')->call('sendTest')->assertSet('testResult.ok', true);
        Http::assertSent(fn (Request $r) => $r['token'] === 'secret-token' && $r['to'] === '+923001234567' && $r->isForm());

        $page = Livewire::test(Messaging::class)->call('choose', 'gateway')->call('save')->set('deviceName', 'Office phone')->call('addDevice');
        $this->assertStringStartsWith('gw_', $page->get('newDeviceToken'));
        $this->assertNotNull(GatewayDevice::findByToken($page->get('newDeviceToken')));
    }

    public function test_json_post_with_a_header(): void
    {
        $this->messaging('http', ['url' => 'https://sms.example.test/v1/send', 'method' => 'POST', 'body_type' => 'json', 'payload' => '{"number":"{to}","sms":"{message}","from":"{sender}"}', 'headers' => 'Authorization: Bearer {token}', 'number_format' => '0', 'token' => 'abc', 'sender' => 'SCHOOL']);
        Http::fake(['*' => Http::response(['status' => 'ok'])]);

        $this->assertTrue(app(Sms::class)->send('+92 300 1234567', 'Hello')['ok']);
        Http::assertSent(fn (Request $r) => $r->method() === 'POST' && $r['number'] === '03001234567' && $r['sms'] === 'Hello' && $r['from'] === 'SCHOOL' && $r->hasHeader('Authorization', 'Bearer abc'));
    }
}
