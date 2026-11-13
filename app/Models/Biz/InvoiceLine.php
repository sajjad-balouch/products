<?php

namespace App\Models\Biz;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class InvoiceLine extends Model
{
    use BelongsToSchool;

    protected $table = 'biz_invoice_lines';

    protected $guarded = ['id'];

    public $timestamps = false;

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function units()
    {
        return $this->hasMany(StockUnit::class, 'invoice_line_id');
    }
}
