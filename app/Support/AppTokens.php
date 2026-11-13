<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Sign-in for the phone app. The phone keeps a random token; we keep only its hash.
 * Tokens do not expire on their own, so a salesman offline for days stays signed in;
 * signing out, or the owner switching the login off, ends it.
 */
class AppTokens
{
    public static function issue(User $user, ?string $device = null): string
    {
        $plain = Str::random(48);
        DB::table('biz_app_tokens')->insert(['user_id' => $user->id, 'token_hash' => hash('sha256', $plain), 'device' => $device ? mb_substr($device, 0, 120) : null, 'last_used_at' => now(), 'created_at' => now(), 'updated_at' => now()]);

        return $plain;
    }

    public static function user(?string $plain): ?User
    {
        if (! $plain || strlen($plain) < 40) {
            return null;
        }
        $row = DB::table('biz_app_tokens')->where('token_hash', hash('sha256', $plain))->first();
        if (! $row) {
            return null;
        }
        if (! $row->last_used_at || now()->diffInMinutes($row->last_used_at, true) > 10) {
            DB::table('biz_app_tokens')->where('id', $row->id)->update(['last_used_at' => now()]);
        }

        return User::find($row->user_id);
    }

    public static function revoke(string $plain): void
    {
        DB::table('biz_app_tokens')->where('token_hash', hash('sha256', $plain))->delete();
    }

    public static function revokeAll(User $user): void
    {
        DB::table('biz_app_tokens')->where('user_id', $user->id)->delete();
    }

    /** Who may use the app: a business's owner (admin) or one of its salesmen. */
    public static function allowed(?User $user): bool
    {
        return $user && $user->is_active !== false && $user->school?->isBusiness() && $user->school->is_active !== false
            && ! $user->school->isLocked() && in_array($user->role, ['admin', 'salesman'], true) && $user->school->hasFeature('sales');
    }
}
