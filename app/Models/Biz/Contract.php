<?php

namespace App\Models\Biz;

use App\Models\Concerns\Audited;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/** An installment sale. Money owed is the financed amount less what the receipts have paid. */
class Contract extends Model
{
    use Audited, BelongsToSchool;

    protected $table = 'biz_contracts';

    protected $guarded = ['id'];

    protected $casts = ['sale_date' => 'date', 'first_due_on' => 'date', 'closed_at' => 'datetime', 'markup_percent' => 'float', 'details' => 'array'];

    public const FREQUENCIES = ['monthly' => 'Monthly', 'fortnightly' => 'Every 2 weeks', 'weekly' => 'Weekly'];

    public const STATUSES = ['active' => 'Running', 'completed' => 'Completed', 'written_off' => 'Written off'];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function stockUnit()
    {
        return $this->belongsTo(StockUnit::class, 'stock_unit_id');
    }

    /** What the sale earns over what the piece cost: the cash price less cost, plus the markup. */
    public function grossProfit(): ?int
    {
        return $this->cost_price === null ? null : $this->total_price - $this->cost_price;
    }

    public function guarantors()
    {
        return $this->hasMany(Guarantor::class, 'contract_id')->orderBy('id');
    }

    public function schedule()
    {
        return $this->hasMany(ScheduleItem::class, 'contract_id')->orderBy('number');
    }

    public function receipts()
    {
        return $this->hasMany(Receipt::class, 'contract_id')->orderBy('date')->orderBy('id');
    }

    /** Paid towards the installments, not counting the down payment or late fees. */
    public function paid(): int
    {
        return (int) $this->schedule->sum('paid');
    }

    public function outstanding(): int
    {
        return (int) $this->schedule->sum(fn ($s) => $s->amount - $s->paid);
    }

    /** Installment money due by the given day (after the grace days) and not yet paid. */
    public function overdue(?Carbon $on = null): int
    {
        $limit = ($on ?? today())->copy()->subDays($this->grace_days);

        return (int) $this->schedule->filter(fn ($s) => $s->due_on->lte($limit))->sum(fn ($s) => $s->amount - $s->paid);
    }

    public function lateInstallments(?Carbon $on = null): int
    {
        $limit = ($on ?? today())->copy()->subDays($this->grace_days);

        return $this->schedule->filter(fn ($s) => $s->paid < $s->amount && $s->due_on->lt($limit))->count();
    }

    public function nextDue(): ?ScheduleItem
    {
        return $this->schedule->first(fn ($s) => $s->paid < $s->amount);
    }

    /** Days since the oldest unpaid installment fell due, or 0. */
    public function daysLate(?Carbon $on = null): int
    {
        $next = $this->nextDue();

        return $next && $next->due_on->lt($on ?? today()) ? (int) $next->due_on->diffInDays($on ?? today()) : 0;
    }

    public function isOpen(): bool
    {
        return $this->status === 'active';
    }

    public function salesman()
    {
        return $this->belongsTo(Employee::class, 'salesman_id');
    }
}
