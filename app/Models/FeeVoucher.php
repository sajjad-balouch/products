<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class FeeVoucher extends Model
{
    use Audited, BelongsToSchool;

    protected $guarded = [];

    protected $casts = ['month' => 'date', 'due_on' => 'date', 'late_fee_waived' => 'boolean'];

    public function guardian()
    {
        return $this->belongsTo(Guardian::class);
    }

    public function lines()
    {
        return $this->hasMany(FeeVoucherLine::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function proofs()
    {
        return $this->hasMany(PaymentProof::class);
    }

    public function getTotalAttribute(): int
    {
        return $this->subtotal + $this->late_fee;
    }

    public function getBalanceAttribute(): int
    {
        return $this->status === 'void' ? 0 : max(0, $this->total - $this->paid);
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', ['unpaid', 'partial']);
    }
}
