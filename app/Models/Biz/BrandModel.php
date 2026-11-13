<?php

namespace App\Models\Biz;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** A model of a brand: Galaxy A35 for Samsung, CD 70 for Honda. */
class BrandModel extends Model
{
    use BelongsToSchool;

    protected $table = 'biz_brand_models';

    protected $guarded = ['id'];

    public function brand()
    {
        return $this->belongsTo(Brand::class, 'brand_id');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'model_id');
    }
}
