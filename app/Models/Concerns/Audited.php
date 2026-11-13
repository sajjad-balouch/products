<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;

/** Records every create, change and delete on sensitive models. */
trait Audited
{
    protected static function bootAudited(): void
    {
        static::created(fn ($m) => AuditLog::record('created', $m, null, $m->auditable($m->getAttributes())));

        static::updated(function ($m) {
            $changes = $m->auditable($m->getChanges());
            if ($changes) {
                AuditLog::record('updated', $m, array_intersect_key($m->getOriginal(), $changes), $changes);
            }
        });

        static::deleted(fn ($m) => AuditLog::record('deleted', $m, $m->auditable($m->getAttributes()), null));
    }

    protected function auditable(array $attributes): array
    {
        return array_diff_key($attributes, array_flip(['updated_at', 'created_at', 'password', 'remember_token']));
    }
}
