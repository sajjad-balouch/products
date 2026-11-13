<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use Audited, BelongsToSchool;

    protected $guarded = [];

    protected $casts = ['paid_on' => 'date', 'voided_at' => 'datetime'];

    public function voucher()
    {
        return $this->belongsTo(FeeVoucher::class, 'fee_voucher_id');
    }

    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function scopeValid($query)
    {
        return $query->whereNull('voided_at');
    }
}
