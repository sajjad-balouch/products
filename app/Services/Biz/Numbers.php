<?php

namespace App\Services\Biz;

use App\Models\School;
use Illuminate\Support\Facades\DB;

/** Running document numbers per business: C-0001, IS-0001, R-000001, JV-00001... */
class Numbers
{
    /** Call inside a transaction. The business row is locked so two people never get the same number. */
    public static function next(School $school, string $table, string $prefix, int $pad, array $where = [], ?string $column = null): string
    {
        School::whereKey($school->id)->lockForUpdate()->first();
        $column ??= $table === 'biz_customers' ? 'code' : 'number';

        $last = DB::table($table)->where('school_id', $school->id)->where($where)->where($column, 'like', $prefix.'%')
            ->orderByDesc('id')->value($column);
        $n = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix.str_pad((string) $n, $pad, '0', STR_PAD_LEFT);
    }
}
