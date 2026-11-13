<?php

namespace App\Support;

use App\Models\Biz\Order;
use App\Models\Inquiry;
use App\Models\Message;
use App\Models\PaymentProof;
use App\Models\Site;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * The little red count beside a menu link: things waiting for someone. Only links in this user's
 * menu are counted, each with one small query, and the whole set once per request.
 */
class Badges
{
    /** What each count means, for the bell's list. */
    public const LABELS = [
        'biz.orders' => 'new orders from salesmen',
        'biz.due' => 'installment plans late',
        'messages.index' => 'messages to send',
        'fees.proofs' => 'payment proofs to check',
        'admissions' => 'new admission inquiries',
        'website.messages' => 'website messages unread',
        'platform.messaging' => 'messages not sent today',
    ];

    /** @return array<string, int> route => count, only counts above zero */
    public static function for(?User $user): array
    {
        if (! $user) {
            return [];
        }
        if (app()->bound('nav.badges')) {
            return app('nav.badges');
        }

        $routes = array_flip(Navigation::routes($user));
        $count = fn (string $route, callable $query) => isset($routes[$route]) ? (int) $query() : 0;
        $school = $user->school;

        $badges = array_filter([
            'biz.orders' => $count('biz.orders', fn () => Order::where('status', 'new')->count()),
            'biz.due' => $count('biz.due', fn () => DB::table('biz_contracts as c')->join('biz_schedule as s', 's.contract_id', '=', 'c.id')
                ->where('c.school_id', $school?->id)->where('c.status', 'active')->whereColumn('s.paid', '<', 's.amount')
                ->whereRaw('s.due_on <= DATE_SUB(CURDATE(), INTERVAL c.grace_days DAY)')->distinct()->count('c.id')),
            // Waiting in the WhatsApp outbox, and automatic ones that did not go.
            'messages.index' => $count('messages.index', fn () => Message::where(fn ($w) => $w->where(fn ($q) => $q->where('status', 'queued')->where('channel', 'whatsapp'))
                ->orWhere(fn ($q) => $q->where('status', 'failed')->where('category', '!=', 'test')))->count()),
            'fees.proofs' => $count('fees.proofs', fn () => PaymentProof::where('status', 'pending')->count()),
            'admissions' => $count('admissions', fn () => Inquiry::where('stage', 'new')->count()),
            'website.messages' => $count('website.messages', fn () => Site::where('owner_key', $school ? 'school:'.$school->id : 'platform')->first()?->messages()->whereNull('read_at')->count() ?? 0),
            'platform.messaging' => $count('platform.messaging', fn () => Message::withoutGlobalScopes()->where('status', 'failed')->where('updated_at', '>=', today())->count()),
        ]);

        app()->instance('nav.badges', $badges);

        return $badges;
    }

    /** For the bell: [label, route, count], biggest first. */
    public static function list(?User $user): array
    {
        return collect(self::for($user))->sortDesc()
            ->map(fn ($n, $route) => ['label' => $n.' '.(self::LABELS[$route] ?? ''), 'route' => $route, 'count' => $n, 'url' => route($route)])
            ->values()->all();
    }

    /** How a count is written in a bubble: 1 to 99, then 99+. */
    public static function text(int $n): string
    {
        return $n > 99 ? '99+' : (string) $n;
    }
}
