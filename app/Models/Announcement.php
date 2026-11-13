<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class Announcement extends Model
{
    use BelongsToSchool;

    protected $guarded = [];

    protected $casts = ['published_on' => 'date'];
}
