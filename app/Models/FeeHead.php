<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class FeeHead extends Model
{
    use BelongsToSchool;

    protected $guarded = [];

    protected $casts = ['is_monthly' => 'boolean'];
}
