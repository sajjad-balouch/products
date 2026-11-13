<?php

namespace App\Models;

use App\Models\Concerns\Audited;
use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class FingerprintTemplate extends Model
{
    use Audited;
    use BelongsToSchool;

    public const FINGERS = [
        'right-thumb' => 'Right thumb',
        'right-index' => 'Right index',
        'right-middle' => 'Right middle',
        'left-thumb' => 'Left thumb',
        'left-index' => 'Left index',
        'left-middle' => 'Left middle',
    ];

    protected $guarded = ['id'];

    protected $hidden = ['template'];

    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    /** The audit log notes that a finger was added or removed, never the template itself. */
    protected function auditable(array $attributes): array
    {
        return array_diff_key($attributes, array_flip(['template', 'updated_at', 'created_at']));
    }
}
