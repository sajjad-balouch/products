<?php

namespace Tests\Feature;

use App\Livewire\Biz\ContractForm;
use App\Livewire\Biz\ContractShow;
use App\Livewire\Biz\Products;
use App\Livewire\Biz\SaleForm;
use App\Livewire\Biz\SaleShow;
use App\Livewire\Biz\Vouchers;
use App\Models\Biz\Account;
use App\Models\Biz\Contract;
use App\Models\Biz\Customer;
use App\Models\Biz\Invoice;
use App\Models\Biz\JournalEntry;
use App\Models\Biz\JournalLine;
use App\Models\Biz\Product;
use App\Models\Biz\ProductType;
use App\Models\Biz\Purchase;
use App\Models\Biz\Receipt;
use App\Models\Biz\StockUnit;
use App\Models\User;
use App\Services\Biz\Stock;
use App\Services\SchoolProvisioner;
use App\Support\Biz\Guides;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Every business entry form explains itself and shows what saving will change. The preview
 * runs the real save and undoes it, so it must leave nothing behind and match the real entry.
 */
class BusinessImpactTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = app(SchoolProvisioner::class)->create(['school' => 'Impact Mobiles', 'type' => 'business', 'kind' => 'mobile', 'name' => 'Owner', 'email' => 'i@example.test', 'phone' => '03000000001', 'password' => 'password']);
        $this->actingAs($this->owner);
    }

    /** A phone with two pieces in stock: one bought for 30,000 and one for 32,000. */
    private function phone(): Product
    {
        $product = Product::create(['type_id' => ProductType::where('name', 'Mobile phone')->value('id'), 'brand' => 'Infinix', 'name' => 'Hot 50', 'sale_price' => 38000]);
        app(Stock::class)->purchase($this->owner->school, $product, ['date' => today()->subDay()->toDateString(), 'quantity' => 1, 'unit_cost' => 30000], [['imei' => '111111111111111']]);
        app(Stock::class)->purchase($this->owner->school, $product, ['date' => today()->subDay()->toDateString(), 'quantity' => 1, 'unit_cost' => 32000], [['imei' => '222222222222222']]);

        return $product;
    }

    /** Everything a preview could have touched. */
    private function snapshot(): array
    {
        return [
            JournalEntry::count(), JournalLine::count(), Invoice::count(), Contract::count(), Receipt::count(), Customer::count(), Purchase::count(),
            StockUnit::where('status', 'in_stock')->count(), DB::table('audit_logs')->count(),
        ];
    }

    /** Every books line saved after the given one, in the shape the preview shows. */
    private function linesSince(int $id): array
    {
        return JournalLine::with('account')->where('id', '>', $id)->orderBy('id')->get()
            ->map(fn ($l) => [$l->account->code, (int) $l->debit, (int) $l->credit])->all();
    }

    private function previewLines(array $impact): array
    {
        return array_map(fn ($l) => [$l['code'], $l['debit'], $l['credit']], $impact['lines']);
    }

    private function effect(array $impact, string $text): ?int
    {
        foreach ($impact['effects'] as $e) {
            if (str_starts_with($e['text'], $text)) {
                return $e['amount'];
            }
        }

        return null;
    }

    public function test_a_sale_shows_its_guide_and_exact_effect_before_saving_and_leaves_nothing_behind(): void
    {
        $product = $this->phone();
        $customer = Customer::create(['code' => 'C-0001', 'name' => 'Ali', 'phone' => '03009998877']);
        $before = $this->snapshot();

        $form = Livewire::test(SaleForm::class)->assertSee('How a sale works')->assertSee('What saving changes')
            ->call('addProduct', $product->id)->set('lines.0.imeis', '222222222222222')->set('paid', '30000');

        // A walk-in buyer cannot leave money unpaid: the preview says so before saving.
        $this->assertSame('Choose the customer to give credit (udhaar) to. A walk-in buyer pays in full.', $form->viewData('impact')['error']);

        $form->call('pickCustomer', $customer->id);
        $impact = $form->viewData('impact');

        $this->assertTrue($impact['ok'], (string) ($impact['error'] ?? $impact['pending'] ?? ''));
        $this->assertSame($before, $this->snapshot(), 'The preview must not save anything.');
        $this->assertSame(30000, $this->effect($impact, 'Cash in hand goes up'));
        $this->assertSame(8000, $this->effect($impact, 'Customers owe you more on credit'));
        $this->assertSame(8000, $this->effect($impact, 'Ali will owe you more'));
        $this->assertSame(38000, $this->effect($impact, 'Sales income'));
        // The dearer piece was scanned, so its own cost is used: 38,000 - 32,000.
        $this->assertSame(6000, $impact['profit']);
        $this->assertSame([['product' => $product->label(), 'pieces' => -1, 'cost' => -32000]], $impact['stock']);
        $form->assertSee('What saving will change')->assertSee('Profit from this entry');

        $last = (int) JournalLine::max('id');
        $form->call('save')->assertHasNoErrors();
        $this->assertSame($this->previewLines($impact), $this->linesSince($last));
    }

    public function test_a_number_not_in_stock_is_reported_in_the_preview_and_on_save(): void
    {
        $product = $this->phone();
        $form = Livewire::test(SaleForm::class)->call('addProduct', $product->id)->set('lines.0.imeis', '999999999999999');

        $impact = $form->viewData('impact');
        $this->assertFalse($impact['ok']);
        $this->assertStringContainsString('999999999999999 is not in stock', $impact['error']);
        $form->assertSee('This cannot be saved yet');
        $form->call('save')->assertHasErrors('lines.0.imeis');
    }

    public function test_an_installment_plan_shows_that_markup_is_not_profit_yet(): void
    {
        $product = $this->phone();
        $before = $this->snapshot();

        $form = Livewire::test(ContractForm::class)->assertSee('How an installment sale works')
            ->call('pickProduct', $product->id)
            ->set('newCustomer', true)->set('form.name', 'Bilal')->set('form.phone', '03001234567')
            ->set('sale.down_payment', '8000')->set('sale.markup_percent', '20')->set('sale.installments', '6');
        $impact = $form->viewData('impact');

        $this->assertTrue($impact['ok'], (string) $impact['error']);
        $this->assertSame($before, $this->snapshot(), 'The preview must not create the customer or the plan.');
        $this->assertSame(8000, $this->effect($impact, 'Cash in hand goes up'));
        $this->assertSame(6000, $this->effect($impact, 'Markup to earn later goes up'));
        // Profit now is only the cash price over the cost: 38,000 - 30,000 (the oldest piece).
        $this->assertSame(8000, $impact['profit']);

        $last = (int) JournalLine::max('id');
        $form->call('save')->assertHasNoErrors();
        $this->assertSame($this->previewLines($impact), $this->linesSince($last));
    }

    public function test_taking_an_installment_and_cancelling_it_show_their_effect(): void
    {
        $product = $this->phone();
        Livewire::test(ContractForm::class)->call('pickProduct', $product->id)
            ->set('newCustomer', true)->set('form.name', 'Bilal')->set('form.phone', '03001234567')
            ->set('sale.down_payment', '8000')->set('sale.markup_percent', '20')->set('sale.installments', '6')->call('save');
        $contract = Contract::firstOrFail();

        $page = Livewire::test(ContractShow::class, ['contract' => $contract])->set('collect', true)->set('amount', '6000')->set('lateFee', '0')->assertSee('How taking an installment works');
        $before = $this->snapshot();
        $impact = $page->viewData('impact');
        $this->assertSame($before, $this->snapshot());
        $this->assertSame(6000, $this->effect($impact, 'Cash in hand goes up'));
        $this->assertSame(6000, $this->effect($impact, 'Installment customers owe you less'));
        $this->assertSame(1000, $this->effect($impact, 'Markup earned'));
        $this->assertSame(1000, $impact['profit']);
        $page->assertSee(rs($contract->fresh()->outstanding()));

        $last = (int) JournalLine::max('id');
        $page->call('takePayment')->assertHasNoErrors();
        $this->assertSame($this->previewLines($impact), $this->linesSince($last));

        $receipt = Receipt::where('is_down_payment', false)->firstOrFail();
        $page->set('voidId', $receipt->id)->assertSee('What cancelling a receipt does');
        $void = $page->viewData('impact');
        $this->assertSame(6000, $this->effect($void, 'Cash in hand goes down'));
        $this->assertSame(-1000, $void['profit']);
        $this->assertNull($receipt->fresh()->voided_at);
    }

    public function test_stock_in_shows_cash_out_and_stock_up_without_profit(): void
    {
        $product = $this->phone();
        $before = $this->snapshot();

        $page = Livewire::test(Products::class)->call('openStock', $product->id)->assertSee('How stock in works')
            ->set('purchase.quantity', '2')->set('purchase.unit_cost', '31000')->set('identifiers', "333333333333333\n444444444444444");
        $impact = $page->viewData('impact');

        $this->assertSame($before, $this->snapshot());
        $this->assertSame(62000, $this->effect($impact, 'Stock value goes up'));
        $this->assertSame(62000, $this->effect($impact, 'Cash in hand goes down'));
        $this->assertNull($impact['profit']);
        $this->assertSame(2, $impact['stock'][0]['pieces']);

        // A repeated IMEI is caught before saving.
        $page->set('identifiers', "111111111111111\n555555555555555");
        $this->assertFalse($page->viewData('impact')['ok']);

        $last = (int) JournalLine::max('id');
        $page->set('identifiers', "333333333333333\n444444444444444")->call('saveStock')->assertHasNoErrors();
        $this->assertSame($this->previewLines($impact), $this->linesSince($last));
    }

    public function test_every_guide_is_in_english_and_roman_urdu_line_for_line(): void
    {
        $this->assertSame(array_keys(Guides::ALL), array_keys(Guides::UR));
        foreach (Guides::ALL as $key => $english) {
            foreach ([1, 2, 3] as $part) {
                $this->assertCount(count($english[$part]), Guides::UR[$key][$part], "Guide $key, part $part");
            }
        }

        $this->get(route('biz.sales.new'))->assertOk()->assertSee('How a sale works')->assertSee('Sale kaise hoti hai')
            ->assertSee('Kaise bharein')->assertSee('Save karne se kya hoga');
    }

    public function test_vouchers_and_credit_sales_explain_what_they_do(): void
    {
        $rent = Account::where('code', '5300')->firstOrFail();
        $page = Livewire::test(Vouchers::class)->call('open', 'payment')->assertSee('How a payment voucher works')
            ->set('memo', 'Shop rent')->set('lines.0.account', (string) $rent->id)->set('lines.0.amount', '25000');
        $impact = $page->viewData('impact');
        $this->assertSame(0, JournalEntry::count());
        $this->assertSame(25000, $this->effect($impact, 'Rent goes up'));
        $this->assertSame(-25000, $impact['profit']);
        $page->call('post')->assertHasNoErrors();
        $this->assertSame($this->previewLines($impact), $this->linesSince(0));

        // Credit sale, then receiving the udhaar later.
        $product = $this->phone();
        $customer = Customer::create(['code' => 'C-0001', 'name' => 'Ali', 'phone' => '03009998877']);
        Livewire::test(SaleForm::class)->call('addProduct', $product->id)->call('pickCustomer', $customer->id)->set('paid', '0')->call('save')->assertHasNoErrors();
        $invoice = Invoice::firstOrFail();
        $show = Livewire::test(SaleShow::class, ['invoice' => $invoice])->call('openPay')->assertSee('How receiving a credit payment works');
        $receive = $show->viewData('impact');
        $this->assertSame(38000, $this->effect($receive, 'Customers owe you less on credit'));
        $this->assertNull($receive['profit']);
        $this->assertSame(38000, $invoice->fresh()->balance());
    }
}
