<?php

namespace Database\Seeders;

use App\Models\Biz\Brand;
use App\Models\Biz\BrandModel;
use App\Models\Biz\Category;
use App\Models\Biz\Contract;
use App\Models\Biz\Customer;
use App\Models\Biz\Product;
use App\Models\Biz\ProductType;
use App\Models\Plan;
use App\Models\User;
use App\Services\Biz\Installments;
use App\Services\Biz\Ledger;
use App\Services\Biz\Sales;
use App\Services\Biz\Stock;
use App\Services\SchoolProvisioner;
use Illuminate\Database\Seeder;

/**
 * A demo shop selling on installments, with a few months of sales and payments,
 * some customers on time and some late. Login: shop@darsgah.test / password.
 */
class DemoBusinessSeeder extends Seeder
{
    public function run(): void
    {
        if (User::where('username', 'shop@darsgah.test')->exists()) {
            return;
        }

        $owner = app(SchoolProvisioner::class)->create(['school' => 'Al-Madina Electronics & Motors', 'type' => 'business', 'kind' => 'general', 'name' => 'Imran Sheikh', 'email' => 'shop@darsgah.test', 'phone' => '03004445566', 'password' => 'password']);
        $shop = $owner->school;
        $shop->update(['address' => 'Main Bazaar, Multan', 'access_until' => now()->addMonths(2)]);
        Plan::where('code', 'business')->first()?->applyTo($shop);
        User::create(['school_id' => $shop->id, 'name' => 'Bilal Accounts', 'username' => 'shopaccounts@darsgah.test', 'email' => 'shopaccounts@darsgah.test', 'role' => 'accountant', 'password' => $owner->password]);

        $ledger = Ledger::for($shop);
        $start = today()->subMonthsNoOverflow(8)->startOfMonth();
        $ledger->post('receipt', $start, 'Capital put in by the owner', [['account' => 'capital', 'credit' => 2500000], ['account' => 'cash', 'debit' => 500000], ['account' => 'bank', 'debit' => 2000000]]);
        // Products, and stock bought in batches. The Honda and the Samsung were bought twice at different prices.
        $types = ProductType::withoutGlobalScopes()->where('school_id', $shop->id)->pluck('id', 'name');
        $stock = app(Stock::class);
        // key => [type, category, brand, model, variant, retail, wholesale, batches [months ago, pieces, cost, supplier]]
        $catalog = [
            'honda' => ['Motorcycle', 'Motorcycles', 'Honda', 'CD 70', '2025', 189000, 0, [[8, 1, 160000, 'Atlas Honda dealer'], [3, 2, 168000, 'Atlas Honda dealer']]],
            'samsung' => ['Mobile phone', 'Mobile phones', 'Samsung', 'Galaxy A35', '8/256', 92000, 88000, [[8, 2, 76000, 'Hall Road Lahore'], [2, 2, 79000, 'Hall Road Lahore']]],
            'infinix' => ['Mobile phone', 'Mobile phones', 'Infinix', 'Hot 40', '8/128', 38500, 36500, [[2, 6, 33000, 'Hall Road Lahore']]],
            'fridge' => ['Electronics / appliance', 'Home appliances', 'Haier', 'Refrigerator', 'HRF-398 14 cu ft', 145000, 0, [[8, 2, 121000, 'Haier distributor']]],
            'tv' => ['Electronics / appliance', 'Electronics', 'TCL', 'LED TV 55"', 'Android 55P635', 165000, 0, [[8, 2, 138000, 'TCL distributor']]],
            'washer' => ['Electronics / appliance', 'Home appliances', 'Dawlance', 'Washing machine', 'DWT-255', 98000, 0, [[8, 1, 82000, 'Dawlance distributor']]],
            'yamaha' => ['Motorcycle', 'Motorcycles', 'Yamaha', 'YBR 125G', null, 485000, 0, [[8, 1, 430000, 'Yamaha 3S dealer']]],
            'ac' => ['Electronics / appliance', 'Home appliances', 'Gree', 'Inverter AC 1.5 ton', '18PITH', 210000, 0, [[8, 2, 178000, 'Gree distributor']]],
            'charger' => ['Accessory / general', 'Chargers and cables', 'Faster', 'Charger', '20W fast', 650, 450, [[3, 200, 320, 'Shah Alam Market'], [1, 100, 350, 'Shah Alam Market']]],
            'cover' => ['Accessory / general', 'Covers and protectors', 'Samsung', 'Original cable', 'USB-C 1m', 900, 700, [[2, 50, 480, 'Shah Alam Market']]],
        ];
        $categories = Category::withoutGlobalScopes()->where('school_id', $shop->id)->pluck('id', 'name');
        $products = [];
        $imei = 356700000000000;
        foreach ($catalog as $key => [$type, $category, $brandName, $modelName, $variant, $price, $wholesale, $batches]) {
            $brand = Brand::withoutGlobalScopes()->firstOrCreate(['school_id' => $shop->id, 'type_id' => $types[$type] ?? null, 'name' => $brandName]);
            $model = BrandModel::withoutGlobalScopes()->firstOrCreate(['school_id' => $shop->id, 'brand_id' => $brand->id, 'name' => $modelName]);
            $products[$key] = Product::create(['school_id' => $shop->id, 'type_id' => $types[$type] ?? null, 'category_id' => $categories[$category] ?? null, 'brand_id' => $brand->id, 'model_id' => $model->id,
                'brand' => $brand->name, 'name' => $model->name, 'variant' => $variant, 'sale_price' => $price, 'wholesale_price' => $wholesale]);
            foreach ($batches as [$monthsAgo, $quantity, $cost, $supplier]) {
                // Phones come in with their IMEIs.
                $units = [];
                for ($n = 0; $type === 'Mobile phone' && $n < $quantity; $n++) {
                    $units[] = ['imei' => (string) ($imei += 17), 'colour' => 'Black'];
                }
                $stock->purchase($shop, $products[$key], ['date' => today()->subMonthsNoOverflow($monthsAgo)->startOfMonth()->addDays(2)->toDateString(), 'quantity' => $quantity, 'unit_cost' => $cost, 'supplier' => $supplier, 'paid_by' => 'bank'], $units);
            }
        }

        // [name, father, CNIC, phone, product, details, months ago, down payment, installments, share of due installments paid]
        $people = [
            ['Muhammad Aslam', 'Ghulam Rasool', '36302-1111111-1', '03011112233', 'honda', ['engine_no' => '7123456', 'chassis_no' => 'CD70-55123', 'colour' => 'Red'], 7, 40000, 12, 1.0],
            ['Shazia Bibi', 'Abdul Majeed', '36302-2222222-2', '03022223344', 'samsung', ['colour' => 'Navy'], 6, 15000, 10, 1.0],
            ['Rashid Mehmood', 'Noor Muhammad', '36302-3333333-3', '03033334455', 'fridge', ['serial_no' => 'HR998877'], 5, 25000, 12, 0.5],
            ['Kashif Ali', 'Sardar Ali', '36302-4444444-4', '03044445566', 'tv', ['serial_no' => 'TC5566'], 4, 30000, 12, 0.2],
            ['Nasreen Akhtar', 'Muhammad Ashraf', '36302-5555555-5', '03055556677', 'washer', ['serial_no' => 'DW2255'], 3, 20000, 8, 1.0],
            ['Tariq Javed', 'Javed Iqbal', '36302-6666666-6', '03066667788', 'yamaha', ['engine_no' => 'YB125-883', 'chassis_no' => 'YBR-99812', 'colour' => 'Black'], 2, 120000, 18, 0.0],
            ['Sadia Noreen', 'Khalid Mehmood', '36302-7777777-7', '03077778899', 'ac', ['serial_no' => 'GR1855'], 1, 50000, 12, 0.8],
            ['Waqas Ahmed', 'Bashir Ahmed', '36302-8888888-8', '03088889900', 'honda', ['engine_no' => '7129981', 'chassis_no' => 'CD70-58810', 'colour' => 'Black'], 1, 40000, 12, 1.0],
        ];

        $installments = app(Installments::class);
        foreach ($people as $i => [$name, $father, $cnic, $phone, $productKey, $details, $monthsAgo, $down, $count, $payRate]) {
            $customer = Customer::create(['school_id' => $shop->id, 'code' => 'C-'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT), 'name' => $name, 'father_name' => $father, 'cnic' => $cnic, 'phone' => $phone, 'address' => ['Gulgasht Colony', 'Shah Rukn-e-Alam', 'Cantt', 'Mumtazabad', 'New Multan', 'Bosan Road', 'Model Town', 'Wapda Town'][$i].', Multan', 'occupation' => ['Shopkeeper', 'Teacher', 'Driver', 'Mechanic', 'Tailor', 'Government employee', 'Nurse', 'Electrician'][$i]]);
            $sold = today()->subMonthsNoOverflow($monthsAgo)->startOfMonth()->addDays(4 + $i);
            $product = $products[$productKey];

            $contract = $installments->sell($shop, $customer, [
                'product_id' => $product->id, 'details' => $details, 'item' => $product->label(), 'sale_date' => $sold->toDateString(), 'cash_price' => $product->sale_price,
                'markup_percent' => 20, 'down_payment' => $down, 'installments' => $count, 'frequency' => 'monthly',
                'first_due_on' => $sold->copy()->addMonthNoOverflow()->toDateString(), 'late_fee' => 300, 'grace_days' => 3, 'rounding' => 100, 'method' => 'cash',
            ], [['name' => 'Akbar '.explode(' ', $name)[1], 'father_name' => $father, 'cnic' => '36302-90000'.$i.'0-1', 'phone' => '0345'.str_pad((string) (7000000 + $i), 7, '0', STR_PAD_LEFT), 'relation' => 'Brother', 'address' => 'Multan']]);

            // Pay the share of installments already due that this customer usually pays, a few days late at times.
            $due = $contract->schedule->filter(fn ($s) => $s->due_on->lt(today()));
            foreach ($due->take((int) round($due->count() * $payRate)) as $n => $s) {
                $paidOn = $s->due_on->copy()->addDays($n % 3 === 2 ? 6 : 1);
                if ($paidOn->gt(today())) {
                    break;
                }
                $installments->collect(Contract::find($contract->id), $s->amount, $n % 3 === 2 ? 300 : 0, 0, $paidOn, $n % 2 ? 'jazzcash' : 'cash');
            }
        }

        // Wholesale to a shopkeeper on part credit (udhaar), a later payment, and a walk-in cash sale.
        $sales = app(Sales::class);
        $dealer = Customer::create(['school_id' => $shop->id, 'code' => 'C-0009', 'name' => 'Ali Mobile Zone', 'father_name' => 'Ali Raza', 'phone' => '03091112233', 'address' => 'Hussain Agahi, Multan', 'occupation' => 'Mobile shop']);
        $wholesale = $sales->create($shop, $dealer, null, today()->subMonthsNoOverflow(1)->startOfMonth()->addDays(5)->toDateString(), [
            ['product_id' => $products['charger']->id, 'quantity' => 80, 'unit_price' => 450],
            ['product_id' => $products['cover']->id, 'quantity' => 20, 'unit_price' => 700],
            ['product_id' => $products['infinix']->id, 'quantity' => 2, 'unit_price' => 36500],
        ], 0, 60000, 'cash', 'Wholesale');
        $sales->receive($wholesale, 25000, today()->subDays(10)->toDateString(), 'jazzcash', 'JC-88123');
        $sales->create($shop, null, 'Walk-in', today()->subDays(2)->toDateString(), [
            ['product_id' => $products['infinix']->id, 'quantity' => 1, 'unit_price' => 38500],
            ['product_id' => $products['charger']->id, 'quantity' => 3, 'unit_price' => 650],
        ], 450, 40000, 'cash');

        foreach (range(7, 0) as $ago) {
            $month = today()->subMonthsNoOverflow($ago)->startOfMonth();
            $ledger->post('payment', $month->copy()->addDays(1), 'Shop rent '.$month->format('F'), [['account' => '5300', 'debit' => 12000], ['account' => 'cash', 'credit' => 12000]]);
            if ($month->copy()->endOfMonth()->lt(today())) {
                $ledger->post('payment', $month->copy()->endOfMonth(), 'Salaries '.$month->format('F'), [['account' => '5200', 'debit' => 15000], ['account' => 'cash', 'credit' => 15000]]);
            }
        }
    }
}
