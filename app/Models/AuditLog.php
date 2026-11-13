<?php

namespace App\Models;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use LogicException;

/** Append-only: the application can write entries but never change them. */
class AuditLog extends Model
{
    use BelongsToSchool;

    public const UPDATED_AT = null;

    protected $guarded = [];

    protected $casts = ['changes' => 'array', 'created_at' => 'datetime'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Audit logs cannot be changed.'));
        static::deleting(fn () => throw new LogicException('Audit logs cannot be deleted.'));
    }

    public static function record(string $action, ?Model $subject, ?array $old = null, ?array $new = null, ?string $summary = null): void
    {
        $user = Auth::user();

        static::create([
            'school_id' => $subject?->school_id ?? $user?->school_id,
            'user_id' => $user?->id,
            'user_name' => $user?->name ?? 'System',
            'action' => $action,
            'subject_type' => $subject ? class_basename($subject) : null,
            'subject_id' => $subject?->getKey(),
            'summary' => $summary,
            'changes' => ($old || $new) ? ['old' => $old, 'new' => $new] : null,
            'ip' => request()?->ip(),
        ]);
    }
}
