<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class SchoolClass extends Model
{
    use BelongsToSchool;

    protected $guarded = [];

    public function sections()
    {
        return $this->hasMany(Section::class);
    }

    public function feeStructures()
    {
        return $this->hasMany(FeeStructure::class);
    }

    public function getLabelAttribute(): string
    {
        return $this->stream ? "{$this->name} ({$this->stream})" : $this->name;
    }
}
