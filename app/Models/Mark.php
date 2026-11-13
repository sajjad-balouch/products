<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class Mark extends Model
{
    use Audited, BelongsToSchool;

    protected $guarded = [];

    protected $casts = ['obtained' => 'float'];

    public function paper()
    {
        return $this->belongsTo(ExamPaper::class, 'exam_paper_id');
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
