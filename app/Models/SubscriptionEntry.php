<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use Illuminate\Database\Eloquent\Model;

class SubscriptionEntry extends Model
{
    use Audited;

    public const TYPES = ['payment' => 'Payment', 'free' => 'Free months', 'adjust' => 'Date set by hand'];

    public const METHODS = ['cash' => 'Cash', 'bank' => 'Bank transfer', 'jazzcash' => 'JazzCash', 'easypaisa' => 'Easypaisa', 'cheque' => 'Cheque', 'other' => 'Other'];

    protected $guarded = ['id'];

    protected $casts = ['from_date' => 'date', 'to_date' => 'date', 'paid_on' => 'date'];

    public function school()
    {
        return $this->belongsTo(School::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
