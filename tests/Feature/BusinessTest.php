<?php

namespace Tests\Feature;

use App\Livewire\Auth\Signup;
use App\Livewire\Biz\ContractForm;
use App\Livewire\Biz\ContractShow;
use App\Livewire\Biz\Vouchers;
use App\Models\AcademicSession;
use App\Models\Biz\Account;
use App\Models\Biz\Contract;
use App\Models\Biz\Customer;
use App\Models\Biz\JournalEntry;
use App\Models\Biz\Receipt;
use App\Models\Plan;
use App\Models\School;
use App\Models\User;
use App\Services\Biz\ChartOfAccounts;
use App\Services\Biz\Installments;
use App\Services\Biz\Ledger;
use App\Services\SchoolProvisioner;
use App\Services\SiteBuilder;
use App\Support\Navigation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class BusinessTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    private School $shop;

    protected function setUp(): void
    {
        parent::setUp();

        $this->owner = app(SchoolProvisioner::class)->create(['school' => 'Shop A', 'type' => 'business', 'name' => 'Owner', 'email' => 'shop@example.test', 'phone' => '03000000001', 'password' => 'password']);
        $this->shop = $this->owner->school;
    }

    private function customer(string $name = 'Aslam', ?School $shop = null): Customer
    {
        return Customer::withoutGlobalScopes()->create(['school_id' => ($shop ?? $this->shop)->id, 'code' => 'C-'.random_int(1000, 9999), 'name' => $name, 'phone' => '0301'.random_int(1000000, 9999999)]);
    }

    private function sale(array $overrides = [], ?Customer $customer = null): Contract
    {
        $this->actingAs($this->owner);

        return app(Installments::class)->sell($this->shop, $customer ?? $this->customer(), $overrides + [
            'item' => 'Honda CD 70', 'sale_date' => today()->subMonths(3)->toDateString(), 'cash_price' => 100000, 'cost_price' => 85000,
            'markup_percent' => 20, 'down_payment' => 20000, 'installments' => 6, 'frequency' => 'monthly',
            'first_due_on' => today()->subMonths(2)->toDateString(), 'late_fee' => 300, 'grace_days' => 3, 'rounding' => 100,
        ]);
    }

    private function assertBooksBalance(): void
    {
        $ledger = Ledger::for($this->shop);
        $accounts = $ledger->accounts();
        $this->assertSame($accounts->sum('total_debit'), $accounts->sum('total_credit'), 'Debits equal credits');
        $contracts = Contract::withoutGlobalScopes()->where('school_id', $this->shop->id)->with('schedule')->get();
        $this->assertSame((int) $contracts->where('status', '!=', 'written_off')->sum(fn ($c) => $c->outstanding()), $ledger->balanceOf('receivable'), 'Receivable matches the schedules');
        $this->assertSame((int) $contracts->where('status', '!=', 'written_off')->sum(fn ($c) => $c->markup - $c->markup_recognised), $ledger->balanceOf('deferred_markup'), 'Unearned markup matches the plans');
        $bs = $ledger->balanceSheet(today()->toDateString());
        $this->assertSame($bs['total_assets'], $bs['total_liabilities'] + $bs['total_equity'], 'Balance sheet balances');
    }

    public function test_plan_works_out_markup_installments_and_dates(): void
    {
        $plan = Installments::plan(100000, 20, null, 20000, 6, 'monthly', '2026-01-31', 100);

        $this->assertSame([16000, 116000, 96000, 16000], [$plan['markup'], $plan['total'], $plan['financed'], $plan['installment']]);
        $this->assertSame(96000, array_sum(array_column($plan['rows'], 'amount')));
        $this->assertSame('2026-02-28', $plan['rows'][1]['due_on']->toDateString()); // month-end stays in February

        $odd = Installments::plan(50000, 0, 7000, 0, 7, 'weekly', '2026-03-01', 50);
        $this->assertSame(57000, array_sum(array_column($odd['rows'], 'amount')));
        $this->assertSame(8150, $odd['installment']);
        $this->assertSame(8100, end($odd['rows'])['amount']);
        $this->assertSame('2026-03-08', $odd['rows'][1]['due_on']->toDateString());

        $this->expectException(\InvalidArgumentException::class);
        Installments::plan(1000, 10, null, 5000, 3, 'monthly', '2026-01-01');
    }

    public function test_sale_collections_cancelling_and_write_off_keep_the_books_right(): void
    {
        $contract = $this->sale();
        $this->assertSame('IS-0001', $contract->number);
        $this->assertSame(16000, $contract->markup);
        $this->assertCount(6, $contract->schedule);
        $this->assertSame(1, $contract->receipts->where('is_down_payment', true)->count());
        $this->assertBooksBalance();
        $this->assertSame(85000, Ledger::for($this->shop)->balanceOf('cogs'));

        // Two installments are late, so the counter suggests two late fees.
        $this->assertSame(32000, $contract->overdue());
        $this->assertSame(600, Installments::suggestedLateFee($contract));

        $service = app(Installments::class);
        $first = $service->collect($contract, 16000, 300, 0, today(), 'cash');
        $second = $service->collect($contract->fresh(), 20000, 0, 0, today(), 'jazzcash', 'TX123');
        $contract->refresh()->load('schedule');
        $this->assertSame(36000, $contract->paid());
        $this->assertSame(4000, $contract->schedule[2]->paid); // the extra went to the third installment
        $this->assertSame(intdiv(36000 * 16000, 96000), $contract->markup_recognised);
        $this->assertSame(300, Ledger::for($this->shop)->balanceOf('late_fee_income'));
        $this->assertSame(20000, Ledger::for($this->shop)->balanceOf('bank'));
        $this->assertBooksBalance();

        // More than is owed is refused.
        try {
            $service->collect($contract->fresh(), 70000);
            $this->fail('Overpayment was accepted.');
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('still owed', $e->getMessage());
        }

        // Cancelling the first receipt reverses it and recalculates the installments.
        $service->void($first, 'Entered twice');
        $contract->refresh()->load('schedule');
        $this->assertSame(20000, $contract->paid());
        $this->assertSame(16000, $contract->schedule[0]->paid);
        $this->assertNotNull($first->fresh()->entry->reversed_at);
        $this->assertBooksBalance();

        // Paying everything left completes the plan and earns all the markup.
        $service->collect($contract->fresh(), 75000, 0, 1000, today());
        $contract->refresh();
        $this->assertSame('completed', $contract->status);
        $this->assertSame(16000, $contract->markup_recognised);
        $this->assertSame(16000, Ledger::for($this->shop)->balanceOf('markup_income'));
        $this->assertSame(1000, Ledger::for($this->shop)->balanceOf('discount'));
        $this->assertBooksBalance();

        // A second plan that goes bad is written off: receivable cleared, unearned markup cancelled.
        $bad = $this->sale(['installments' => 4, 'down_payment' => 0]);
        $service->collect($bad, 10000);
        $service->writeOff($bad->fresh(), 'Customer left the city');
        $bad->refresh()->load('schedule');
        $this->assertSame('written_off', $bad->status);
        $this->assertSame(0, Ledger::for($this->shop)->balanceOf('deferred_markup'));
        $this->assertSame($bad->outstanding() - ($bad->markup - $bad->markup_recognised), Ledger::for($this->shop)->balanceOf('bad_debt'));
        $this->assertSame(0, Ledger::for($this->shop)->balanceOf('receivable'));
    }

    public function test_the_screens_sell_collect_and_print(): void
    {
        $this->actingAs($this->owner);
        foreach (Navigation::routes($this->owner) as $route) {
            $this->get(route($route))->assertOk();
        }

        // A sale with a new customer and a guarantor, from the form.
        $form = Livewire::test(ContractForm::class)
            ->set('newCustomer', true)->set('form.name', 'Shazia Bibi')->set('form.phone', '0302-2223344')->set('form.cnic', '36302-1234567-8')
            ->set('sale.item', 'Samsung A35')->set('sale.cash_price', '92000')->set('sale.down_payment', '15000')->set('sale.installments', '10')
            ->set('guarantors.0.name', 'Akbar')->set('guarantors.0.phone', '03451234567')
            ->assertSee('Rs 92,000');
        $form->call('save')->assertHasNoErrors();
        $contract = Contract::firstOrFail();
        $form->assertRedirect(route('biz.contract', $contract));
        $this->assertSame(['03022223344', 'Akbar', 15400], [$contract->customer->phone, $contract->guarantors->first()->name, $contract->markup]);

        // Taking a payment at the counter makes a receipt to print.
        $page = Livewire::test(ContractShow::class, ['contract' => $contract])->set('amount', '9000')->set('lateFee', '0')->call('takePayment')->assertHasNoErrors();
        $receipt = Receipt::where('is_down_payment', false)->firstOrFail();
        $page->assertSee($receipt->number);
        $page->set('amount', '999999')->call('takePayment')->assertHasErrors('amount');

        foreach ([route('biz.print.receipt', $receipt), route('biz.print.statement', $contract), route('biz.print.agreement', $contract), route('biz.print.voucher', $receipt->journal_entry_id), route('biz.contract', $contract), route('biz.customer', $contract->customer_id)] as $url) {
            $this->get($url)->assertOk()->assertSee('Shazia Bibi');
        }
        $this->get(route('biz.reports.collections'))->assertSee($receipt->number);
        $this->get(route('biz.reports.trial'))->assertSee('Balanced');
        $this->get(route('biz.reports.bs'))->assertSee('Balanced: assets equal liabilities plus equity.');

        // The reminder goes to the WhatsApp outbox.
        $page->call('remind')->assertDispatched('open-whatsapp');
        $this->assertDatabaseHas('messages', ['school_id' => $this->shop->id, 'category' => 'installment', 'phone' => '03022223344']);
    }

    public function test_vouchers_must_balance_and_are_reversed_not_deleted(): void
    {
        $this->actingAs($this->owner);
        $rent = Account::where('code', '5300')->firstOrFail();
        $cash = Account::where('system_key', 'cash')->firstOrFail();

        Livewire::test(Vouchers::class)->call('open', 'payment')->set('memo', 'Rent')->set('lines.0.account', (string) $rent->id)->set('lines.0.amount', '12000')
            ->call('post')->assertHasNoErrors();
        $this->assertSame(12000, Ledger::for($this->shop)->balanceOf('cash') * -1);

        // A journal says "goes up" / "goes down"; cash up is a debit, capital up is a credit.
        $journal = Livewire::test(Vouchers::class)->call('open', 'journal')->set('memo', 'Opening')
            ->set('lines.0.account', (string) $cash->id)->set('lines.0.side', 'up')->set('lines.0.amount', '50000')
            ->set('lines.1.account', (string) Account::where('system_key', 'capital')->value('id'))->set('lines.1.side', 'up')->set('lines.1.amount', '40000')
            ->assertSee('difference Rs 10,000')
            ->call('post')->assertHasErrors('lines');
        $journal->set('lines.1.amount', '50000')->assertSee('Both sides match')->call('post')->assertHasNoErrors();
        $opening = JournalEntry::where('memo', 'Opening')->firstOrFail()->lines;
        $this->assertSame([[50000, 0], [0, 50000]], $opening->map(fn ($l) => [(int) $l->debit, (int) $l->credit])->all());

        $entry = JournalEntry::where('kind', 'payment')->firstOrFail();
        Livewire::test(Vouchers::class)->set('reverseId', $entry->id)->set('reverseReason', 'Wrong month')->call('reverse')->assertHasNoErrors();
        $this->assertNotNull($entry->fresh()->reversed_at);
        // Opening cash 50,000; the rent paid and then reversed leaves it untouched, and rent back at nil.
        $this->assertSame(50000, Ledger::for($this->shop)->balanceOf('cash'));
        $this->assertSame(0, Ledger::for($this->shop)->accounts()->firstWhere('code', '5300')->balance);
        $this->assertBooksBalance();
    }

    public function test_schools_and_businesses_only_see_their_own_screens_and_data(): void
    {
        $school = app(SchoolProvisioner::class)->create(['school' => 'School B', 'type' => 'school', 'name' => 'Head', 'email' => 'head@example.test', 'phone' => '03000000002', 'password' => 'password']);
        $other = app(SchoolProvisioner::class)->create(['school' => 'Shop B', 'type' => 'business', 'name' => 'Other', 'email' => 'other@example.test', 'phone' => '03000000003', 'password' => 'password']);
        $contract = $this->sale();

        // Livewire's own requests (typing, uploads) must pass for a business.
        foreach (['default-livewire.update', 'livewire.upload-file', 'livewire.preview-file'] as $route) {
            $this->assertTrue($this->shop->allowsRoute($route), $route);
        }
        $this->assertFalse($this->shop->allowsRoute('settings'));
        $this->assertFalse($school->school->allowsRoute('biz.dashboard'));

        $this->assertSame('biz.dashboard', $this->owner->homeRoute());
        $this->assertSame('dashboard', $school->homeRoute());
        $this->actingAs($this->owner)->get(route('students.index'))->assertForbidden();
        $this->actingAs($this->owner)->get(route('dashboard'))->assertForbidden();
        $this->actingAs($school)->get(route('biz.dashboard'))->assertForbidden();
        $this->actingAs($school)->get(route('biz.contracts'))->assertForbidden();
        $this->actingAs($other)->get(route('biz.contract', $contract))->assertNotFound();
        $this->actingAs($other)->get(route('biz.print.receipt', $contract->receipts->first()))->assertNotFound();

        $accountant = User::create(['school_id' => $this->shop->id, 'name' => 'Acc', 'username' => 'acc@example.test', 'role' => 'accountant', 'password' => Hash::make('password')]);
        $this->actingAs($accountant)->get(route('biz.collect'))->assertOk();
        $this->actingAs($accountant)->get(route('biz.settings'))->assertForbidden();
        Livewire::actingAs($accountant)->test(ContractShow::class, ['contract' => $contract])->set('writeOffReason', 'x')->call('writeOff')->assertForbidden();
    }

    public function test_signup_plans_and_the_product_page(): void
    {
        Livewire::withQueryParams(['product' => 'business'])->test(Signup::class)->assertSet('type', 'business')
            ->set('kind', 'general')->set('school', 'New Motors')->set('name', 'Ali')->set('email', 'ali@example.test')->set('phone', '03001234567')->set('password', 'password1')
            ->call('register')->assertRedirect(route('biz.dashboard'));
        $shop = School::where('name', 'New Motors')->firstOrFail();
        $this->assertTrue($shop->isBusiness());
        $this->assertSame(count(ChartOfAccounts::DEFAULT), Account::withoutGlobalScopes()->where('school_id', $shop->id)->count());
        $this->assertSame(0, AcademicSession::withoutGlobalScopes()->where('school_id', $shop->id)->count());

        // The business plan only switches business features, and a school cannot be put on it.
        Plan::where('code', 'business')->firstOrFail()->update(['features' => ['installments', 'messages']]);
        Plan::where('code', 'business')->first()->applyTo($shop);
        $this->assertEqualsCanonicalizing(['sales', 'accounts', 'sms', 'website'], $shop->fresh()->features_off);
        $this->actingAs(User::where('username', 'ali@example.test')->first())->get(route('biz.vouchers'))->assertForbidden();

        $site = app(SiteBuilder::class)->platform();
        $this->assertNull(app(SiteBuilder::class)->addBusinessProductPage($site)); // already there, nothing added twice
        $this->get('/p/installments')->assertOk()->assertSee('Installment sales and accounts')->assertSee('Business: installment sales and recovery')
            ->assertDontSee('Attendance</span>', false)->assertSee('/signup?product=business', false);
    }
}
