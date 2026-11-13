<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class TimetableEntry extends Model
{
    use BelongsToSchool;

    protected $guarded = [];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function period()
    {
        return $this->belongsTo(TimetablePeriod::class, 'timetable_period_id');
    }

    /** Entries of the timetable in use. Drafts stay hidden from teachers and parents. */
    public function scopeLive($query)
    {
        return $query->whereHas('period.timetable', fn ($q) => $q->where('is_active', true));
    }
}
