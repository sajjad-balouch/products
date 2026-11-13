<?php

namespace App\Models\Biz;

use App\Models\Concerns\Audited;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** Stock coming in: how many pieces of a product, at what cost each. */
class Purchase extends Model
{
    use Audited, BelongsToSchool;

    protected $table = 'biz_purchases';

    protected $guarded = ['id'];

    protected $casts = ['date' => 'date'];

    /** opening: stock that was already in the shop when the business started using the system. */
    public const PAID_BY = ['cash' => 'Cash', 'bank' => 'Bank', 'credit' => 'On credit (owed to supplier)', 'opening' => 'Already in the shop (opening stock)'];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id');
    }

    public function units()
    {
        return $this->hasMany(StockUnit::class, 'purchase_id');
    }

    public function total(): int
    {
        return $this->quantity * $this->unit_cost;
    }

    /** How much of this purchase was left owing to the vendor (0 when fully paid). */
    public function owed(): int
    {
        if (! $this->journal_entry_id) {
            return 0;
        }

        return (int) JournalLine::where('entry_id', $this->journal_entry_id)
            ->whereHas('account', fn ($q) => $q->where('system_key', 'payable'))->sum('credit');
    }

    /** "Cash", "Bank", "On credit", or "Cash 5,000 + owed 3,000" when part paid. */
    public function paymentLabel(): string
    {
        $owed = in_array($this->paid_by, ['cash', 'bank'], true) ? $this->owed() : 0;
        $label = self::PAID_BY[$this->paid_by] ?? $this->paid_by;

        return $owed > 0 ? $label.' '.number_format($this->total() - $owed).' + owed '.number_format($owed) : $label;
    }
}
