<?php

namespace Tests\Feature;

use App\Livewire\Biz\ContractForm;
use App\Livewire\Biz\Products;
use App\Livewire\Biz\ProductShow;
use App\Models\Biz\Contract;
use App\Models\Biz\Customer;
use App\Models\Biz\Product;
use App\Models\Biz\ProductType;
use App\Models\Biz\StockUnit;
use App\Models\School;
use App\Models\User;
use App\Services\Biz\Installments;
use App\Services\Biz\Ledger;
use App\Services\Biz\Stock;
use App\Services\SchoolProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductStockTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private School $shop;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = app(SchoolProvisioner::class)->create(['school' => 'Shop A', 'type' => 'business', 'name' => 'Owner', 'email' => 'shop@example.test', 'phone' => '03000000001', 'password' => 'password']);
        $this->shop = $this->owner->school;
        $this->actingAs($this->owner);
    }

    private function honda(): Product
    {
        return Product::create(['type_id' => ProductType::where('name', 'Motorcycle')->value('id'), 'brand' => 'Honda', 'name' => 'CD 70', 'sale_price' => 189000]);
    }

    private function sell(Product $product, ?int $unitId = null, array $details = []): Contract
    {
        $customer = Customer::create(['code' => 'C-'.random_int(1000, 9999), 'name' => 'Buyer', 'phone' => '0301'.random_int(1000000, 9999999)]);

        return app(Installments::class)->sell($this->shop, $customer, [
            'product_id' => $product->id, 'stock_unit_id' => $unitId, 'details' => $details, 'item' => '', 'sale_date' => today()->toDateString(),
            'cash_price' => 189000, 'markup_percent' => 20, 'down_payment' => 40000, 'installments' => 12, 'frequency' => 'monthly',
            'first_due_on' => today()->addMonth()->toDateString(), 'rounding' => 100,
        ]);
    }

    public function test_each_piece_keeps_its_own_cost_and_profit_follows_it(): void
    {
        $this->assertSame(count(ProductType::STARTERS), ProductType::count()); // ready-made types for a new business
        $honda = $this->honda();
        $stock = app(Stock::class);

        $first = $stock->purchase($this->shop, $honda, ['date' => today()->subMonth()->toDateString(), 'quantity' => 1, 'unit_cost' => 160000, 'paid_by' => 'bank'], [['engine_no' => 'E-1']]);
        $second = $stock->purchase($this->shop, $honda, ['date' => today()->toDateString(), 'quantity' => 2, 'unit_cost' => 168000, 'paid_by' => 'credit', 'supplier' => 'Atlas']);
        $ledger = Ledger::for($this->shop);
        $this->assertSame(160000 + 2 * 168000, $ledger->balanceOf('inventory'));
        $this->assertSame(-160000, $ledger->balanceOf('bank'));
        $this->assertSame(336000, $ledger->balanceOf('payable'));
        $this->assertSame(['count' => 3, 'value' => 496000], Stock::onHand($honda));

        // No piece chosen: the oldest goes first, with its own cost and the details it came with.
        $saleA = $this->sell($honda);
        $this->assertSame('Honda CD 70', $saleA->item);
        $this->assertSame(160000, $saleA->cost_price);
        $this->assertSame($first->units->first()->id, $saleA->stock_unit_id);
        $this->assertSame('E-1', $saleA->details['engine_no']);
        $this->assertStringContainsString('Engine no.: E-1', $saleA->item_details);

        // A chosen piece from the dearer batch: same product, less profit.
        $unit = $second->units->last();
        $saleB = $this->sell($honda, $unit->id, ['engine_no' => 'E-3', 'colour' => 'Black']);
        $this->assertSame(168000, $saleB->cost_price);
        $this->assertSame($saleA->grossProfit() - 8000, $saleB->grossProfit());
        $this->assertSame(['engine_no' => 'E-3', 'colour' => 'Black'], $unit->fresh()->details);
        $this->assertSame('sold', $unit->fresh()->status);

        // The books: stock left at cost, and the cost of each piece sold in cost of goods.
        $this->assertSame(168000, $ledger->balanceOf('inventory'));
        $this->assertSame(328000, $ledger->balanceOf('cogs'));
        $accounts = $ledger->accounts();
        $this->assertSame($accounts->sum('total_debit'), $accounts->sum('total_credit'));

        // A sold piece cannot be sold again.
        $this->expectException(\InvalidArgumentException::class);
        $this->sell($honda, $unit->id);
    }

    public function test_products_types_stock_and_selling_from_the_screens(): void
    {
        Livewire::test(Products::class)->set('tab', 'types')->call('openType')->set('typeName', 'Tractor')->set('typeFields', "Engine no.\nChassis no.\nHorsepower")->call('saveType')->assertHasNoErrors();
        $tractor = ProductType::where('name', 'Tractor')->firstOrFail();
        $this->assertSame(['engine_no', 'chassis_no', 'horsepower'], array_column($tractor->fields, 'key'));

        $page = Livewire::test(Products::class)->call('openProduct')->set('product.type_id', (string) $tractor->id)->set('product.brand', 'Massey')
            ->set('product.name', 'MF 375')->set('product.sale_price', '3500000')->call('saveProduct')->assertHasNoErrors();
        $product = Product::where('name', 'MF 375')->firstOrFail();

        $page->call('openStock', $product->id)->set('purchase.quantity', '2')->assertCount('units', 2)
            ->set('purchase.unit_cost', '3000000')->set('units.0.engine_no', 'MF-1')->call('saveStock')->assertHasNoErrors();
        $page->call('openStock', $product->id)->set('purchase.quantity', '1')->set('purchase.unit_cost', '3100000')->call('saveStock')->assertHasNoErrors();
        $this->assertSame(3, StockUnit::where('product_id', $product->id)->where('status', 'in_stock')->count());
        $this->get(route('biz.products'))->assertOk()->assertSee('Massey MF 375');

        // Selling: search, pick, the price and the oldest piece's cost come in.
        $form = Livewire::test(ContractForm::class)->set('productSearch', 'MF')->assertSee('3 in stock')
            ->call('pickProduct', $product->id)
            ->assertSet('sale.item', 'Massey MF 375')->assertSet('sale.cash_price', '3500000')->assertSet('sale.cost_price', '3000000')
            ->assertSet('details.engine_no', 'MF-1');
        $dearer = StockUnit::where('unit_cost', 3100000)->firstOrFail();
        $form->set('stockUnitId', $dearer->id)->assertSet('sale.cost_price', '3100000')
            ->set('details.engine_no', 'MF-9')->set('newCustomer', true)->set('form.name', 'Farmer')->set('form.phone', '03111111111')
            ->set('sale.down_payment', '1000000')->call('save')->assertHasNoErrors();
        $contract = Contract::firstOrFail();
        $this->assertSame([3100000, $dearer->id, 'MF-9'], [$contract->cost_price, $contract->stock_unit_id, $contract->details['engine_no']]);

        $this->get(route('biz.product', $product))->assertOk()->assertSee($contract->number)->assertSee(rs($contract->grossProfit()));
        $this->get(route('biz.reports.profit'))->assertOk()->assertSee($contract->number)->assertSee('3,100,000');
        $this->get(route('biz.contracts.new', ['product' => $product->id]))->assertOk()->assertSee('2 in stock');

        // Details of a piece still in stock can be written later.
        $left = StockUnit::where('status', 'in_stock')->orderBy('id')->first();
        Livewire::test(ProductShow::class, ['product' => $product])->call('openUnit', $left->id)->set('unitDetails.chassis_no', 'CH-7')->call('saveUnit');
        $this->assertSame('CH-7', $left->fresh()->details['chassis_no']);
    }

    public function test_another_business_cannot_see_or_use_the_stock(): void
    {
        $honda = $this->honda();
        app(Stock::class)->purchase($this->shop, $honda, ['date' => today()->toDateString(), 'quantity' => 1, 'unit_cost' => 160000]);

        $other = app(SchoolProvisioner::class)->create(['school' => 'Shop B', 'type' => 'business', 'name' => 'B', 'email' => 'b@example.test', 'phone' => '03000000002', 'password' => 'password']);
        $this->actingAs($other)->get(route('biz.product', $honda))->assertNotFound();
        $this->actingAs($other)->get(route('biz.products'))->assertOk()->assertDontSee('CD 70');
        Livewire::actingAs($other)->test(ContractForm::class)->call('pickProduct', $honda->id)->assertNotFound();

        $this->expectException(\InvalidArgumentException::class);
        app(Stock::class)->purchase($other->school, $honda, ['date' => today()->toDateString(), 'quantity' => 1, 'unit_cost' => 1]);
    }
}
