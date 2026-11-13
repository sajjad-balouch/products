<?php

namespace App\Models\Biz;

use App\Models\Concerns\BelongsToSchool;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/** A posted voucher. Never edited: mistakes are fixed by a reversal entry. */
class JournalEntry extends Model
{
    use BelongsToSchool;

    protected $table = 'biz_journal_entries';

    protected $guarded = ['id'];

    protected $casts = ['date' => 'date', 'reversed_at' => 'datetime'];

    public const KINDS = [
        'journal' => ['Journal voucher', 'JV'],
        'receipt' => ['Receipt voucher', 'RV'],
        'payment' => ['Payment voucher', 'PV'],
        'sale' => ['Installment sale', 'SV'],
        'collection' => ['Installment collection', 'CR'],
        'reversal' => ['Reversal', 'RE'],
    ];

    public function lines()
    {
        return $this->hasMany(JournalLine::class, 'entry_id')->orderBy('id');
    }

    public function source()
    {
        return $this->morphTo();
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reversal()
    {
        return $this->hasOne(self::class, 'reversal_of');
    }

    public function total(): int
    {
        return (int) $this->lines->sum('debit');
    }

    public function kindLabel(): string
    {
        return self::KINDS[$this->kind][0] ?? ucfirst($this->kind);
    }
}
