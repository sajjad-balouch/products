<?php

namespace App\Models\Biz;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** One piece of stock, with what it cost and its own details (serial, engine no., IMEI...). */
class StockUnit extends Model
{
    use BelongsToSchool;

    protected $table = 'biz_stock_units';

    protected $guarded = ['id'];

    protected $casts = ['details' => 'array', 'sold_on' => 'date'];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class, 'purchase_id');
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    public function invoiceLine()
    {
        return $this->belongsTo(InvoiceLine::class, 'invoice_line_id');
    }

    /** "Engine no. 7123, Colour Red" from the details that are filled in. */
    public function summary(array $fields = []): string
    {
        $labels = collect($fields)->pluck('label', 'key');

        return collect($this->details ?? [])->filter(fn ($v) => $v !== null && $v !== '')
            ->map(fn ($v, $k) => ($labels[$k] ?? $k).' '.$v)->implode(', ');
    }
}
