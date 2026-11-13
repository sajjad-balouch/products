<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use App\Support\Phone;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use BelongsToSchool;

    protected $guarded = [];

    protected $casts = ['sent_at' => 'datetime'];

    public function guardian()
    {
        return $this->belongsTo(Guardian::class);
    }

    /** A business customer (installment reminders). */
    public function customer()
    {
        return $this->belongsTo(Biz\Customer::class);
    }

    /** Click-to-send link: works today with no WhatsApp API account. */
    public function getWhatsappUrlAttribute(): string
    {
        return 'https://wa.me/'.Phone::international($this->phone).'?text='.rawurlencode($this->body);
    }
}
