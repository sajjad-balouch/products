<?php

namespace Tests\Feature;

use App\Livewire\Biz\ContractForm;
use App\Livewire\Biz\EmployeeShow;
use App\Livewire\Biz\Employees;
use App\Livewire\Biz\Products;
use App\Livewire\Biz\SaleForm;
use App\Livewire\Biz\VendorShow;
use App\Livewire\Biz\Vendors;
use App\Models\Biz\Contract;
use App\Models\Biz\Employee;
use App\Models\Biz\Invoice;
use App\Models\Biz\JournalEntry;
use App\Models\Biz\Product;
use App\Models\Biz\ProductType;
use App\Models\Biz\Vendor;
use App\Models\User;
use App\Services\Biz\Ledger;
use App\Services\SchoolProvisioner;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** Vendors with their own account, employees with salary and advances, salesmen with commission. */
class VendorsEmployeesTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = app(SchoolProvisioner::class)->create(['school' => 'People Mobiles', 'type' => 'business', 'kind' => 'mobile', 'name' => 'Owner', 'email' => 'p@example.test', 'phone' => '03000000001', 'password' => 'password']);
        $this->actingAs($this->owner);
    }

    private function charger(): Product
    {
        return Product::create(['type_id' => ProductType::where('name', 'Accessory / general')->value('id'), 'brand' => 'Faster', 'name' => 'Charger', 'sale_price' => 1000]);
    }

    private function assertBooksBalance(): void
    {
        $accounts = Ledger::for($this->owner->school)->accounts();
        $this->assertSame($accounts->sum('total_debit'), $accounts->sum('total_credit'));
    }

    public function test_a_vendor_has_an_account_of_credit_stock_payments_and_old_dues(): void
    {
        Livewire::test(Vendors::class)->call('openForm')->assertSee('How adding a vendor works')->set('form.name', 'Bilal Traders')->set('form.phone', '0300-1112233')->call('save')->assertHasNoErrors();
        $vendor = Vendor::firstOrFail();
        $this->assertSame(['V-0001', '03001112233'], [$vendor->code, $vendor->phone]);

        // Stock on credit needs the vendor; then it is owed to that vendor.
        $charger = $this->charger();
        $stock = Livewire::test(Products::class)->call('openStock', $charger->id)->set('purchase.quantity', '20')->set('purchase.unit_cost', '400')->set('purchase.paid_by', 'credit')
            ->call('saveStock')->assertHasErrors('purchase.vendor_id');
        $stock->set('purchase.vendor_id', (string) $vendor->id)->call('saveStock')->assertHasNoErrors();
        $this->assertSame(8000, $vendor->balance());

        // A vendor typed on the stock-in box is added on the spot.
        Livewire::test(Products::class)->call('openStock', $charger->id)->set('newVendor', 'Hafeez & Sons')->call('addVendor')
            ->assertSet('purchase.vendor_id', (string) Vendor::where('name', 'Hafeez & Sons')->value('id'));

        // Paying shows its effect first, then lowers what is owed.
        $page = Livewire::test(VendorShow::class, ['vendor' => $vendor])->assertSee('Rs 8,000')->call('open', 'pay')->assertSet('amount', '8000')->set('amount', '5000');
        $impact = $page->viewData('impact');
        $this->assertSame(0, JournalEntry::where('kind', 'payment')->where('memo', 'like', 'Paid %')->count());
        $this->assertContains(['tone' => 'info', 'text' => 'You will owe Bilal Traders less', 'amount' => 5000], $impact['effects']);
        $page->call('save')->assertHasNoErrors();
        $this->assertSame(3000, $vendor->balance());

        Livewire::test(VendorShow::class, ['vendor' => $vendor])->call('open', 'opening')->set('amount', '12000')->call('save')->assertHasNoErrors();
        $this->assertSame(15000, $vendor->balance());
        $this->assertSame(15000, (int) Vendor::balances()->sum() - (int) Vendor::balances([Vendor::where('name', 'Hafeez & Sons')->value('id')])->sum());
        $this->assertSame(15000, Ledger::for($this->owner->school)->balanceOf('payable'));
        $this->get(route('biz.vendors'))->assertOk()->assertSee('Bilal Traders')->assertSee('Rs 15,000');
        $this->get(route('biz.vendor', $vendor))->assertOk()->assertSee('Old balance owed to Bilal Traders');
        $this->assertBooksBalance();

        // Another business cannot open this vendor.
        $other = app(SchoolProvisioner::class)->create(['school' => 'Other', 'type' => 'business', 'kind' => 'mobile', 'name' => 'O', 'email' => 'o@example.test', 'phone' => '03000000002', 'password' => 'password']);
        $this->actingAs($other)->get(route('biz.vendor', $vendor))->assertNotFound();
    }

    public function test_salary_advances_and_salesman_commission(): void
    {
        Livewire::test(Employees::class)->call('openForm', null, true)->assertSet('form.is_salesman', true)
            ->set('form.name', 'Asif')->set('form.salary', '30000')->set('form.commission_percent', '2')->call('save')->assertHasNoErrors();
        $asif = Employee::firstOrFail();
        $this->assertSame(['E-001', true, 30000, 2.0], [$asif->code, $asif->is_salesman, $asif->salary, $asif->commission_percent]);

        // He sells: a bill and an installment plan, both named on him.
        $charger = $this->charger();
        app(\App\Services\Biz\Stock::class)->purchase($this->owner->school, $charger, ['date' => today()->toDateString(), 'quantity' => 10, 'unit_cost' => 400]);
        Livewire::test(SaleForm::class)->assertSee('Salesman')->call('addProduct', $charger->id)->set('lines.0.quantity', '5')->set('salesmanId', (string) $asif->id)->call('save')->assertHasNoErrors();
        $this->assertSame($asif->id, Invoice::firstOrFail()->salesman_id);
        Livewire::test(ContractForm::class)->set('newCustomer', true)->set('form.name', 'Bilal')->set('form.phone', '03001234567')
            ->set('sale.item', 'Phone')->set('sale.cash_price', '45000')->set('sale.installments', '6')->set('sale.salesman_id', (string) $asif->id)
            ->call('save')->assertHasNoErrors();
        $this->assertSame($asif->id, Contract::firstOrFail()->salesman_id);
        $sales = $asif->salesBetween(today()->startOfMonth()->toDateString(), today()->toDateString());
        $this->assertSame([5000, 45000, 50000, 1000], [$sales['bills_total'], $sales['plans_total'], $sales['total'], $sales['commission']]);

        // Advance, then a salary that takes part of it back.
        $page = Livewire::test(EmployeeShow::class, ['employee' => $asif]);
        $page->call('open', 'advance')->set('amount', '5000')->call('save')->assertHasNoErrors();
        $this->assertSame(5000, $asif->advance());
        $page->call('open', 'salary')->assertSet('amount', '30000')->assertSet('deduct', '5000')->set('deduct', '3000');
        $impact = $page->viewData('impact');
        $this->assertSame(-30000, $impact['profit']);
        $this->assertContains(['tone' => 'info', 'text' => 'Asif will owe you less (advance)', 'amount' => 3000], $impact['effects']);
        $page->call('save')->assertHasNoErrors();
        $this->assertSame(2000, $asif->advance());
        $this->assertSame(30000, $asif->paid('salaries'));

        // Commission starts at what was earned and not yet paid.
        $page->call('open', 'commission')->assertSet('amount', '1000')->call('save')->assertHasNoErrors();
        $this->assertSame(1000, $asif->paid('commission'));
        $page->call('open', 'commission')->assertSet('amount', '0');

        // Cash: +5,000 sale, -5,000 advance, -27,000 salary handed over, -1,000 commission (the purchase was cash too).
        $this->assertSame(5000 - 5000 - 27000 - 1000 - 4000, Ledger::for($this->owner->school)->balanceOf('cash'));
        $this->assertBooksBalance();

        $this->get(route('biz.employees'))->assertOk()->assertSee('Asif')->assertSee('Rs 30,000');
        $this->get(route('biz.reports.salesmen'))->assertOk()->assertSee('Asif')->assertSee('Rs 50,000')->assertSee('Rs 1,000');
        $this->get(route('biz.employee', $asif))->assertOk()->assertSee('Salary for '.today()->format('F Y'));
    }
    public function test_stock_part_paid_cash_borrowed_and_a_part_paid_bill_all_go_on_the_vendor_account(): void
    {
        $vendor = Vendor::create(['code' => 'V-0001', 'name' => 'Bilal Traders']);
        $charger = $this->charger();
        $ledger = Ledger::for($this->owner->school);

        // 20 chargers at 400 = 8,000: 5,000 paid in cash now, 3,000 owed. Without a vendor the rest has nobody to be owed to.
        $stock = Livewire::test(Products::class)->call('openStock', $charger->id)->set('purchase.quantity', '20')->set('purchase.unit_cost', '400')
            ->set('purchase.vendor_id', '')->set('purchase.paid_by', 'cash')->set('purchase.paid_now', '5000')
            ->assertSee('Rs 3,000 baqi')->call('saveStock')->assertHasErrors('purchase.vendor_id');
        $stock->set('purchase.vendor_id', (string) $vendor->id);
        $impact = $stock->viewData('impact');
        $this->assertContains(['tone' => 'info', 'text' => 'You will owe Bilal Traders more', 'amount' => 3000], $impact['effects']);
        $stock->call('saveStock')->assertHasNoErrors();
        $this->assertSame([3000, -5000, 8000], [$vendor->balance(), $ledger->balanceOf('cash'), $ledger->balanceOf('inventory')]);
        $this->assertSame('Cash 5,000 + owed 3,000', \App\Models\Biz\Purchase::firstOrFail()->paymentLabel());
        Livewire::test(Products::class)->call('openStock', $charger->id)->set('purchase.paid_now', '9000')->call('saveStock')->assertHasErrors('purchase.paid_now');

        // The vendor lends 10,000 cash.
        $page = Livewire::test(VendorShow::class, ['vendor' => $vendor])->call('open', 'loan')->assertSee('How cash borrowed from a vendor works')->set('amount', '10000');
        $page->call('save')->assertHasNoErrors();
        $this->assertSame([13000, 5000], [$vendor->balance(), $ledger->balanceOf('cash')]);

        // Their repair bill of 6,000: 2,000 paid now, 4,000 owed; the whole bill is an expense.
        $rent = \App\Models\Biz\Account::where('code', '5300')->value('id');
        $bill = Livewire::test(VendorShow::class, ['vendor' => $vendor])->call('open', 'bill')->set('amount', '6000')->set('paidNow', '2000');
        $bill->call('save')->assertHasErrors('expense');
        $bill->set('expense', (string) $rent);
        $this->assertSame(-6000, $bill->viewData('impact')['profit']);
        $bill->call('save')->assertHasNoErrors();
        $this->assertSame([17000, 3000, 6000], [$vendor->balance(), $ledger->balanceOf('cash'), (int) \App\Models\Biz\JournalLine::where('account_id', $rent)->sum('debit')]);

        // Fully on credit with nothing paid now is fine too.
        Livewire::test(VendorShow::class, ['vendor' => $vendor])->call('open', 'bill')->set('amount', '1500')->set('expense', (string) $rent)->call('save')->assertHasNoErrors();
        $this->assertSame(18500, $vendor->balance());

        $this->get(route('biz.vendor', $vendor))->assertOk()->assertSee('Cash borrowed from Bilal Traders')->assertSee('Bill from Bilal Traders')->assertSee('Rs 18,500');
        $this->assertBooksBalance();
    }
}
