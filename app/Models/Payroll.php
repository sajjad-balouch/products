<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    use BelongsToSchool;

    protected $guarded = [];

    protected $casts = ['month' => 'date', 'paid_on' => 'date'];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}
