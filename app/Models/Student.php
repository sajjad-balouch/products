<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
    use Audited, BelongsToSchool;

    protected $guarded = [];

    protected $casts = ['dob' => 'date', 'admitted_on' => 'date', 'left_on' => 'date'];

    public function guardian()
    {
        return $this->belongsTo(Guardian::class);
    }

    public function cardKey(): string
    {
        return 'student:'.$this->id;
    }

    public function section()
    {
        return $this->belongsTo(Section::class);
    }

    public function transportRoute()
    {
        return $this->belongsTo(TransportRoute::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function marks()
    {
        return $this->hasMany(Mark::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
}
