<?php

namespace Tests\Feature;

use App\Livewire\Biz\Products;
use App\Livewire\Biz\SaleForm;
use App\Livewire\Biz\SaleShow;
use App\Models\Biz\Brand;
use App\Models\Biz\BrandModel;
use App\Models\Biz\Category;
use App\Models\Biz\Customer;
use App\Models\Biz\Invoice;
use App\Models\Biz\Product;
use App\Models\Biz\ProductType;
use App\Models\Biz\StockUnit;
use App\Models\School;
use App\Models\User;
use App\Services\Biz\Ledger;
use App\Services\Biz\Sales;
use App\Services\Biz\Stock;
use App\Services\SchoolEraser;
use App\Services\SchoolProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CatalogSalesTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private School $shop;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = app(SchoolProvisioner::class)->create(['school' => 'Wholesale Mobiles', 'type' => 'business', 'name' => 'Owner', 'email' => 'w@example.test', 'phone' => '03000000001', 'password' => 'password']);
        $this->shop = $this->owner->school;
        $this->actingAs($this->owner);
    }

    private function phone(): Product
    {
        $type = ProductType::where('name', 'Mobile phone')->firstOrFail();
        $brand = Brand::where('type_id', $type->id)->where('name', 'Infinix')->firstOrFail();

        return Product::create(['type_id' => $type->id, 'brand_id' => $brand->id, 'brand' => 'Infinix', 'model_id' => $brand->models()->where('name', 'Hot 40')->value('id'), 'name' => 'Hot 40', 'variant' => '8/128', 'sale_price' => 38500, 'wholesale_price' => 36500]);
    }

    private function charger(): Product
    {
        return Product::create(['type_id' => ProductType::where('name', 'Accessory / general')->value('id'), 'category_id' => Category::where('name', 'Chargers and cables')->value('id'), 'brand' => 'Faster', 'name' => 'Charger', 'sale_price' => 650, 'wholesale_price' => 450]);
    }

    private function assertBooksBalance(): void
    {
        $ledger = Ledger::for($this->shop);
        $accounts = $ledger->accounts();
        $this->assertSame($accounts->sum('total_debit'), $accounts->sum('total_credit'));
        $this->assertSame((int) StockUnit::where('status', 'in_stock')->sum('unit_cost'), $ledger->balanceOf('inventory'));
        $this->assertSame((int) Invoice::get()->sum(fn ($i) => $i->balance()), $ledger->balanceOf('trade_receivable'));
    }

    public function test_a_new_business_gets_categories_brands_and_models_that_can_be_managed(): void
    {
        $this->assertGreaterThanOrEqual(10, Category::count());
        $samsung = Brand::where('name', 'Samsung')->whereHas('type', fn ($q) => $q->where('name', 'Mobile phone'))->firstOrFail();
        $this->assertTrue($samsung->models()->where('name', 'Galaxy A35')->exists());
        $this->assertTrue(Brand::where('name', 'Honda')->whereHas('type', fn ($q) => $q->where('name', 'Motorcycle'))->exists());

        $mobile = ProductType::where('name', 'Mobile phone')->firstOrFail();
        $page = Livewire::test(Products::class)->call('openProduct')->set('product.type_id', (string) $mobile->id)
            ->set('newBrand', 'Sparx')->call('addBrand')
            ->set('newModel', 'Neo 7')->call('addModel')
            ->assertSet('product.name', 'Neo 7')
            ->set('product.variant', '4/64')->set('product.sale_price', '22000')->set('product.wholesale_price', '20500')
            ->set('product.category_id', (string) Category::where('name', 'Mobile phones')->value('id'))
            ->call('saveProduct')->assertHasNoErrors();
        $product = Product::where('name', 'Neo 7')->firstOrFail();
        $this->assertSame(['Sparx', 'Sparx Neo 7 4/64', 20500], [$product->brand, $product->label(), $product->wholesale_price]);

        // Renaming a brand renames it on its products; a used brand or model cannot be deleted.
        $sparx = Brand::where('name', 'Sparx')->firstOrFail();
        Livewire::test(Products::class)->call('openBrand', $sparx->id)->set('brandName', 'Sparx Mobile')->call('saveBrand')->assertHasNoErrors();
        $this->assertSame('Sparx Mobile', $product->fresh()->brand);
        Livewire::test(Products::class)->call('deleteBrand', $sparx->id);
        $this->assertModelExists($sparx);
        Livewire::test(Products::class)->set('modelDraft.'.$sparx->id, 'Neo 8')->call('addModelTo', $sparx->id);
        $neo8 = BrandModel::where('name', 'Neo 8')->firstOrFail();
        Livewire::test(Products::class)->call('deleteModel', $neo8->id);
        $this->assertModelMissing($neo8);

        Livewire::test(Products::class)->call('openCategory')->set('categoryName', 'Smart watches')->call('saveCategory')->assertHasNoErrors();
        $this->assertTrue(Category::where('name', 'Smart watches')->exists());
        $this->get(route('biz.products', ['tab' => 'brands']))->assertOk()->assertSee('Galaxy A35');
    }

    public function test_imeis_are_pasted_kept_unique_and_found(): void
    {
        $phone = $this->phone();
        $page = Livewire::test(Products::class)->call('openStock', $phone->id)
            ->set('identifiers', "356700000000011\n356700000000029\n356700000000037")
            ->assertSet('purchase.quantity', '3')
            ->set('purchase.unit_cost', '33000')->call('saveStock')->assertHasNoErrors();
        $this->assertSame(3, StockUnit::where('product_id', $phone->id)->count());
        $this->assertSame(['356700000000011', '356700000000029', '356700000000037'], StockUnit::orderBy('id')->get()->map(fn ($u) => $u->details['imei'])->all());

        // The same IMEI twice, in the list or already in stock, is refused.
        Livewire::test(Products::class)->call('openStock', $phone->id)->set('identifiers', "356700000000045\n356700000000045")->set('purchase.unit_cost', '33000')->call('saveStock')->assertHasErrors('identifiers');
        Livewire::test(Products::class)->call('openStock', $phone->id)->set('identifiers', '356700000000029')->set('purchase.unit_cost', '33000')->call('saveStock')->assertHasErrors('identifiers');
        $this->assertSame(3, StockUnit::count());

        // Find it, before and after it is sold.
        Livewire::test(Products::class)->set('tab', 'find')->set('find', '00000029')->assertSee('Infinix Hot 40 8/128')->assertSee('In stock');
        $invoice = app(Sales::class)->create($this->shop, null, 'Walk-in', today()->toDateString(), [['product_id' => $phone->id, 'quantity' => 1, 'unit_price' => 38500, 'unit_ids' => [StockUnit::orderBy('id')->skip(1)->value('id')]]], 0, 38500);
        $this->assertSame('356700000000029', $invoice->lines->first()->details);
        Livewire::test(Products::class)->set('tab', 'find')->set('find', '00000029')->assertSee($invoice->number);
    }

    public function test_wholesale_sale_on_credit_costs_each_piece_and_can_be_paid_or_cancelled(): void
    {
        $charger = $this->charger();
        $stock = app(Stock::class);
        $stock->purchase($this->shop, $charger, ['date' => today()->subMonth()->toDateString(), 'quantity' => 3, 'unit_cost' => 300]);
        $stock->purchase($this->shop, $charger, ['date' => today()->toDateString(), 'quantity' => 5, 'unit_cost' => 350, 'paid_by' => 'credit']);
        $dealer = Customer::create(['code' => 'C-0001', 'name' => 'Ali Mobile Zone', 'phone' => '03091112233']);
        $sales = app(Sales::class);

        // Udhaar needs a known customer; more than the bill is refused.
        foreach ([[null, 1000], [$dealer, 99999]] as [$who, $paid]) {
            try {
                $sales->create($this->shop, $who, 'Someone', today()->toDateString(), [['product_id' => $charger->id, 'quantity' => 4, 'unit_price' => 450]], 0, $paid);
                $this->fail('A wrong payment was accepted.');
            } catch (\InvalidArgumentException) {
            }
        }
        $this->assertSame(8, StockUnit::where('status', 'in_stock')->count());

        // Four chargers: the three at 300 first, then one at 350.
        $invoice = $sales->create($this->shop, $dealer, null, today()->toDateString(), [['product_id' => $charger->id, 'quantity' => 4, 'unit_price' => 450]], 100, 1000);
        $this->assertSame([1800, 1700, 1250, 450, 700], [$invoice->subtotal, $invoice->total, $invoice->cost, $invoice->profit(), $invoice->balance()]);
        $this->assertSame(4, StockUnit::where('status', 'in_stock')->count());
        $this->assertBooksBalance();

        $sales->receive($invoice, 500, today()->toDateString(), 'jazzcash');
        $this->assertSame(200, $invoice->fresh()->balance());
        $this->assertBooksBalance();

        // Cancelling puts the pieces back and reverses every entry.
        $sales->void($invoice->fresh(), 'Returned');
        $this->assertSame(8, StockUnit::where('status', 'in_stock')->count());
        $this->assertSame(0, Ledger::for($this->shop)->balanceOf('sales'));
        $this->assertSame(0, Ledger::for($this->shop)->balanceOf('cogs'));
        $this->assertBooksBalance();
    }

    public function test_the_sale_screen_scans_imeis_switches_to_wholesale_and_takes_udhaar(): void
    {
        $phone = $this->phone();
        $charger = $this->charger();
        app(Stock::class)->purchase($this->shop, $phone, ['date' => today()->toDateString(), 'quantity' => 3, 'unit_cost' => 33000], [['imei' => '111111111111111'], ['imei' => '222222222222222'], ['imei' => '333333333333333']]);
        app(Stock::class)->purchase($this->shop, $charger, ['date' => today()->toDateString(), 'quantity' => 50, 'unit_cost' => 320]);
        $dealer = Customer::create(['code' => 'C-0001', 'name' => 'Ali Mobile Zone', 'phone' => '03091112233']);

        $form = Livewire::test(SaleForm::class)->set('productSearch', 'Hot')->assertSee('3 in stock')
            ->call('addProduct', $phone->id)->call('addProduct', $charger->id)
            ->assertSet('lines.0.unit_price', '38500')
            ->set('priceList', 'wholesale')->assertSet('lines.0.unit_price', '36500')->assertSet('lines.1.unit_price', '450')
            ->set('lines.0.imeis', "333333333333333\n222222222222222")->assertSet('lines.0.quantity', '2')
            ->set('lines.1.quantity', '20');

        // An IMEI that is not in stock is caught.
        $form->set('lines.0.imeis', "333333333333333\n999999999999999")->call('save')->assertHasErrors('lines.0.imeis');
        $form->set('lines.0.imeis', "333333333333333\n222222222222222");

        // No customer chosen: udhaar is refused.
        $form->set('paid', '50000')->call('save')->assertHasErrors('lines');
        $form->call('pickCustomer', $dealer->id)->call('save')->assertHasNoErrors();

        $invoice = Invoice::with('lines')->firstOrFail();
        $this->assertSame([82000, 50000, 32000], [$invoice->total, $invoice->paid, $invoice->balance()]);
        $this->assertEqualsCanonicalizing(['333333333333333', '222222222222222'], explode(', ', $invoice->lines[0]->details));
        $this->assertSame('in_stock', StockUnit::where('details->imei', '111111111111111')->value('status'));

        Livewire::test(SaleShow::class, ['invoice' => $invoice])->call('openPay')->assertSet('amount', '32000')->call('receive')->assertHasNoErrors();
        $this->assertSame(0, $invoice->fresh()->balance());
        $this->assertBooksBalance();

        foreach ([route('biz.sales'), route('biz.sale', $invoice), route('biz.print.invoice', $invoice), route('biz.reports.profit'), route('biz.customer', $dealer), route('biz.product', $phone), route('biz.dashboard')] as $url) {
            $this->get($url)->assertOk();
        }
        $this->get(route('biz.reports.profit'))->assertSee($invoice->number);
    }

    public function test_a_business_with_stock_and_sales_can_be_deleted(): void
    {
        $charger = $this->charger();
        app(Stock::class)->purchase($this->shop, $charger, ['date' => today()->toDateString(), 'quantity' => 5, 'unit_cost' => 300]);
        app(Sales::class)->create($this->shop, null, null, today()->toDateString(), [['product_id' => $charger->id, 'quantity' => 2, 'unit_price' => 450]], 0, 900);

        $this->actingAs(User::create(['name' => 'Owner', 'username' => 'o@example.test', 'role' => 'super_admin', 'password' => 'x']));
        app(SchoolEraser::class)->erase($this->shop);
        $this->assertModelMissing($this->shop);
        $this->assertSame(0, StockUnit::withoutGlobalScopes()->count());
        $this->assertSame(0, Invoice::withoutGlobalScopes()->count());
    }
}
