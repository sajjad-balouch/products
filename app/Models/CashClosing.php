<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class CashClosing extends Model
{
    use BelongsToSchool;

    protected $guarded = [];

    protected $casts = ['date' => 'date'];

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }
}
