<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class TransportRoute extends Model
{
    use BelongsToSchool;

    protected $guarded = [];

    public function students()
    {
        return $this->hasMany(Student::class);
    }
}
