<?php

namespace App\Models\Biz;

use App\Models\Concerns\Audited;
use App\Models\Concerns\BelongsToSchool;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class InvoicePayment extends Model
{
    use Audited, BelongsToSchool;

    protected $table = 'biz_invoice_payments';

    protected $guarded = ['id'];

    protected $casts = ['date' => 'date'];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'invoice_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
