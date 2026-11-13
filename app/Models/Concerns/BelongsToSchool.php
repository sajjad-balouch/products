<?php

namespace App\Models\Concerns;

use App\Models\School;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Tenant isolation: every query is limited to the signed-in user's school and
 * new rows are stamped with it. Super admins (no school) and CLI jobs see all.
 */
trait BelongsToSchool
{
    protected static function bootBelongsToSchool(): void
    {
        static::addGlobalScope('school', function (Builder $query) {
            if ($schoolId = Auth::user()?->school_id) {
                $query->where($query->getModel()->getTable().'.school_id', $schoolId);
            }
        });

        static::creating(function ($model) {
            if (! $model->school_id && ($schoolId = Auth::user()?->school_id)) {
                $model->school_id = $schoolId;
            }
        });
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }
}
