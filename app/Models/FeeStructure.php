<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class FeeStructure extends Model
{
    use BelongsToSchool;

    protected $guarded = [];

    public function feeHead()
    {
        return $this->belongsTo(FeeHead::class);
    }
}
