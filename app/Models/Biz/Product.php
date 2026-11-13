<?php

namespace App\Models\Biz;

use App\Models\Concerns\Audited;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use Audited, BelongsToSchool;

    protected $table = 'biz_products';

    protected $guarded = ['id'];

    protected $casts = ['is_active' => 'boolean'];

    public function type()
    {
        return $this->belongsTo(ProductType::class, 'type_id');
    }

    public function units()
    {
        return $this->hasMany(StockUnit::class, 'product_id');
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class, 'product_id')->latest('date')->latest('id');
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class, 'product_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function brandRecord()
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    public function model()
    {
        return $this->belongsTo(BrandModel::class, 'model_id');
    }

    public function invoiceLines()
    {
        return $this->hasMany(InvoiceLine::class, 'product_id');
    }

    /** @return array<int, array{key: string, label: string}> */
    public function fields(): array
    {
        return $this->type?->fields ?? [];
    }

    /** The first detail of the type (IMEI, engine no., serial no.) identifies a piece and must not repeat. */
    public function identifier(): ?array
    {
        return $this->fields()[0] ?? null;
    }

    public function label(): string
    {
        return trim(($this->brand ? $this->brand.' ' : '').$this->name.($this->variant ? ' '.$this->variant : ''));
    }
}
