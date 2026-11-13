<?php

namespace App\Models\Biz;

use App\Models\Concerns\Audited;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/** A supplier the business buys stock or services from. */
class Vendor extends Model
{
    use Audited, BelongsToSchool;

    protected $table = 'biz_vendors';

    protected $guarded = ['id'];

    protected $casts = ['is_active' => 'boolean'];

    public function purchases()
    {
        return $this->hasMany(Purchase::class, 'vendor_id')->latest('date')->latest('id');
    }

    public function lines()
    {
        return $this->hasMany(JournalLine::class, 'vendor_id');
    }

    /** What the business owes this vendor now: credits less debits on Accounts payable. */
    public function balance(): int
    {
        return (int) self::balances([$this->id])->get($this->id, 0);
    }

    /** @return \Illuminate\Support\Collection<int, int> vendor id => amount owed */
    public static function balances(?array $ids = null)
    {
        return DB::table('biz_journal_lines as l')->join('biz_accounts as a', 'a.id', '=', 'l.account_id')
            ->where('a.system_key', 'payable')->whereNotNull('l.vendor_id')
            ->when($ids !== null, fn ($q) => $q->whereIn('l.vendor_id', $ids))
            ->groupBy('l.vendor_id')->selectRaw('l.vendor_id, SUM(l.credit) - SUM(l.debit) as owed')
            ->pluck('owed', 'vendor_id')->map(fn ($v) => (int) $v);
    }
}
