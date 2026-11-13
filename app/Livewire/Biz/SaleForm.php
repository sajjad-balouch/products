<?php

namespace App\Livewire\Biz;

use App\Livewire\Biz\Concerns\InBusiness;
use App\Livewire\Biz\Concerns\ShowsImpact;
use App\Models\Biz\Customer;
use App\Models\Biz\Employee;
use App\Models\Biz\Invoice;
use App\Models\Biz\Order;
use App\Models\Biz\Product;
use App\Models\Biz\Receipt;
use App\Models\Biz\StockUnit;
use App\Services\Biz\Orders;
use App\Services\Biz\Sales;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/** A cash, bank or credit sale of one or more products, at retail or wholesale prices. */
#[Title('New sale')]
class SaleForm extends Component
{
    use InBusiness, ShowsImpact;

    #[Url(as: 'product')]
    public ?int $startProduct = null;

    public ?int $customerId = null;

    public string $customerSearch = '';

    public string $walkIn = '';

    /** retail or wholesale: which saved price new lines start with. */
    public string $priceList = 'retail';

    public string $productSearch = '';

    /** [product_id, label, quantity, unit_price, imeis] */
    public array $lines = [];

    public string $date = '';

    public string $discount = '0';

    public string $paid = '';

    public bool $paidTouched = false;

    public string $method = 'cash';

    public string $notes = '';

    /** Who made the sale, for the salesman report and commission. */
    public string $salesmanId = '';

    /** A salesman's order this sale is made from; it is marked delivered when the sale is saved. */
    #[Url(as: 'order')]
    public ?int $fromOrder = null;

    public function mount(): void
    {
        $this->date = today()->toDateString();
        if ($this->startProduct && Product::find($this->startProduct)) {
            $this->addProduct($this->startProduct);
        }
        if ($this->fromOrder) {
            $order = Order::with('lines.product', 'salesman')->find($this->fromOrder);
            if (! $order?->isOpen()) {
                $this->fromOrder = null;

                return;
            }
            // The order's products and agreed prices, the customer and the salesman, ready to check and save.
            $this->priceList = $order->price_list;
            $this->customerId = $order->customer_id;
            $this->salesmanId = (string) ($order->salesman_id ?? '');
            $this->notes = 'Order '.$order->number;
            $this->paid = '0';
            $this->paidTouched = true;
            foreach ($order->lines as $l) {
                if ($l->product?->is_active) {
                    $this->lines[] = ['product_id' => $l->product_id, 'label' => $l->product->label(), 'quantity' => (string) $l->quantity, 'unit_price' => (string) $l->unit_price, 'imeis' => ''];
                }
            }
        }
    }

    public function addProduct(int $id): void
    {
        $product = Product::where('is_active', true)->findOrFail($id);
        $this->productSearch = '';
        foreach ($this->lines as $i => $line) {
            if ($line['product_id'] === $product->id && $line['imeis'] === '') {
                $this->lines[$i]['quantity'] = (string) ((int) $line['quantity'] + 1);

                return;
            }
        }
        $this->lines[] = ['product_id' => $product->id, 'label' => $product->label(), 'quantity' => '1', 'unit_price' => (string) $this->priceOf($product), 'imeis' => ''];
    }

    private function priceOf(Product $product): int
    {
        return $this->priceList === 'wholesale' && $product->wholesale_price ? $product->wholesale_price : $product->sale_price;
    }

    public function updatedPriceList(): void
    {
        $products = Product::whereIn('id', array_column($this->lines, 'product_id'))->get()->keyBy('id');
        foreach ($this->lines as $i => $line) {
            if ($p = $products[$line['product_id']] ?? null) {
                $this->lines[$i]['unit_price'] = (string) $this->priceOf($p);
            }
        }
    }

    /** Scanned IMEIs set the quantity of their line. */
    public function updatedLines($value, string $key): void
    {
        [$i, $field] = array_pad(explode('.', $key, 2), 2, null);
        if ($field === 'imeis' && isset($this->lines[$i])) {
            $count = count($this->imeiList($this->lines[$i]['imeis']));
            if ($count) {
                $this->lines[$i]['quantity'] = (string) $count;
            }
        }
    }

    public function removeLine(int $i): void
    {
        unset($this->lines[$i]);
        $this->lines = array_values($this->lines);
    }

    public function pickCustomer(int $id): void
    {
        $this->customerId = Customer::findOrFail($id)->id;
        $this->customerSearch = '';
    }

    private function imeiList(string $text): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/[\r\n,;\s]+/', $text)), fn ($v) => $v !== ''));
    }

    private function totals(): array
    {
        $subtotal = array_sum(array_map(fn ($l) => (int) $l['quantity'] * (int) $l['unit_price'], $this->lines));
        $total = max(0, $subtotal - (int) $this->discount);

        return ['subtotal' => $subtotal, 'total' => $total, 'paid' => $this->paidTouched ? (int) $this->paid : $total];
    }

    public function updatedPaid(): void
    {
        $this->paidTouched = true;
    }

    private function rules(): array
    {
        return [
            'date' => 'required|date|before_or_equal:today',
            'customerId' => ['nullable', Rule::exists('biz_customers', 'id')->where('school_id', $this->business()->id)],
            'walkIn' => 'nullable|string|max:120',
            'lines' => 'required|array|min:1|max:100',
            'lines.*.quantity' => 'required|integer|min:1|max:5000',
            'lines.*.unit_price' => 'required|integer|min:0|max:1000000000',
            'lines.*.imeis' => 'nullable|string|max:100000',
            'discount' => 'nullable|integer|min:0',
            'paid' => 'nullable|integer|min:0',
            'method' => ['required', Rule::in(array_keys(Receipt::METHODS))],
            'notes' => 'nullable|string|max:255',
            'salesmanId' => ['nullable', Rule::exists('biz_employees', 'id')->where('school_id', $this->business()->id)],
        ];
    }

    private const MESSAGES = ['lines.required' => 'Add at least one product.', 'date.before_or_equal' => 'A sale cannot be dated in the future.'];

    private const ATTRIBUTES = ['lines.*.quantity' => 'quantity', 'lines.*.unit_price' => 'price'];

    /** The line whose scanned number was not found, for the error message. */
    private ?int $badLine = null;

    /** Turns scanned IMEIs / serials into the exact pieces to sell. Throws when one is not in stock. */
    private function payload(): array
    {
        $payload = [];
        foreach ($this->lines as $i => $line) {
            $product = Product::with('type')->findOrFail($line['product_id']);
            $unitIds = [];
            if (($id = $product->identifier()) && ($list = $this->imeiList($line['imeis']))) {
                $found = StockUnit::where('product_id', $product->id)->where('status', 'in_stock')->whereIn('details->'.$id['key'], $list)->get();
                $missing = array_diff($list, $found->map(fn ($u) => $u->details[$id['key']])->all());
                if ($missing) {
                    $this->badLine = $i;
                    throw new InvalidArgumentException($id['label'].' '.reset($missing).' is not in stock for '.$product->label().'.');
                }
                $unitIds = $found->pluck('id')->all();
            }
            $payload[] = ['product_id' => $product->id, 'quantity' => (int) $line['quantity'], 'unit_price' => (int) $line['unit_price'], 'unit_ids' => $unitIds];
        }

        return $payload;
    }

    /** The sale itself, exactly as saving makes it. The preview runs this too and undoes it. */
    private function sell(Sales $sales): Invoice
    {
        $this->badLine = null;
        $t = $this->totals();
        $customer = $this->customerId ? Customer::find($this->customerId) : null;

        return DB::transaction(function () use ($sales, $customer, $t) {
            $invoice = $sales->create($this->business(), $customer, $this->walkIn ?: null, $this->date, $this->payload(), (int) $this->discount, $t['paid'], $this->method, $this->notes ?: null, $this->salesmanId !== '' ? (int) $this->salesmanId : null);
            // Made from a salesman's order: that order is now delivered.
            if ($this->fromOrder) {
                app(Orders::class)->delivered(Order::findOrFail($this->fromOrder), $invoice);
            }

            return $invoice;
        });
    }

    public function save(Sales $sales)
    {
        $this->validate($this->rules(), self::MESSAGES, self::ATTRIBUTES);

        try {
            $invoice = $this->sell($sales);
        } catch (InvalidArgumentException $e) {
            $this->addError($this->badLine !== null ? 'lines.'.$this->badLine.'.imeis' : 'lines', $e->getMessage());

            return null;
        }
        session()->flash('toast', 'Sale '.$invoice->number.' saved.');

        return $this->redirectRoute('biz.sale', $invoice, navigate: true);
    }

    public function render()
    {
        $term = trim($this->productSearch);
        $customerTerm = trim($this->customerSearch);
        $stock = StockUnit::whereIn('product_id', array_column($this->lines, 'product_id'))->where('status', 'in_stock')
            ->selectRaw('product_id, COUNT(*) as n')->groupBy('product_id')->pluck('n', 'product_id');

        return view('livewire.biz.sale-form', [
            'matches' => $term !== '' ? Product::where('is_active', true)->withCount(['units as in_stock' => fn ($q) => $q->where('status', 'in_stock')])
                ->where(fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('brand', 'like', "%{$term}%")->orWhere('variant', 'like', "%{$term}%")->orWhere('code', $term))
                ->orderBy('brand')->orderBy('name')->limit(10)->get() : collect(),
            'customer' => $this->customerId ? Customer::find($this->customerId) : null,
            'customerMatches' => strlen($customerTerm) >= 2 ? Customers::search(Customer::query(), $customerTerm)->orderBy('name')->limit(6)->get() : collect(),
            'stock' => $stock,
            'identifiers' => Product::with('type')->whereIn('id', array_column($this->lines, 'product_id'))->get()->mapWithKeys(fn ($p) => [$p->id => $p->identifier()]),
            't' => $this->totals(),
            'order' => $this->fromOrder ? Order::find($this->fromOrder) : null,
            'salesmen' => Employee::where('is_active', true)->where('is_salesman', true)->orderBy('name')->get(),
            // What saving will change, once there is something to sell.
            'impact' => $this->lines ? $this->impactOf($this->rules(), fn () => $this->sell(app(Sales::class)), self::MESSAGES, self::ATTRIBUTES) : null,
        ]);
    }
}
