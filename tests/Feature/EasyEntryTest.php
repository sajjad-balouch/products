<?php

namespace Tests\Feature;

use App\Livewire\Biz\EasyEntry;
use App\Livewire\Biz\Products;
use App\Models\Biz\Account;
use App\Models\Biz\Employee;
use App\Models\Biz\Vendor;
use App\Models\Biz\JournalEntry;
use App\Models\Biz\JournalLine;
use App\Models\Biz\Product;
use App\Models\Biz\ProductType;
use App\Models\User;
use App\Services\Biz\Ledger;
use App\Services\SchoolProvisioner;
use App\Support\Biz\EasyEntries;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/** "What happened?" entries: the user never chooses debit or credit, and the books still come out right. */
class EasyEntryTest extends TestCase
{
    use RefreshDatabase;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->owner = app(SchoolProvisioner::class)->create(['school' => 'Easy Mobiles', 'type' => 'business', 'kind' => 'mobile', 'name' => 'Owner', 'email' => 'e@example.test', 'phone' => '03000000001', 'password' => 'password']);
        $this->actingAs($this->owner);
    }

    /** The account that went up and the one that went down, by code. */
    private function sides(JournalEntry $entry): array
    {
        $lines = $entry->lines()->with('account')->get();

        return [$lines->firstWhere('debit', '>', 0)->account->code, $lines->firstWhere('credit', '>', 0)->account->code];
    }

    public function test_the_page_lists_every_event_in_english_and_urdu(): void
    {
        $page = $this->get(route('biz.entry'))->assertOk()->assertSee('New entry: what happened?');
        foreach (EasyEntries::ALL as $e) {
            $page->assertSee($e[6][0])->assertSee($e[7][0]);
        }
        $this->get(route('biz.vouchers'))->assertSee('New entry · Kya hua?');
    }

    /** @return array<string, array{0: string, 1: string, 2: string}> event => [money, debit code, credit code] */
    public static function events(): array
    {
        return [
            'owner money in, to bank' => ['capital_in', 'bank', '1010', '3000'],
            'loan in, to cash' => ['loan_in', 'cash', '1000', '2200'],
            'other income' => ['other_income', 'cash', '1000', '4900'],
            'pay supplier' => ['pay_supplier', 'cash', '2000', '1000'],
            'repay loan from bank' => ['loan_repay', 'bank', '2200', '1010'],
            'owner takes money' => ['owner_takes', 'cash', '3100', '1000'],
            'buy equipment' => ['buy_equipment', 'cash', '1500', '1000'],
            'advance given' => ['advance_given', 'cash', '1300', '1000'],
            'cash to bank' => ['cash_to_bank', 'cash', '1010', '1000'],
            'bank to cash' => ['bank_to_cash', 'cash', '1000', '1010'],
            'opening money in bank' => ['opening_money', 'bank', '1010', '3000'],
            'opening supplier dues' => ['opening_payable', 'cash', '3000', '2000'],
            'opening loan' => ['opening_loan', 'cash', '3000', '2200'],
            'salary' => ['salary', 'cash', '5200', '1000'],
            'cash borrowed from a vendor' => ['vendor_loan', 'bank', '1010', '2000'],
            'staff advance' => ['staff_advance', 'bank', '1350', '1010'],
        ];
    }

    /** @dataProvider events */
    public function test_each_event_posts_the_right_sides_and_matches_its_preview(string $type, string $money, string $debit, string $credit): void
    {
        $form = Livewire::test(EasyEntry::class)->call('choose', $type)->set('money', $money)->set('amount', '25000')->set('note', 'test');
        // Supplier entries are on a vendor's own account, salaries on an employee's.
        $who = match (EasyEntries::get($type)['pick']) {
            'vendor' => Vendor::create(['code' => 'V-0001', 'name' => 'Bilal Traders']),
            'employee' => Employee::create(['code' => 'E-001', 'name' => 'Asif']),
            default => null,
        };
        if ($who) {
            $form->set('pick', (string) $who->id);
        }
        $impact = $form->viewData('impact');
        $this->assertTrue($impact['ok'], (string) ($impact['error'] ?? $impact['pending'] ?? ''));
        $this->assertSame(0, JournalEntry::count(), 'The preview saves nothing.');

        $form->call('save')->assertHasNoErrors()->assertSee('Saved as');
        $entry = JournalEntry::firstOrFail();
        $this->assertSame([$debit, $credit], $this->sides($entry));
        $this->assertSame(25000, $entry->total());
        $this->assertSame(EasyEntries::get($type)['en'][0].($who ? ' · '.$who->name : '').': test', $entry->memo);
        if ($who instanceof Vendor) {
            $this->assertSame($type === 'pay_supplier' ? -25000 : 25000, $who->balance());
        }
        $this->assertSame(array_map(fn ($l) => [$l['code'], $l['debit'], $l['credit']], $impact['lines']),
            JournalLine::with('account')->orderBy('id')->get()->map(fn ($l) => [$l->account->code, (int) $l->debit, (int) $l->credit])->all());

        $accounts = Ledger::for($this->owner->school)->accounts();
        $this->assertSame($accounts->sum('total_debit'), $accounts->sum('total_credit'));
    }

    public function test_an_expense_asks_what_it_was_for_and_lowers_profit(): void
    {
        $form = Livewire::test(EasyEntry::class)->call('choose', 'expense')->set('amount', '18000');
        $this->assertSame('Choose from the list.', $form->viewData('impact')['pending']);
        $form->call('save')->assertHasErrors('pick');

        // Only everyday expenses can be picked, not the ones the system posts itself.
        $form->assertSee('Rent')->assertDontSee('Cost of goods sold');
        $rent = Account::where('code', '5300')->value('id');
        $form->set('pick', (string) $rent);
        $impact = $form->viewData('impact');
        $this->assertSame(-18000, $impact['profit']);
        $form->call('save')->assertHasNoErrors();
        $this->assertSame(['5300', '1000'], $this->sides(JournalEntry::firstOrFail()));
        $this->assertSame(-18000, Ledger::for($this->owner->school)->balanceOf('cash'));
    }

    public function test_stock_already_in_the_shop_counts_as_capital_not_cash_spent(): void
    {
        $product = Product::create(['type_id' => ProductType::where('name', 'Accessory / general')->value('id'), 'brand' => 'Faster', 'name' => 'Charger', 'sale_price' => 650]);
        Livewire::test(Products::class)->call('openStock', $product->id)->assertSee('Already in the shop (opening stock)')
            ->set('purchase.quantity', '40')->set('purchase.unit_cost', '400')->set('purchase.paid_by', 'opening')
            ->call('saveStock')->assertHasNoErrors();

        $entry = JournalEntry::firstOrFail();
        $this->assertSame('journal', $entry->kind);
        $this->assertSame(['1200', '3000'], $this->sides($entry));
        $ledger = Ledger::for($this->owner->school);
        $this->assertSame(16000, $ledger->balanceOf('inventory'));
        $this->assertSame(0, $ledger->balanceOf('cash'));
    }
}
