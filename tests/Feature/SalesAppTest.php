<?php

namespace Tests\Feature;

use App\Livewire\Biz\EmployeeShow;
use App\Livewire\Biz\Orders as OrdersPage;
use App\Livewire\Biz\SaleForm;
use App\Models\Biz\Customer;
use App\Models\Biz\Employee;
use App\Models\Biz\Invoice;
use App\Models\Biz\Order;
use App\Models\Biz\Product;
use App\Models\Biz\ProductType;
use App\Models\User;
use App\Services\Biz\Stock;
use App\Services\SchoolProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

/** The salesman / owner phone app: sign-in, offline-safe orders, and the owner turning them into sales. */
class SalesAppTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private Employee $asif;

    private Product $charger;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = app(SchoolProvisioner::class)->create(['school' => 'Wholesale Mobiles', 'type' => 'business', 'kind' => 'mobile', 'name' => 'Owner', 'email' => 'owner@example.test', 'phone' => '03000000001', 'password' => 'password']);
        $this->actingAs($this->owner);
        $this->asif = Employee::create(['code' => 'E-001', 'name' => 'Asif', 'is_salesman' => true, 'phone' => '03004445566', 'commission_percent' => 2]);
        $this->charger = Product::create(['type_id' => ProductType::where('name', 'Accessory / general')->value('id'), 'brand' => 'Faster', 'name' => 'Charger', 'sale_price' => 650, 'wholesale_price' => 450]);
        app(Stock::class)->purchase($this->owner->school, $this->charger, ['date' => today()->toDateString(), 'quantity' => 100, 'unit_cost' => 300]);
    }

    /** The owner gives Asif a login from his employee page, then the phone signs in. */
    private function salesmanToken(): string
    {
        Livewire::test(EmployeeShow::class, ['employee' => $this->asif])->call('openLogin')->assertSet('loginUsername', '03004445566')
            ->set('loginPassword', 'secret123')->call('saveLogin')->assertHasNoErrors();

        return $this->postJson('/sales/api/login', ['username' => '03004445566', 'password' => 'secret123'])->assertOk()->assertJsonPath('me.role', 'salesman')->json('token');
    }

    private function order(array $extra = []): array
    {
        return array_merge(['uuid' => (string) Str::uuid(), 'price_list' => 'wholesale', 'ordered_at' => now()->subHour()->toIso8601String(),
            'lines' => [['product_id' => $this->charger->id, 'quantity' => 12, 'unit_price' => 450]]], $extra);
    }

    public function test_the_app_page_installs_and_works_offline(): void
    {
        $this->get('/sales')->assertOk()->assertSee('MSK Devs Sales')->assertSee('manifest.webmanifest');
        $this->get('/sales/manifest.webmanifest')->assertOk()->assertJsonPath('display', 'standalone')->assertJsonPath('name', 'MSK Devs Sales');
        $this->get('/sales/sw.js')->assertOk()->assertHeader('Service-Worker-Allowed')->assertSee('caches.open');
        $this->get('/.well-known/assetlinks.json')->assertOk()->assertExactJson([]);
        config(['services.sales_app.android_sha256' => 'AA:BB']);
        $this->get('/.well-known/assetlinks.json')->assertJsonPath('0.target.package_name', 'com.mskdevs.sales')->assertJsonPath('0.target.sha256_cert_fingerprints.0', 'AA:BB');
    }

    public function test_a_salesman_signs_in_takes_orders_offline_and_sees_only_his_own(): void
    {
        $this->postJson('/sales/api/login', ['username' => 'nobody', 'password' => 'x'])->assertStatus(422);
        $token = $this->salesmanToken();
        $user = User::where('username', '03004445566')->firstOrFail();
        $this->assertSame(['salesman', $user->id], [$user->role, $this->asif->fresh()->user_id]);
        $this->assertSame('app', $user->homeRoute());
        $auth = ['Authorization' => 'Bearer '.$token];

        // Everything for offline use.
        $this->getJson('/sales/api/catalog', $auth)->assertOk()->assertJsonPath('me.business', 'Wholesale Mobiles')
            ->assertJsonFragment(['label' => $this->charger->label(), 'wholesale' => 450, 'stock' => 100]);

        // Two orders saved offline arrive together; one is sent twice because of a weak signal.
        $first = $this->order(['new_customer' => ['name' => 'Bilal Mobile Zone', 'phone' => '0301-2223344', 'address' => 'Hall Road']]);
        $second = $this->order(['notes' => 'Deliver Monday']);
        $saved = $this->postJson('/sales/api/orders', ['orders' => [$first, $second, $first]], $auth)->assertOk()->json('results');
        $this->assertSame([true, false, true], [$saved[0]['ok'], $saved[1]['ok'], $saved[2]['ok']]);
        $this->assertSame('Choose the customer for this order.', $saved[1]['message']);
        $this->assertSame(1, Order::count(), 'The same order sent twice is saved once.');
        $order = Order::firstOrFail();
        $this->assertSame([5400, 'new', $this->asif->id, 'SO-00001'], [(int) $order->total, $order->status, $order->salesman_id, $order->number]);
        $this->assertSame('03012223344', Customer::where('name', 'Bilal Mobile Zone')->value('phone'));

        // The second one had no customer, so it saved with none; a fixed copy goes through.
        $fixed = $this->order(['customer_id' => Customer::where('name', 'Bilal Mobile Zone')->value('id')]);
        $this->postJson('/sales/api/orders', ['orders' => [$fixed]], $auth)->assertOk()->assertJsonPath('results.0.ok', true);

        // A salesman cannot accept orders, and sees only his own.
        $this->postJson('/sales/api/orders/'.$order->id, ['action' => 'accept'], $auth)->assertForbidden();
        $other = Employee::create(['code' => 'E-002', 'name' => 'Bilal', 'is_salesman' => true]);
        Order::create(['number' => 'SO-09999', 'client_uuid' => (string) Str::uuid(), 'salesman_id' => $other->id, 'customer_id' => Customer::value('id'), 'ordered_at' => now(), 'status' => 'new']);
        $this->assertCount(Order::where('user_id', $user->id)->count(), $this->getJson('/sales/api/orders', $auth)->json('orders'));

        // Switching the login off signs the phone out.
        Livewire::test(EmployeeShow::class, ['employee' => $this->asif->fresh()])->call('switchOffLogin');
        $this->getJson('/sales/api/catalog', $auth)->assertStatus(401);
        $this->getJson('/sales/api/catalog', ['Authorization' => 'Bearer '.str_repeat('x', 48)])->assertStatus(401);
    }

    public function test_the_owner_accepts_on_the_phone_and_makes_the_sale_from_the_order(): void
    {
        $token = $this->salesmanToken();
        $shop = Customer::create(['code' => 'C-0001', 'name' => 'Ali Traders', 'phone' => '03009998877']);
        $this->postJson('/sales/api/orders', ['orders' => [$this->order(['customer_id' => $shop->id])]], ['Authorization' => 'Bearer '.$token])->assertOk();
        $order = Order::firstOrFail();

        // The owner's own login works in the app and sees everyone's orders.
        $owner = $this->postJson('/sales/api/login', ['username' => 'owner@example.test', 'password' => 'password'])->assertOk()->assertJsonPath('me.role', 'owner')->json('token');
        $list = $this->getJson('/sales/api/orders', ['Authorization' => 'Bearer '.$owner])->assertOk();
        $this->assertSame([1, 'Asif'], [$list->json('summary.new'), $list->json('orders.0.salesman')]);
        $this->postJson('/sales/api/orders/'.$order->id, ['action' => 'accept'], ['Authorization' => 'Bearer '.$owner])->assertOk()->assertJsonPath('order.status', 'accepted');

        // On the computer: the orders page, then the sale made from the order.
        Livewire::test(OrdersPage::class)->assertSee('SO-00001')->assertSee('Ali Traders')->call('open', $order->id)->assertSee('Make sale');
        $sale = Livewire::withQueryParams(['order' => $order->id])->test(SaleForm::class)
            ->assertSet('customerId', $shop->id)->assertSet('salesmanId', (string) $this->asif->id)->assertSet('priceList', 'wholesale')
            ->assertSet('lines.0.quantity', '12')->assertSet('lines.0.unit_price', '450')->assertSee('Making the sale from order');
        $sale->call('save')->assertHasNoErrors();
        $invoice = Invoice::firstOrFail();
        $this->assertSame([5400, $this->asif->id, 5400], [(int) $invoice->total, $invoice->salesman_id, $invoice->balance()]);
        $this->assertSame(['delivered', $invoice->id], [$order->fresh()->status, $order->fresh()->invoice_id]);
        $this->assertSame(88, $this->charger->units()->where('status', 'in_stock')->count());

        // A delivered order cannot be made into a second sale.
        Livewire::withQueryParams(['order' => $order->id])->test(SaleForm::class)->assertSet('fromOrder', null)->assertSet('lines', []);
    }

    public function test_another_business_cannot_see_or_touch_these_orders(): void
    {
        $token = $this->salesmanToken();
        $this->postJson('/sales/api/orders', ['orders' => [$this->order(['new_customer' => ['name' => 'Shop']])]], ['Authorization' => 'Bearer '.$token])->assertOk();
        $order = Order::firstOrFail();

        $rival = app(SchoolProvisioner::class)->create(['school' => 'Rival', 'type' => 'business', 'kind' => 'mobile', 'name' => 'R', 'email' => 'r@example.test', 'phone' => '03000000002', 'password' => 'password']);
        $rivalToken = $this->postJson('/sales/api/login', ['username' => 'r@example.test', 'password' => 'password'])->json('token');
        $this->getJson('/sales/api/orders', ['Authorization' => 'Bearer '.$rivalToken])->assertOk()->assertJsonCount(0, 'orders');
        $this->postJson('/sales/api/orders/'.$order->id, ['action' => 'cancel', 'reason' => 'x'], ['Authorization' => 'Bearer '.$rivalToken])->assertNotFound();
        // Their products cannot be ordered either.
        $this->postJson('/sales/api/orders', ['orders' => [$this->order()]], ['Authorization' => 'Bearer '.$rivalToken])->assertJsonPath('results.0.ok', false);
        $this->assertSame('new', $order->fresh()->status);

        // A school login cannot use the sales app.
        $school = app(SchoolProvisioner::class)->create(['school' => 'A School', 'type' => 'school', 'name' => 'P', 'email' => 's@example.test', 'phone' => '03000000003', 'password' => 'password']);
        $this->postJson('/sales/api/login', ['username' => 's@example.test', 'password' => 'password'])->assertForbidden();
    }
}
