<?php

namespace App\Models\Biz;

use App\Models\Concerns\Audited;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/** Someone who works for the business. A salesman can be named on sales and earns commission on them. */
class Employee extends Model
{
    use Audited, BelongsToSchool;

    protected $table = 'biz_employees';

    protected $guarded = ['id'];

    protected $casts = ['is_active' => 'boolean', 'is_salesman' => 'boolean', 'joined_on' => 'date', 'commission_percent' => 'float'];

    /** The login for the salesman phone app, if the owner gave one. */
    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }

    public function lines()
    {
        return $this->hasMany(JournalLine::class, 'employee_id');
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class, 'salesman_id');
    }

    public function contracts()
    {
        return $this->hasMany(Contract::class, 'salesman_id');
    }

    /** Advance not yet taken back from salary. */
    public function advance(): int
    {
        return (int) DB::table('biz_journal_lines as l')->join('biz_accounts as a', 'a.id', '=', 'l.account_id')
            ->where('a.system_key', 'staff_advance')->where('l.employee_id', $this->id)
            ->selectRaw('COALESCE(SUM(l.debit) - SUM(l.credit), 0) as v')->value('v');
    }

    /** Paid to this employee from an account (salaries, commission) in the dates given. */
    public function paid(string $key, ?string $from = null, ?string $to = null): int
    {
        return (int) DB::table('biz_journal_lines as l')->join('biz_accounts as a', 'a.id', '=', 'l.account_id')
            ->join('biz_journal_entries as e', 'e.id', '=', 'l.entry_id')
            ->where('a.system_key', $key)->where('l.employee_id', $this->id)
            ->when($from, fn ($q) => $q->where('e.date', '>=', $from))->when($to, fn ($q) => $q->where('e.date', '<=', $to))
            ->selectRaw('COALESCE(SUM(l.debit) - SUM(l.credit), 0) as v')->value('v');
    }

    /**
     * Sales this salesman made in the dates given (cash and credit bills, and installment plans
     * at their cash price), and the commission earned on them.
     *
     * @return array{bills: int, bills_total: int, plans: int, plans_total: int, total: int, commission: int}
     */
    public function salesBetween(string $from, string $to): array
    {
        $bills = Invoice::where('salesman_id', $this->id)->whereNull('voided_at')->whereBetween('date', [$from, $to]);
        $plans = Contract::where('salesman_id', $this->id)->whereBetween('sale_date', [$from, $to]);
        $billsTotal = (int) (clone $bills)->sum('total');
        $plansTotal = (int) (clone $plans)->sum('cash_price');
        $total = $billsTotal + $plansTotal;

        return [
            'bills' => $bills->count(), 'bills_total' => $billsTotal,
            'plans' => $plans->count(), 'plans_total' => $plansTotal,
            'total' => $total, 'commission' => (int) round($total * $this->commission_percent / 100),
        ];
    }
}
