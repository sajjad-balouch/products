<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class AcademicSession extends Model
{
    use BelongsToSchool;

    protected $guarded = [];

    protected $casts = ['starts_on' => 'date', 'ends_on' => 'date', 'is_current' => 'boolean'];
}
