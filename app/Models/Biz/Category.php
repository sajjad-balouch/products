<?php

namespace App\Models\Biz;

use App\Models\Concerns\Audited;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use Audited, BelongsToSchool;

    protected $table = 'biz_categories';

    protected $guarded = ['id'];

    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }
}
