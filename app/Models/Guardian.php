<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class Guardian extends Model
{
    use BelongsToSchool;

    protected $guarded = [];

    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public function vouchers()
    {
        return $this->hasMany(FeeVoucher::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function pickupPeople()
    {
        return $this->hasMany(PickupPerson::class)->orderBy('id');
    }

    public function cardKey(): string
    {
        return 'guardian:'.$this->id;
    }
}
