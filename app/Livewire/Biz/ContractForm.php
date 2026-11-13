<?php

namespace App\Livewire\Biz;

use App\Livewire\Biz\Concerns\InBusiness;
use App\Livewire\Biz\Concerns\ShowsImpact;
use App\Models\Biz\Contract;
use App\Models\Biz\Customer;
use App\Models\Biz\Employee;
use App\Models\Biz\Product;
use App\Models\Biz\Receipt;
use App\Models\Biz\StockUnit;
use App\Services\Biz\Installments;
use App\Services\Biz\Numbers;
use App\Support\Phone;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Title('New installment sale')]
class ContractForm extends Component
{
    use InBusiness, ShowsImpact;

    #[Url(as: 'customer')]
    public ?int $customerId = null;

    public string $customerSearch = '';

    public bool $newCustomer = false;

    public array $form = Customers::FIELDS;

    public array $sale = [];

    public array $guarantors = [];

    public bool $confirmBlacklisted = false;

    /** Guarantors are filled in a pop-up, so the plan fits on one screen. */
    public bool $showGuarantors = false;

    // From the product list
    #[Url(as: 'product')]
    public ?int $productId = null;

    public string $productSearch = '';

    public ?int $stockUnitId = null;

    /** The type's details for the piece sold: engine no., IMEI... */
    public array $details = [];

    public function pickProduct(int $id): void
    {
        $product = Product::where('is_active', true)->findOrFail($id);
        $this->productId = $product->id;
        $this->productSearch = '';
        $this->sale['item'] = $product->label();
        $this->sale['cash_price'] = $product->sale_price ? (string) $product->sale_price : $this->sale['cash_price'];
        $this->stockUnitId = $product->units()->where('status', 'in_stock')->orderBy('id')->value('id');
        $this->updatedStockUnitId();
    }

    public function clearProduct(): void
    {
        $this->productId = $this->stockUnitId = null;
        $this->details = [];
        $this->sale['item'] = '';
        $this->sale['cost_price'] = '';
    }

    /** Choosing a piece brings in its cost and the details written when it came in. */
    public function updatedStockUnitId(): void
    {
        $unit = $this->stockUnitId ? StockUnit::where('product_id', $this->productId)->where('status', 'in_stock')->find($this->stockUnitId) : null;
        $this->stockUnitId = $unit?->id;
        $this->details = $unit?->details ?? [];
        $this->sale['cost_price'] = $unit ? (string) $unit->unit_cost : '';
    }

    public function mount(): void
    {
        $s = Installments::settings($this->business());
        $this->sale = [
            'item' => '', 'item_details' => '', 'sale_date' => today()->toDateString(),
            'cash_price' => '', 'cost_price' => '', 'markup_percent' => (string) $s['markup_percent'], 'markup' => '',
            'down_payment' => '', 'installments' => (string) $s['installments'], 'frequency' => $s['frequency'],
            'first_due_on' => today()->addMonthNoOverflow()->toDateString(), 'late_fee' => (string) $s['late_fee'],
            'grace_days' => (string) $s['grace_days'], 'rounding' => (string) $s['rounding'], 'method' => 'cash', 'notes' => '', 'salesman_id' => '',
        ];
        $blank = ['name' => '', 'father_name' => '', 'cnic' => '', 'phone' => '', 'address' => '', 'relation' => ''];
        $this->guarantors = [$blank, $blank];

        if ($this->productId) {
            Product::find($this->productId) ? $this->pickProduct($this->productId) : $this->productId = null;
        }
    }

    public function updatedSaleFrequency(): void
    {
        $date = today();
        $first = match ($this->sale['frequency']) {
            'weekly' => $date->addWeek(), 'fortnightly' => $date->addDays(14), default => $date->addMonthNoOverflow(),
        };
        $this->sale['first_due_on'] = $first->toDateString();
    }

    public function pickCustomer(int $id): void
    {
        $this->customerId = Customer::findOrFail($id)->id;
        $this->customerSearch = '';
        $this->newCustomer = false;
        $this->confirmBlacklisted = false;
    }

    /** @return array<string, mixed>|null */
    private function preview(): ?array
    {
        $s = $this->sale;
        if ((int) $s['cash_price'] <= 0 || (int) $s['installments'] < 1 || ! $s['first_due_on']) {
            return null;
        }

        try {
            return Installments::plan((int) $s['cash_price'], (float) $s['markup_percent'], $s['markup'] !== '' ? (int) $s['markup'] : null,
                (int) $s['down_payment'], min(120, (int) $s['installments']), $s['frequency'], $s['first_due_on'], (int) ($s['rounding'] ?: 1));
        } catch (\Throwable) {
            return null;
        }
    }

    private function rules(): array
    {
        $rules = [
            'sale.item' => 'required|string|max:150',
            'sale.item_details' => 'nullable|string|max:1000',
            'sale.sale_date' => 'required|date',
            'sale.cash_price' => 'required|integer|min:1|max:1000000000',
            'sale.cost_price' => 'nullable|integer|min:0|max:1000000000',
            'sale.markup_percent' => 'required|numeric|min:0|max:500',
            'sale.markup' => 'nullable|integer|min:0|max:1000000000',
            'sale.down_payment' => 'nullable|integer|min:0|lte:sale.cash_price',
            'sale.installments' => 'required|integer|min:1|max:120',
            'sale.frequency' => ['required', Rule::in(array_keys(Contract::FREQUENCIES))],
            'sale.first_due_on' => 'required|date|after_or_equal:sale.sale_date',
            'sale.late_fee' => 'nullable|integer|min:0|max:1000000',
            'sale.grace_days' => 'nullable|integer|min:0|max:60',
            'sale.rounding' => ['required', Rule::in(['1', '10', '50', '100', 1, 10, 50, 100])],
            'sale.method' => ['required', Rule::in(array_keys(Receipt::METHODS))],
            'sale.notes' => 'nullable|string|max:2000',
            'sale.salesman_id' => ['nullable', Rule::exists('biz_employees', 'id')->where('school_id', $this->business()->id)],
            'guarantors.*.name' => 'nullable|string|max:120',
            'guarantors.*.father_name' => 'nullable|string|max:120',
            'guarantors.*.cnic' => ['nullable', 'string', 'max:20', 'regex:/^[0-9-]+$/'],
            'guarantors.*.phone' => 'nullable|string|max:30|required_with:guarantors.*.name',
            'guarantors.*.address' => 'nullable|string|max:255',
            'guarantors.*.relation' => 'nullable|string|max:60',
            'productId' => ['nullable', Rule::exists('biz_products', 'id')->where('school_id', $this->business()->id)],
            'stockUnitId' => ['nullable', Rule::exists('biz_stock_units', 'id')->where('product_id', $this->productId)->where('status', 'in_stock')],
            'details.*' => 'nullable|string|max:120',
        ];
        if ($this->newCustomer) {
            $rules += Customers::rules();
        } else {
            $rules['customerId'] = 'required|integer';
        }

        return $rules;
    }

    private function messages(): array
    {
        return Customers::messages() + [
            'customerId.required' => 'Choose a customer, or add a new one.',
            'sale.down_payment.lte' => 'The down payment cannot be more than the cash price.',
            'sale.first_due_on.after_or_equal' => 'The first installment cannot fall before the sale date.',
            'guarantors.*.phone.required_with' => "Give the guarantor's phone.",
            'guarantors.*.cnic.regex' => 'Use numbers and dashes.',
            'stockUnitId.exists' => 'That piece was just sold. Choose another one.',
        ];
    }

    private function attributes(): array
    {
        return Customers::attributes() + [
            'sale.item' => 'item', 'sale.cash_price' => 'cash price', 'sale.installments' => 'number of installments', 'sale.first_due_on' => 'first due date',
        ];
    }

    /** The plan itself, exactly as saving makes it (a new customer too). The preview runs this and undoes it. */
    private function sell(Installments $installments): Contract
    {
        $customer = $this->newCustomer ? null : Customer::findOrFail($this->customerId);
        $sale = array_map(fn ($v) => $v === '' ? null : $v, $this->sale);
        $sale['markup_percent'] ??= 0;
        $sale += ['product_id' => $this->productId, 'stock_unit_id' => $this->stockUnitId, 'details' => $this->details];

        return DB::transaction(function () use ($customer, $sale, $installments) {
            $customer ??= Customer::create(array_merge(array_map(fn ($v) => $v === '' ? null : $v, $this->form), [
                'code' => Numbers::next($this->business(), 'biz_customers', 'C-', 4),
                'phone' => Phone::normalize($this->form['phone']),
                'is_blacklisted' => false,
            ]));

            return $installments->sell($this->business(), $customer, $sale, $this->guarantors);
        });
    }

    public function save(Installments $installments)
    {
        $this->validate($this->rules(), $this->messages(), $this->attributes());

        $customer = $this->newCustomer ? null : Customer::findOrFail($this->customerId);
        if ($customer?->is_blacklisted && ! $this->confirmBlacklisted) {
            $this->addError('customerId', 'This customer is blacklisted. Tick the box below to sell anyway.');

            return null;
        }

        $contract = $this->guarded(fn () => $this->sell($installments), 'sale.cash_price');

        if (! $contract) {
            return null;
        }

        session()->flash('toast', 'Sale '.$contract->number.' saved.'.($contract->down_payment ? ' Down payment receipt is ready to print.' : ''));

        return $this->redirectRoute('biz.contract', $contract, navigate: true);
    }

    public function render()
    {
        $term = trim($this->customerSearch);
        $productTerm = trim($this->productSearch);
        $product = $this->productId ? Product::with('type')->find($this->productId) : null;

        return view('livewire.biz.contract-form', [
            'product' => $product,
            'pieces' => $product ? $product->units()->with('purchase')->where('status', 'in_stock')->orderBy('id')->get() : collect(),
            'productMatches' => strlen($productTerm) >= 1 ? Product::where('is_active', true)
                ->withCount(['units as in_stock' => fn ($q) => $q->where('status', 'in_stock')])
                ->where(fn ($q) => $q->where('name', 'like', "%{$productTerm}%")->orWhere('brand', 'like', "%{$productTerm}%")->orWhere('code', $productTerm))
                ->orderBy('name')->limit(8)->get() : collect(),
            'salesmen' => Employee::where('is_active', true)->where('is_salesman', true)->orderBy('name')->get(),
            'customer' => $this->customerId ? Customer::with(['contracts' => fn ($q) => $q->where('status', 'active')->with('schedule')])->find($this->customerId) : null,
            'matches' => strlen($term) >= 2 ? Customers::search(Customer::query(), $term)->orderBy('name')->limit(8)->get() : collect(),
            'plan' => $plan = $this->preview(),
            // What saving will change, once there is a price to work with.
            'impact' => $plan ? $this->impactOf($this->rules(), fn () => $this->sell(app(Installments::class)), $this->messages(), $this->attributes()) : null,
        ]);
    }
}
