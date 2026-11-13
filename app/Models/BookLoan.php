<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class BookLoan extends Model
{
    use BelongsToSchool;

    protected $guarded = [];

    protected $casts = ['issued_on' => 'date', 'due_on' => 'date', 'returned_on' => 'date'];

    public function book()
    {
        return $this->belongsTo(Book::class);
    }

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}
