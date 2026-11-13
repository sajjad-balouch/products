<?php

namespace App\Models\Biz;

use App\Models\Concerns\Audited;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** A brand of one product type, like Samsung for mobiles or Honda for motorcycles, with its models. */
class Brand extends Model
{
    use Audited, BelongsToSchool;

    protected $table = 'biz_brands';

    protected $guarded = ['id'];

    public function type()
    {
        return $this->belongsTo(ProductType::class, 'type_id');
    }

    public function models()
    {
        return $this->hasMany(BrandModel::class, 'brand_id')->orderBy('name');
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'brand_id');
    }
}
