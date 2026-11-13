<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    use BelongsToSchool;

    protected $guarded = [];

    protected $casts = ['starts_on' => 'date', 'is_published' => 'boolean'];

    public function papers()
    {
        return $this->hasMany(ExamPaper::class);
    }

    public function session()
    {
        return $this->belongsTo(AcademicSession::class, 'academic_session_id');
    }
}
