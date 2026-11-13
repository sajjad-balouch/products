<?php

namespace App\Models\Biz;

use App\Models\Concerns\Audited;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use Audited, BelongsToSchool;

    protected $table = 'biz_customers';

    protected $guarded = ['id'];

    protected $casts = ['is_blacklisted' => 'boolean'];

    public function contracts()
    {
        return $this->hasMany(Contract::class, 'customer_id')->latest('id');
    }
}
