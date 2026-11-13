<?php

namespace App\Models\Biz;

use App\Models\Concerns\Audited;
use App\Models\Concerns\BelongsToSchool;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Receipt extends Model
{
    use Audited, BelongsToSchool;

    protected $table = 'biz_receipts';

    protected $guarded = ['id'];

    protected $casts = ['date' => 'date', 'voided_at' => 'datetime', 'is_down_payment' => 'boolean'];

    public const METHODS = ['cash' => 'Cash', 'bank' => 'Bank transfer', 'jazzcash' => 'JazzCash', 'easypaisa' => 'Easypaisa', 'cheque' => 'Cheque'];

    public function contract()
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function entry()
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    /** Everything the customer handed over. */
    public function total(): int
    {
        return $this->amount + $this->late_fee;
    }
}
