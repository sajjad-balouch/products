<?php

namespace App\Models\Biz;

use App\Models\Concerns\Audited;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** One account in the chart of accounts. */
class Account extends Model
{
    use Audited, BelongsToSchool;

    protected $table = 'biz_accounts';

    protected $guarded = ['id'];

    protected $casts = ['is_active' => 'boolean'];

    public const TYPES = ['asset' => 'Assets', 'liability' => 'Liabilities', 'equity' => 'Equity', 'income' => 'Income', 'expense' => 'Expenses'];

    public function lines()
    {
        return $this->hasMany(JournalLine::class, 'account_id');
    }

    /** Assets and expenses grow with debits; the rest grow with credits. */
    public function isDebitNature(): bool
    {
        return in_array($this->type, ['asset', 'expense'], true);
    }

    public function label(): string
    {
        return $this->code.' · '.$this->name;
    }
}
