<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** Every change is audited, so no one can quietly alter a day's attendance. */
class StaffAttendance extends Model
{
    use Audited;
    use BelongsToSchool;

    protected $guarded = [];

    protected $casts = ['date' => 'date'];
}
