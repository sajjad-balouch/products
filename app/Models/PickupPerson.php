<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

/** Someone other than the main guardian whom a family allows to pick up its children. */
class PickupPerson extends Model
{
    use Audited, BelongsToSchool;

    protected $table = 'pickup_people';

    protected $guarded = [];

    public const RELATIONS = ['Father', 'Mother', 'Grandfather', 'Grandmother', 'Uncle', 'Aunt', 'Brother', 'Sister', 'Driver', 'Guardian', 'Other'];

    public function guardian()
    {
        return $this->belongsTo(Guardian::class);
    }

    public function cardKey(): string
    {
        return 'pickup:'.$this->id;
    }
}
