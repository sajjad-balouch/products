<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class Section extends Model
{
    use BelongsToSchool;

    protected $guarded = [];

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function classTeacher()
    {
        return $this->belongsTo(Staff::class, 'class_teacher_id');
    }

    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public function getLabelAttribute(): string
    {
        return $this->schoolClass->label.' - '.$this->name;
    }
}
