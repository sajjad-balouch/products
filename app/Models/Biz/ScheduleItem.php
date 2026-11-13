<?php

namespace App\Models\Biz;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** One installment of a contract. */
class ScheduleItem extends Model
{
    use BelongsToSchool;

    protected $table = 'biz_schedule';

    protected $guarded = ['id'];

    public $timestamps = false;

    protected $casts = ['due_on' => 'date', 'paid_on' => 'date'];

    public function contract()
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    public function status(): string
    {
        return match (true) {
            $this->paid >= $this->amount => 'paid',
            $this->due_on->lt(today()) => 'late',
            $this->paid > 0 => 'part',
            $this->due_on->isToday() => 'today',
            default => 'upcoming',
        };
    }
}
