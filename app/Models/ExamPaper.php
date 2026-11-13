<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class ExamPaper extends Model
{
    use BelongsToSchool;

    protected $guarded = [];

    protected $casts = ['paper_on' => 'date'];

    public function exam()
    {
        return $this->belongsTo(Exam::class);
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function marks()
    {
        return $this->hasMany(Mark::class);
    }
}
