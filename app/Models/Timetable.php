<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class Timetable extends Model
{
    use BelongsToSchool;

    protected $guarded = [];

    protected $casts = ['is_active' => 'boolean'];

    public function periods()
    {
        return $this->hasMany(TimetablePeriod::class)->orderBy('starts_at');
    }
}
