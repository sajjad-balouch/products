<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class Certificate extends Model
{
    use BelongsToSchool;

    protected $guarded = [];

    protected $casts = ['issued_on' => 'date'];

    public const TYPES = ['leaving' => 'School Leaving Certificate', 'character' => 'Character Certificate', 'bonafide' => 'Bonafide Certificate'];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
