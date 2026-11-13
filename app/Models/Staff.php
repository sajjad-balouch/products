<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use App\Services\BiometricService;
use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    use BelongsToSchool;

    /** Groups on the staff attendance sheet. */
    public const CATEGORIES = [
        'teaching' => 'Teaching',
        'office' => 'Office',
        'support' => 'Support (attendants, peons, cleaners)',
        'transport' => 'Transport (drivers, conductors)',
        'security' => 'Security (guards)',
    ];

    protected $guarded = [];

    protected $table = 'staff';

    protected $casts = ['joined_on' => 'date', 'is_teacher' => 'boolean'];

    protected static function booted(): void
    {
        // Punches that arrived before this machine id was entered are matched to the person now.
        static::saved(function (Staff $staff) {
            if ($staff->biometric_id && $staff->wasChanged('biometric_id') || ($staff->wasRecentlyCreated && $staff->biometric_id)) {
                app(BiometricService::class)->claim($staff);
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function dutyStart(School $school): string
    {
        return $this->duty_starts_at ?: $school->staff_starts_at;
    }

    public function dutyEnd(School $school): string
    {
        return $this->duty_ends_at ?: $school->staff_ends_at;
    }
}
