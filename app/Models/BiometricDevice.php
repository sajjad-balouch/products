<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class BiometricDevice extends Model
{
    use Audited;
    use BelongsToSchool;

    protected $guarded = ['id'];

    protected $hidden = ['token_hash'];

    protected $casts = ['is_active' => 'boolean', 'last_seen_at' => 'datetime'];

    public function punches()
    {
        return $this->hasMany(BiometricPunch::class);
    }
}
