<?php

namespace App\Livewire\Biz;

use App\Livewire\Biz\Concerns\InBusiness;
use App\Livewire\Biz\Concerns\ShowsImpact;
use App\Models\Biz\Brand;
use App\Models\Biz\BrandModel;
use App\Models\Biz\Category;
use App\Models\Biz\Product;
use App\Models\Biz\ProductType;
use App\Models\Biz\Purchase;
use App\Models\Biz\Vendor;
use App\Services\Biz\Stock;
use App\Support\BusinessKinds;
use Illuminate\Validation\Rule;
use InvalidArgumentException;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/** Products, their categories, brands and models, product types, stock coming in, and finding a piece by IMEI. */
#[Title('Products and stock')]
class Products extends Component
{
    use InBusiness, ShowsImpact;

    public const TABS = ['products' => ['Products', 'package'], 'categories' => ['Categories', 'grid'], 'brands' => ['Brands and models', 'tag'], 'types' => ['Product types', 'layers'], 'find' => ['Find IMEI / serial', 'fingerprint']];

    #[Url]
    public string $tab = 'products';

    #[Url]
    public string $search = '';

    #[Url]
    public string $typeFilter = '';

    #[Url]
    public string $categoryFilter = '';

    // Product form
    public ?int $productId = null;

    public bool $showProduct = false;

    public array $product = [];

    public string $newBrand = '';

    public string $newModel = '';

    // Type form
    public ?int $typeId = null;

    public bool $showType = false;

    public string $typeName = '';

    public string $typeFields = '';

    // Category and brand forms
    public ?int $categoryId = null;

    public ?string $categoryName = null;

    public ?int $brandId = null;

    public ?string $brandName = null;

    public string $brandType = '';

    public array $modelDraft = [];

    // Stock in
    public ?int $stockFor = null;

    public array $purchase = [];

    public array $units = [];

    /** IMEIs or serials pasted one per line; their count becomes the quantity. */
    public string $identifiers = '';

    // Find a piece
    public string $find = '';

    public function updatedTab(): void
    {
        $this->tab = isset(self::TABS[$this->tab]) ? $this->tab : 'products';
    }

    // ---- Products ----

    public function openProduct(?int $id = null): void
    {
        $this->resetValidation();
        $p = $id ? Product::findOrFail($id) : null;
        $this->productId = $p?->id;
        $this->product = [
            'type_id' => (string) ($p?->type_id ?? ($this->typeFilter ?: BusinessKinds::typesQuery($this->business())->orderBy('name')->value('id')) ?? ''),
            'category_id' => (string) ($p?->category_id ?? $this->categoryFilter),
            'brand_id' => (string) $p?->brand_id, 'model_id' => (string) $p?->model_id,
            'name' => (string) $p?->name, 'variant' => (string) $p?->variant, 'brand' => (string) $p?->brand, 'code' => (string) $p?->code,
            'sale_price' => (string) ($p?->sale_price ?: ''), 'wholesale_price' => (string) ($p?->wholesale_price ?: ''),
            'notes' => (string) $p?->notes, 'is_active' => $p?->is_active ?? true,
        ];
        $this->newBrand = $this->newModel = '';
        $this->showProduct = true;
    }

    public function updatedProductTypeId(): void
    {
        $this->product['brand_id'] = $this->product['model_id'] = '';
    }

    public function updatedProductBrandId(): void
    {
        $this->product['model_id'] = '';
        $this->product['brand'] = (string) Brand::find((int) $this->product['brand_id'])?->name;
    }

    /** Picking a model writes the product name, which can still be changed. */
    public function updatedProductModelId(): void
    {
        if ($model = BrandModel::find((int) $this->product['model_id'])) {
            $this->product['name'] = $model->name;
        }
    }

    /** Adds a brand for the chosen type straight from the product form. */
    public function addBrand(): void
    {
        $this->validate(['newBrand' => 'required|string|max:80'], [], ['newBrand' => 'brand']);
        $brand = Brand::firstOrCreate(['type_id' => $this->product['type_id'] ?: null, 'name' => trim($this->newBrand)]);
        $this->product['brand_id'] = (string) $brand->id;
        $this->product['brand'] = $brand->name;
        $this->product['model_id'] = '';
        $this->newBrand = '';
    }

    public function addModel(): void
    {
        $this->validate(['newModel' => 'required|string|max:100', 'product.brand_id' => 'required'], ['product.brand_id.required' => 'Choose the brand first.'], ['newModel' => 'model']);
        $brand = Brand::findOrFail((int) $this->product['brand_id']);
        $model = $brand->models()->firstOrCreate(['name' => trim($this->newModel)], ['school_id' => $brand->school_id]);
        $this->product['model_id'] = (string) $model->id;
        $this->product['name'] = $model->name;
        $this->newModel = '';
    }

    public function saveProduct(): void
    {
        $school = $this->business()->id;
        $this->validate([
            'product.type_id' => ['nullable', Rule::exists('biz_product_types', 'id')->where('school_id', $school)],
            'product.category_id' => ['nullable', Rule::exists('biz_categories', 'id')->where('school_id', $school)],
            'product.brand_id' => ['nullable', Rule::exists('biz_brands', 'id')->where('school_id', $school)],
            'product.model_id' => ['nullable', Rule::exists('biz_brand_models', 'id')->where('brand_id', (int) ($this->product['brand_id'] ?? 0))],
            'product.name' => 'required|string|max:150',
            'product.variant' => 'nullable|string|max:80',
            'product.brand' => 'nullable|string|max:80',
            'product.code' => ['nullable', 'string', 'max:30', Rule::unique('biz_products', 'code')->where('school_id', $school)->ignore($this->productId)],
            'product.sale_price' => 'required|integer|min:0|max:1000000000',
            'product.wholesale_price' => 'nullable|integer|min:0|max:1000000000',
            'product.notes' => 'nullable|string|max:1000',
            'product.is_active' => 'boolean',
        ], ['product.code.unique' => 'Another product already has this code.'], ['product.name' => 'name', 'product.sale_price' => 'retail price']);

        $values = array_map(fn ($v) => $v === '' ? null : $v, $this->product);
        $values['sale_price'] = (int) $values['sale_price'];
        $values['wholesale_price'] = (int) ($values['wholesale_price'] ?? 0);
        $values['is_active'] = (bool) $this->product['is_active'];
        $values['brand'] = $values['brand_id'] ? Brand::find($values['brand_id'])?->name : $values['brand'];
        $this->productId ? Product::findOrFail($this->productId)->update($values) : Product::create($values);
        $this->showProduct = false;
        $this->dispatch('toast', message: 'Product saved.');
    }

    // ---- Product types ----

    public function openType(?int $id = null): void
    {
        $this->resetValidation();
        $type = $id ? ProductType::findOrFail($id) : null;
        $this->typeId = $type?->id;
        $this->typeName = (string) $type?->name;
        $this->typeFields = collect($type?->fields ?? [])->pluck('label')->implode("\n");
        $this->showType = true;
    }

    public function saveType(): void
    {
        $this->validate([
            'typeName' => ['required', 'string', 'max:80', Rule::unique('biz_product_types', 'name')->where('school_id', $this->business()->id)->ignore($this->typeId)],
            'typeFields' => 'nullable|string|max:1000',
        ], [], ['typeName' => 'name']);

        $type = $this->typeId ? ProductType::findOrFail($this->typeId) : null;
        $fields = ProductType::fieldsFrom(preg_split('/\r\n|\r|\n|,/', $this->typeFields));
        // Keep the keys of fields that keep their label, so details already saved still show.
        $old = collect($type?->fields ?? [])->keyBy('label');
        $fields = array_map(fn ($f) => isset($old[$f['label']]) ? $old[$f['label']] : $f, $fields);

        $values = ['name' => $this->typeName, 'fields' => $fields];
        $type ? $type->update($values) : ProductType::create($values);
        $this->showType = false;
        $this->dispatch('toast', message: 'Product type saved. The first detail ('.($fields[0]['label'] ?? 'none').') must be different on every piece.');
    }

    // ---- Categories ----

    public function openCategory(?int $id = null): void
    {
        $this->resetValidation();
        $this->categoryId = $id;
        $this->categoryName = (string) ($id ? Category::findOrFail($id)->name : '');
    }

    public function saveCategory(): void
    {
        $this->validate(['categoryName' => ['required', 'string', 'max:80', Rule::unique('biz_categories', 'name')->where('school_id', $this->business()->id)->ignore($this->categoryId)]], [], ['categoryName' => 'name']);
        $this->categoryId ? Category::findOrFail($this->categoryId)->update(['name' => $this->categoryName]) : Category::create(['name' => $this->categoryName]);
        $this->categoryName = null;
        $this->dispatch('toast', message: 'Category saved.');
    }

    public function deleteCategory(int $id): void
    {
        $category = Category::withCount('products')->findOrFail($id);
        if ($category->products_count) {
            $this->dispatch('toast', message: 'Move its '.$category->products_count.' products to another category first.', error: true);

            return;
        }
        $category->delete();
    }

    // ---- Brands and models ----

    public function openBrand(?int $id = null): void
    {
        $this->resetValidation();
        $brand = $id ? Brand::findOrFail($id) : null;
        $this->brandId = $brand?->id;
        $this->brandName = (string) $brand?->name;
        $this->brandType = (string) ($brand?->type_id ?? $this->typeFilter);
    }

    public function saveBrand(): void
    {
        $this->validate([
            'brandName' => ['required', 'string', 'max:80', Rule::unique('biz_brands', 'name')->where('school_id', $this->business()->id)->where('type_id', $this->brandType ?: null)->ignore($this->brandId)],
            'brandType' => ['nullable', Rule::exists('biz_product_types', 'id')->where('school_id', $this->business()->id)],
        ], ['brandName.unique' => 'This brand is already there for this type.'], ['brandName' => 'brand']);
        $values = ['name' => trim($this->brandName), 'type_id' => $this->brandType ?: null];
        $this->brandId ? Brand::findOrFail($this->brandId)->update($values) : Brand::create($values);
        // Products show the brand's name, so a renamed brand is renamed on them too.
        if ($this->brandId) {
            Product::where('brand_id', $this->brandId)->update(['brand' => $values['name']]);
        }
        $this->brandName = null;
        $this->dispatch('toast', message: 'Brand saved.');
    }

    public function addModelTo(int $brandId): void
    {
        $name = trim((string) ($this->modelDraft[$brandId] ?? ''));
        if ($name === '' || mb_strlen($name) > 100) {
            return;
        }
        $brand = Brand::findOrFail($brandId);
        $brand->models()->firstOrCreate(['name' => $name], ['school_id' => $brand->school_id]);
        $this->modelDraft[$brandId] = '';
    }

    public function deleteModel(int $id): void
    {
        $model = BrandModel::withCount('products')->findOrFail($id);
        if ($model->products_count) {
            $this->dispatch('toast', message: 'Products use this model, so it stays.', error: true);

            return;
        }
        $model->delete();
    }

    public function deleteBrand(int $id): void
    {
        $brand = Brand::withCount('products')->findOrFail($id);
        if ($brand->products_count) {
            $this->dispatch('toast', message: 'Products use this brand, so it stays.', error: true);

            return;
        }
        $brand->delete();
    }

    // ---- Stock in ----

    public function openStock(int $productId): void
    {
        $this->resetValidation();
        $product = Product::findOrFail($productId);
        $this->stockFor = $product->id;
        $last = $product->purchases()->first();
        $this->purchase = ['date' => today()->toDateString(), 'quantity' => '1', 'unit_cost' => (string) ($last?->unit_cost ?? ''), 'vendor_id' => (string) ($last?->vendor_id ?? ''), 'paid_by' => 'cash', 'paid_now' => '', 'reference' => ''];
        $this->newVendor = '';
        $this->units = [[]];
        $this->identifiers = '';
    }

    /** One row of details for each piece, up to 25 rows on screen. */
    public function updatedPurchaseQuantity(): void
    {
        $n = max(1, min(25, (int) $this->purchase['quantity']));
        $this->units = array_slice(array_pad($this->units, $n, []), 0, $n);
    }

    public function updatedIdentifiers(): void
    {
        $count = count($this->identifierList());
        if ($count) {
            $this->purchase['quantity'] = (string) $count;
            $this->updatedPurchaseQuantity();
        }
    }

    private function identifierList(): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/[\r\n,;]+/', $this->identifiers)), fn ($v) => $v !== ''));
    }

    private function stockRules(): array
    {
        return [
            'purchase.date' => 'required|date|before_or_equal:today',
            'purchase.quantity' => 'required|integer|min:1|max:'.Stock::MAX_PIECES,
            'purchase.unit_cost' => 'required|integer|min:0|max:1000000000',
            'purchase.vendor_id' => [$this->owesVendor() ? 'required' : 'nullable', Rule::exists('biz_vendors', 'id')->where('school_id', $this->business()->id)],
            'purchase.paid_now' => ['nullable', 'integer', 'min:0', 'max:'.$this->stockTotal()],
            'purchase.paid_by' => ['required', Rule::in(array_keys(Purchase::PAID_BY))],
            'purchase.reference' => 'nullable|string|max:60',
            'units.*.*' => 'nullable|string|max:120',
            'identifiers' => 'nullable|string|max:200000',
        ];
    }

    private const STOCK_MESSAGES = ['purchase.date.before_or_equal' => 'Stock cannot come in on a future date.', 'purchase.vendor_id.required' => 'Choose the vendor you owe the unpaid amount to.', 'purchase.paid_now.max' => 'Paid now cannot be more than the total.'];

    private function stockTotal(): int
    {
        return (int) ($this->purchase['quantity'] ?? 0) * (int) ($this->purchase['unit_cost'] ?? 0);
    }

    /** Something is left to pay the vendor: bought on credit, or only part paid now. */
    private function owesVendor(): bool
    {
        $by = $this->purchase['paid_by'] ?? 'cash';
        $now = $this->purchase['paid_now'] ?? '';

        return $by === 'credit' || (in_array($by, ['cash', 'bank'], true) && $now !== '' && (int) $now < $this->stockTotal());
    }

    /** A vendor typed on the stock-in box, added and chosen on the spot. */
    public string $newVendor = '';

    public function addVendor(): void
    {
        $this->validate(['newVendor' => 'required|string|max:120'], [], ['newVendor' => 'vendor name']);
        $vendor = Vendors::create(['name' => $this->newVendor], $this->business());
        $this->purchase['vendor_id'] = (string) $vendor->id;
        $this->newVendor = '';
    }

    private const STOCK_ATTRIBUTES = ['purchase.unit_cost' => 'cost of one piece', 'purchase.quantity' => 'quantity'];

    /** The stock coming in, exactly as saving adds it. The preview runs this and undoes it. */
    private function addStock(Stock $stock): Purchase
    {
        $product = Product::with('type')->findOrFail($this->stockFor);
        $units = $this->units;
        if (($id = $product->identifier()) && ($list = $this->identifierList())) {
            if (count($list) !== (int) $this->purchase['quantity']) {
                throw new InvalidArgumentException(count($list).' '.$id['label'].' written for '.$this->purchase['quantity'].' pieces.');
            }
            foreach ($list as $i => $value) {
                $units[$i] = array_merge($units[$i] ?? [], [$id['key'] => $value]);
            }
        }

        return $stock->purchase($this->business(), $product, ['vendor_id' => $this->purchase['vendor_id'] ?: null] + $this->purchase, $units);
    }

    public function saveStock(Stock $stock): void
    {
        $this->validate($this->stockRules(), self::STOCK_MESSAGES, self::STOCK_ATTRIBUTES);

        $purchase = $this->guarded(fn () => $this->addStock($stock), 'identifiers');
        if ($purchase) {
            $this->stockFor = null;
            $this->dispatch('toast', message: $purchase->quantity.' × '.$purchase->product->label().' added to stock ('.rs($purchase->total()).').');
        }
    }

    public function render()
    {
        $term = trim($this->search);
        // Only what this kind of business sells (see BusinessKinds), plus anything it made or uses.
        $data = [
            'types' => BusinessKinds::typesQuery($this->business())->withCount('products')->orderBy('name')->get(),
            'categories' => BusinessKinds::categoriesQuery($this->business())->withCount('products')->orderBy('name')->get(),
            'kind' => BusinessKinds::of($this->business()),
        ];

        if ($this->tab === 'products') {
            $data['products'] = Product::with(['type', 'category'])
                ->withCount(['units as in_stock' => fn ($q) => $q->where('status', 'in_stock')])
                ->withSum(['units as stock_value' => fn ($q) => $q->where('status', 'in_stock')], 'unit_cost')
                ->withCount(['units as sold' => fn ($q) => $q->where('status', 'sold')])
                ->when($term !== '', fn ($q) => $q->where(fn ($q) => $q->where('name', 'like', "%{$term}%")->orWhere('brand', 'like', "%{$term}%")->orWhere('variant', 'like', "%{$term}%")->orWhere('code', $term)))
                ->when($this->typeFilter, fn ($q) => $q->where('type_id', (int) $this->typeFilter))
                ->when($this->categoryFilter, fn ($q) => $q->where('category_id', (int) $this->categoryFilter))
                ->orderByDesc('is_active')->orderBy('brand')->orderBy('name')->get();
        }
        if ($this->tab === 'brands') {
            $data['brands'] = BusinessKinds::brandsQuery($this->business())->with(['models' => fn ($q) => $q->withCount('products'), 'type'])->withCount('products')
                ->when($this->typeFilter, fn ($q) => $q->where('type_id', (int) $this->typeFilter))
                ->when($term !== '', fn ($q) => $q->where('name', 'like', "%{$term}%"))
                ->orderBy('name')->get();
        }
        if ($this->tab === 'find') {
            $data['found'] = Stock::find($this->business(), $this->find);
        }
        if ($this->showProduct) {
            $data['formBrands'] = Brand::where('type_id', $this->product['type_id'] ?: null)->orderBy('name')->get();
            $data['formModels'] = $this->product['brand_id'] ? BrandModel::where('brand_id', (int) $this->product['brand_id'])->orderBy('name')->get() : collect();
        }
        $data['stockProduct'] = $this->stockFor ? Product::with('type')->find($this->stockFor) : null;
        $data['vendors'] = $this->stockFor ? Vendor::where('is_active', true)->orderBy('name')->get() : collect();
        // What adding this stock will change, worked out by adding it and undoing it.
        $data['impact'] = $data['stockProduct'] ? $this->impactOf($this->stockRules(), fn () => $this->addStock(app(Stock::class)), self::STOCK_MESSAGES, self::STOCK_ATTRIBUTES) : null;

        return view('livewire.biz.products', $data);
    }
}
