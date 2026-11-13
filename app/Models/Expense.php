<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use BelongsToSchool;

    protected $guarded = [];

    protected $casts = ['spent_on' => 'date'];
}
