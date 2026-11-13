<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class BiometricPunch extends Model
{
    use BelongsToSchool;

    protected $guarded = ['id'];

    protected $casts = ['punched_at' => 'datetime'];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    public function device()
    {
        return $this->belongsTo(BiometricDevice::class, 'biometric_device_id');
    }
}
