<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class Inquiry extends Model
{
    use BelongsToSchool;

    protected $guarded = [];

    protected $casts = ['follow_up_on' => 'date'];

    public const STAGES = ['new' => 'New', 'contacted' => 'Contacted', 'visited' => 'Visited', 'tested' => 'Test taken', 'admitted' => 'Admitted', 'lost' => 'Lost'];

    public function schoolClass()
    {
        return $this->belongsTo(SchoolClass::class);
    }
}
