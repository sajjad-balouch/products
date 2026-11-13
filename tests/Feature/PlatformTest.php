<?php

namespace Tests\Feature;

use App\Livewire\Auth\Login;
use App\Livewire\Platform\Plans;
use App\Livewire\Platform\Schools;
use App\Livewire\Platform\SchoolShow;
use App\Models\AuditLog;
use App\Models\Plan;
use App\Models\School;
use App\Models\SubscriptionEntry;
use App\Models\User;
use App\Services\SchoolProvisioner;
use App\Services\SiteBuilder;
use App\Support\Navigation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class PlatformTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private User $admin;

    private School $school;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = User::create(['name' => 'Owner', 'username' => 'owner@example.test', 'role' => 'super_admin', 'password' => Hash::make('password')]);
        $this->admin = app(SchoolProvisioner::class)->create(['school' => 'School A', 'type' => 'school', 'name' => 'Head', 'email' => 'head@example.test', 'phone' => '03000000000', 'password' => 'password']);
        $this->school = $this->admin->school;
    }

    private function page(string $tab = 'overview')
    {
        return Livewire::actingAs($this->owner)->test(SchoolShow::class, ['school' => $this->school])->set('tab', $tab);
    }

    public function test_every_owner_page_and_tab_loads_and_others_are_kept_out(): void
    {
        app(SiteBuilder::class)->forSchool($this->school);
        $this->actingAs($this->owner);

        foreach (Navigation::for($this->owner) as $links) {
            foreach ($links as [, $route]) {
                $this->get(route($route))->assertOk();
            }
        }
        foreach (array_keys(SchoolShow::TABS) as $tab) {
            $this->get(route('platform.school', [$this->school, 'tab' => $tab]))->assertOk()->assertSee('School A');
        }

        $this->actingAs($this->admin);
        foreach (['platform.dashboard', 'platform.schools', 'platform.payments'] as $route) {
            $this->get(route($route))->assertForbidden();
        }
        $this->get(route('platform.school', $this->school))->assertForbidden();
        Livewire::actingAs($this->admin)->test(SchoolShow::class, ['school' => $this->school])->assertForbidden();
    }

    public function test_free_months_and_payments_add_on_from_the_end_date_and_are_recorded(): void
    {
        $this->school->update(['access_until' => today()->addDays(10)]);

        $this->page('billing')->set('freeMonths', '2')->set('freeNote', 'Launch offer')->call('giveFree')->assertHasNoErrors();
        $this->assertTrue($this->school->fresh()->access_until->eq(today()->addDays(11)->addMonthsNoOverflow(2)->subDay()));

        $before = $this->school->fresh()->access_until;
        $this->page('billing')->set('amount', '6000')->set('months', '3')->set('method', 'jazzcash')->set('reference', 'TX1')->call('recordPayment')->assertHasNoErrors();
        $school = $this->school->fresh();
        $this->assertTrue($school->access_until->eq($before->copy()->addDay()->addMonthsNoOverflow(3)->subDay()));
        $this->assertSame('basic', $school->plan);

        $this->assertSame(['payment', 'free'], SubscriptionEntry::latest('id')->pluck('type')->all());
        $this->assertSame(6000, (int) SubscriptionEntry::where('type', 'payment')->value('amount'));
        $this->assertSame($this->owner->id, SubscriptionEntry::first()->user_id);
    }

    public function test_a_payment_after_expiry_starts_from_today(): void
    {
        $this->school->update(['access_until' => today()->subDays(40), 'is_active' => false]);

        $this->page('billing')->set('amount', '2000')->set('months', '1')->call('recordPayment')->assertHasNoErrors();

        $school = $this->school->fresh();
        $this->assertTrue($school->access_until->eq(today()->addMonthNoOverflow()->subDay()));
        $this->assertTrue($school->is_active);
    }

    public function test_changing_the_date_by_hand_needs_a_reason(): void
    {
        $this->page('billing')->set('manualDate', '2030-01-31')->call('setDate')->assertHasErrors('manualNote');
        $this->page('billing')->set('manualDate', '2030-01-31')->set('manualNote', 'Yearly deal')->call('setDate')->assertHasNoErrors();

        $this->assertSame('2030-01-31', $this->school->fresh()->access_until->toDateString());
        $this->assertSame('adjust', SubscriptionEntry::first()->type);
    }

    public function test_a_school_is_warned_then_locked_after_the_extra_days_and_opens_again_when_paid(): void
    {
        $this->school->update(['access_until' => today()->subDays(3), 'grace_days' => 7, 'monthly_fee' => 2500]);
        $this->actingAs($this->admin)->get(route('dashboard'))->assertOk()->assertSee('Your subscription ended on')->assertSee('Rs 2,500');

        $this->school->update(['access_until' => today()->subDays(8)]);
        $this->actingAs($this->admin)->get(route('dashboard'))->assertRedirect(route('subscription.expired'));
        $this->get(route('subscription.expired'))->assertOk()->assertSee('subscription ended')->assertSee('nothing has been deleted');
        auth()->logout();
        $this->get('/apply/'.$this->school->slug)->assertNotFound();
        $this->actingAs($this->admin);

        $this->school->update(['lock_when_due' => false]);
        $this->actingAs($this->admin)->get(route('dashboard'))->assertOk();

        $this->school->update(['lock_when_due' => true]);
        $this->page('billing')->set('amount', '2500')->call('recordPayment');
        $this->actingAs($this->admin->fresh())->get(route('dashboard'))->assertOk();
        $this->get(route('subscription.expired'))->assertRedirect(route('dashboard'));
    }

    public function test_a_switched_off_feature_leaves_the_menu_and_its_pages_and_public_pages_close(): void
    {
        $site = app(SiteBuilder::class)->forSchool($this->school);
        $site->update(['is_published' => true]);

        $this->page('features')->set('features', array_values(array_diff(array_keys(School::featuresFor('school')), ['timetable', 'website', 'admissions'])))->call('saveFeatures');

        $routes = collect(Navigation::for($this->admin->fresh()))->flatten(1)->pluck(1);
        $this->assertNotContains('timetable', $routes);
        $this->assertNotContains('website.pages', $routes);
        $this->assertContains('attendance.mark', $routes);

        $this->actingAs($this->admin->fresh());
        $this->get(route('timetable'))->assertForbidden();
        $this->get(route('website.pages'))->assertForbidden();
        $this->get(route('attendance.mark'))->assertOk();

        auth()->logout();
        $this->get('/site/'.$this->school->slug)->assertNotFound();
        $this->get('/apply/'.$this->school->slug)->assertNotFound();

        $this->page('features')->set('features', array_keys(School::featuresFor('school')))->call('saveFeatures');
        $this->get('/site/'.$this->school->slug)->assertOk();
    }

    public function test_owner_can_sign_in_as_a_school_login_and_come_back(): void
    {
        $this->admin->update(['must_change_password' => true]);
        $this->school->update(['access_until' => today()->subDays(30)]);

        $this->page('users')->call('loginAs', $this->admin->id)->assertRedirect(route('dashboard'));
        $this->assertSame($this->admin->id, auth()->id());

        // The owner is not stopped by the school's lock or its password rule.
        $this->get(route('dashboard'))->assertOk()->assertSee('You are using')->assertSee('Back to owner panel');

        $this->post(route('impersonation.stop'))->assertRedirect(route('platform.school', $this->school->id));
        $this->assertSame($this->owner->id, auth()->id());
        $this->assertSame(2, AuditLog::withoutGlobalScopes()->whereIn('action', ['login_as_started', 'login_as_ended'])->count());
    }

    public function test_sign_in_as_goes_straight_to_the_website_manager(): void
    {
        $this->page('website')->call('loginAs', $this->admin->id, 'website')->assertRedirect(route('website.pages'));
    }

    public function test_only_this_schools_logins_can_be_used(): void
    {
        $other = app(SchoolProvisioner::class)->create(['school' => 'School B', 'type' => 'school', 'name' => 'Other', 'email' => 'b@example.test', 'phone' => '03000000001', 'password' => 'password']);

        $this->page('users')->call('loginAs', $other->id)->assertNotFound();
        $this->page('users')->call('resetPassword', $other->id)->assertNotFound();
        $this->page('users')->call('loginAs', $this->owner->id)->assertNotFound();
        $this->assertSame($this->owner->id, auth()->id());
    }

    public function test_a_stop_request_without_a_login_as_does_nothing_harmful(): void
    {
        $this->actingAs($this->admin)->post(route('impersonation.stop'))->assertRedirect(route('login'));
        $this->assertSame($this->admin->id, auth()->id());
    }

    public function test_owner_can_reset_a_password_and_disable_a_login(): void
    {
        $page = $this->page('users')->call('resetPassword', $this->admin->id);
        $password = $page->get('issued')['password'];
        $this->assertTrue(Hash::check($password, $this->admin->fresh()->password));
        $this->assertTrue($this->admin->fresh()->must_change_password);

        $page->call('toggleUser', $this->admin->id);
        $this->assertFalse($this->admin->fresh()->is_active);
    }

    public function test_owner_can_add_a_school_with_a_fee_and_free_days(): void
    {
        $page = Livewire::actingAs($this->owner)->test(Schools::class)
            ->set('school', 'New Academy')->set('name', 'Principal')->set('email', 'New@Academy.test')->set('phone', '03001112222')
            ->set('monthlyFee', '3000')->set('freeDays', '60')->call('create')->assertHasNoErrors();

        $school = School::where('name', 'New Academy')->firstOrFail();
        $this->assertSame(3000, $school->monthly_fee);
        $this->assertSame(today()->addDays(60)->toDateString(), $school->access_until->toDateString());
        $this->assertSame('trial', $school->status());

        $issued = $page->get('issued');
        $this->assertSame('new@academy.test', $issued['username']);
        $this->assertTrue(Hash::check($issued['password'], User::where('username', 'new@academy.test')->value('password')));
    }

    public function test_suspending_from_the_school_page_signs_its_users_out(): void
    {
        $this->page()->call('toggleActive');
        $this->assertFalse($this->school->fresh()->is_active);

        $this->actingAs($this->admin->fresh())->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_dashboard_lists_schools_to_call_and_money_this_month(): void
    {
        $this->school->update(['access_until' => today()->addDays(3), 'monthly_fee' => 1500]);
        $this->page('billing')->set('amount', '4500')->set('months', '3')->call('recordPayment');
        $this->school->fresh()->update(['access_until' => today()->addDays(3)]);

        $this->actingAs($this->owner)->get(route('platform.dashboard'))->assertOk()
            ->assertSee('School A')->assertSee('Rs 4,500')->assertSee('3 days left');
    }

    public function test_signing_in_records_the_time(): void
    {
        Livewire::test(Login::class)->set('username', 'head@example.test')->set('password', 'password')->call('login');

        $this->assertNotNull($this->admin->fresh()->last_login_at);
    }

    public function test_putting_a_school_on_a_plan_sets_its_fee_and_features(): void
    {
        Plan::where('code', 'basic')->update(['monthly_price' => 3000]);

        $this->page('billing')->set('planCode', 'basic')->call('applyPlan')->assertHasNoErrors();
        $school = $this->school->fresh();
        $this->assertSame('basic', $school->plan);
        $this->assertSame(3000, $school->monthly_fee);
        $this->assertFalse($school->hasFeature('library'));
        $this->assertTrue($school->hasFeature('fees'));
        $this->assertSame('Basic', $school->planName());

        $school->update(['monthly_fee' => 2000]);
        $this->page('billing')->set('planCode', 'pro')->set('keepFee', true)->call('applyPlan');
        $this->assertSame(2000, $this->school->fresh()->monthly_fee);
        $this->assertTrue($this->school->fresh()->hasFeature('library'));

        $this->page('billing')->set('planCode', 'nope')->call('applyPlan')->assertHasErrors('planCode');
    }

    public function test_plans_can_be_made_and_changed_for_every_school_on_them(): void
    {
        $this->page('billing')->set('planCode', 'basic')->call('applyPlan');

        $plans = Livewire::actingAs($this->owner)->test(Plans::class);
        $plans->call('openForm')->set('name', 'Gold Plus')->set('monthlyPrice', '9000')->set('features', ['fees', 'website'])->call('save')->assertHasNoErrors();
        $this->assertSame(['fees', 'website'], Plan::where('code', 'gold-plus')->first()->features);
        $plans->call('openForm')->set('name', 'Trial')->set('code', 'trial')->call('save')->assertHasErrors('code');

        $basic = Plan::where('code', 'basic')->first();
        $plans->call('openForm', $basic->id)->set('monthlyPrice', '4000')->set('features', ['fees'])->call('save');
        $this->assertSame(0, $this->school->fresh()->monthly_fee, 'Schools change only when asked.');

        $plans->call('openForm', $basic->id)->set('updateSchools', true)->call('save');
        $this->assertSame(4000, $this->school->fresh()->monthly_fee);
        $this->assertFalse($this->school->fresh()->hasFeature('attendance'));

        $plans->call('delete', $basic->id);
        $this->assertNotNull($basic->fresh(), 'A plan in use is kept.');
        $plans->call('delete', Plan::where('code', 'gold-plus')->first()->id);
        $this->assertNull(Plan::where('code', 'gold-plus')->first());

        Livewire::actingAs($this->admin)->test(Plans::class)->assertForbidden();
    }

    public function test_a_first_payment_moves_the_school_off_the_free_period(): void
    {
        $this->assertSame('Free period', $this->school->planName());

        $this->page('billing')->set('amount', '1000')->call('recordPayment');

        $this->assertSame('basic', $this->school->fresh()->plan);
    }

    public function test_payments_have_a_receipt_only_the_owner_can_open(): void
    {
        $this->page('billing')->set('amount', '7500')->set('months', '3')->set('reference', 'JC-99')->call('recordPayment');
        $this->page('billing')->set('freeMonths', '1')->call('giveFree');
        [$free, $payment] = [SubscriptionEntry::where('type', 'free')->first(), SubscriptionEntry::where('type', 'payment')->first()];

        $this->actingAs($this->owner)->get(route('platform.receipt', $payment))->assertOk()
            ->assertSee('Subscription receipt')->assertSee('School A')->assertSee('Rs 7,500')->assertSee('JC-99')->assertSee('S-'.str_pad((string) $payment->id, 5, '0', STR_PAD_LEFT));
        $this->get(route('platform.receipt', $free))->assertNotFound();
        $this->actingAs($this->admin)->get(route('platform.receipt', $payment))->assertForbidden();
    }

    public function test_reminder_opens_whatsapp_to_the_school_with_the_date_and_fee(): void
    {
        $this->school->update(['access_until' => today()->addDays(5), 'monthly_fee' => 2500]);

        $url = $this->school->fresh()->reminderUrl();
        $this->assertStringStartsWith('https://wa.me/923000000000?text=', $url);
        $text = rawurldecode(substr($url, strpos($url, '=') + 1));
        $this->assertStringContainsString('ends on '.today()->addDays(5)->format('j M Y'), $text);
        $this->assertStringContainsString('Rs 2,500', $text);

        $this->school->update(['access_until' => today()->subDays(2)]);
        $this->assertStringContainsString('ended on', rawurldecode($this->school->fresh()->reminderUrl()));

        $this->actingAs($this->owner)->get(route('platform.dashboard'))->assertSee('Remind');
        $this->school->update(['phone' => null]);
        $this->assertNull($this->school->fresh()->reminderUrl());
    }
}
