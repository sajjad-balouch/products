<?php

namespace App\Models\Biz;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class OrderLine extends Model
{
    use BelongsToSchool;

    protected $table = 'biz_order_lines';

    protected $guarded = ['id'];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
