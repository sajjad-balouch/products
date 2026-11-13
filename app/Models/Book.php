<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class Book extends Model
{
    use BelongsToSchool;

    protected $guarded = [];

    public function loans()
    {
        return $this->hasMany(BookLoan::class);
    }
}
