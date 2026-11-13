<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class TimetablePeriod extends Model
{
    use BelongsToSchool;

    protected $guarded = [];

    protected $casts = ['is_break' => 'boolean'];

    public function timetable()
    {
        return $this->belongsTo(Timetable::class);
    }

    public function entries()
    {
        return $this->hasMany(TimetableEntry::class);
    }
}
