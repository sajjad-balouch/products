<?php

namespace App\Models\Biz;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class JournalLine extends Model
{
    use BelongsToSchool;

    protected $table = 'biz_journal_lines';

    protected $guarded = ['id'];

    public $timestamps = false;

    public function entry()
    {
        return $this->belongsTo(JournalEntry::class, 'entry_id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
