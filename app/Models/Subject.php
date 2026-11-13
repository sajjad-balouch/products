<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class Subject extends Model
{
    use BelongsToSchool;

    protected $guarded = [];
}
