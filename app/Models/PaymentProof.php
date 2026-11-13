<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class PaymentProof extends Model
{
    use BelongsToSchool;

    protected $guarded = [];

    protected $casts = ['reviewed_at' => 'datetime'];

    public function voucher()
    {
        return $this->belongsTo(FeeVoucher::class, 'fee_voucher_id');
    }

    public function guardian()
    {
        return $this->belongsTo(Guardian::class);
    }

    /** Another non-rejected proof already used this transaction ID. */
    public function getIsDuplicateAttribute(): bool
    {
        return static::where('method', $this->method)->where('reference', $this->reference)
            ->where('id', '!=', $this->id)->where('status', '!=', 'rejected')->exists();
    }
}
