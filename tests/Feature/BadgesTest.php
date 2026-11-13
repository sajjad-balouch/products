<?php

namespace Tests\Feature;

use App\Models\Biz\Customer;
use App\Models\Biz\Order;
use App\Models\Message;
use App\Models\User;
use App\Services\SchoolProvisioner;
use App\Support\Badges;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BadgesTest extends TestCase
{
    use RefreshDatabase;

    private function owner(string $type = 'business'): User
    {
        return app(SchoolProvisioner::class)->create(['school' => 'Badge '.$type, 'type' => $type, 'name' => 'Owner', 'email' => $type.'@badges.test', 'phone' => '03000000001', 'password' => 'password']);
    }

    public function test_menu_shows_counts_of_new_orders_and_messages(): void
    {
        $owner = $this->owner();
        $this->actingAs($owner);
        $customer = Customer::create(['code' => 'C-1', 'name' => 'Aslam', 'phone' => '03001112222']);
        foreach (['new', 'new', 'accepted'] as $i => $status) {
            Order::create(['number' => 'SO-'.$i, 'client_uuid' => 'u'.$i, 'customer_id' => $customer->id, 'user_id' => $owner->id, 'status' => $status, 'price_list' => 'wholesale', 'total' => 100, 'ordered_at' => now()]);
        }
        Message::create(['phone' => '03001112222', 'category' => 'fee', 'body' => 'x', 'status' => 'queued']);
        Message::create(['phone' => '03001112222', 'category' => 'fee', 'channel' => 'sms', 'body' => 'x', 'status' => 'failed']);
        Message::create(['phone' => '03001112222', 'category' => 'fee', 'body' => 'x', 'status' => 'sent']);

        $this->assertSame(['biz.orders' => 2, 'messages.index' => 2], Badges::for($owner->fresh()));

        $page = $this->get(route('biz.dashboard'))->assertOk();
        $page->assertSee('aria-label="Notifications"', false)->assertSeeInOrder(['Orders from salesmen', '>2</span>'], false);
        $this->getJson(route('badges'))->assertOk()->assertJson(['badges' => ['biz.orders' => 2, 'messages.index' => 2]])
            ->assertJsonPath('list.0.url', route('biz.orders'));
    }

    public function test_other_schools_are_not_counted_and_big_numbers_are_short(): void
    {
        $a = $this->owner();
        $b = $this->owner('school');
        Message::withoutGlobalScopes()->create(['school_id' => $b->school_id, 'phone' => '03001112222', 'category' => 'fee', 'body' => 'x', 'status' => 'queued']);

        $this->actingAs($a);
        $this->assertSame([], Badges::for($a));
        $this->assertSame('99+', Badges::text(150));
        $this->assertSame('7', Badges::text(7));
    }
}
