<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use Audited;

    protected $guarded = ['id'];

    protected $casts = ['features' => 'array'];

    public function schools()
    {
        return $this->hasMany(School::class, 'plan', 'code');
    }

    /** Sets the school to this plan: its features, and its fee unless the school keeps its own. */
    public function applyTo(School $school, bool $keepFee = false): void
    {
        $school->update([
            'plan' => $this->code,
            'monthly_fee' => $keepFee ? $school->monthly_fee : $this->monthly_price,
            'features_off' => array_values(array_diff(array_keys(School::featuresFor($this->product ?? 'school')), $this->features)),
        ]);

        AuditLog::record('plan_applied', $school, null, null, $school->name.' moved to the '.$this->name.' plan');
    }
}
