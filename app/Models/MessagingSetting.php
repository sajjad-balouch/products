<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** How messages go out, for the whole platform (one row). Tokens are kept encrypted. */
class MessagingSetting extends Model
{
    protected $fillable = ['driver', 'config', 'school_daily_limit'];

    protected $casts = ['config' => 'encrypted:array', 'school_daily_limit' => 'integer'];

    public static function current(): self
    {
        return self::query()->first() ?? self::create(['driver' => 'off', 'config' => [], 'school_daily_limit' => 500]);
    }

    public function get(string $key, $default = null)
    {
        $value = ($this->config ?? [])[$key] ?? null;

        return $value === null || $value === '' ? $default : $value;
    }
}
