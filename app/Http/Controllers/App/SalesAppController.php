<?php

namespace App\Http\Controllers\App;

use App\Http\Controllers\Controller;
use App\Models\Biz\Customer;
use App\Models\Biz\Invoice;
use App\Models\Biz\Order;
use App\Models\Biz\Product;
use App\Models\User;
use App\Services\Biz\Orders;
use App\Support\AppTokens;
use App\Support\BusinessKinds;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use InvalidArgumentException;

/**
 * The JSON the salesman / owner phone app talks to. Everything the app needs to work offline
 * comes from catalog(); orders made offline arrive later through store(), any number at once.
 */
class SalesAppController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $data = $request->validate(['username' => 'required|string|max:120', 'password' => 'required|string|max:200', 'device' => 'nullable|string|max:120']);
        $key = 'app-login:'.Str::lower($data['username']).'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, 6)) {
            return response()->json(['message' => 'Too many tries. Wait '.RateLimiter::availableIn($key).' seconds.'], 429);
        }

        $user = User::where('username', Str::lower(trim($data['username'])))->first();
        if (! $user || ! Hash::check($data['password'], $user->password)) {
            RateLimiter::hit($key, 60);

            return response()->json(['message' => 'Wrong login or password. · Login ya password ghalat hai.'], 422);
        }
        if (! AppTokens::allowed($user)) {
            return response()->json(['message' => 'This login cannot use the sales app. Ask the owner to give you an app login.'], 403);
        }
        RateLimiter::clear($key);

        return response()->json(['token' => AppTokens::issue($user, $data['device'] ?? null), 'me' => $this->profile($user)]);
    }

    public function logout(Request $request): JsonResponse
    {
        AppTokens::revoke((string) $request->bearerToken());

        return response()->json(['ok' => true]);
    }

    private function profile(User $user): array
    {
        $school = $user->school;

        return [
            'name' => $user->name,
            'role' => $user->role === 'salesman' ? 'salesman' : 'owner',
            'business' => $school->name,
            'kind' => BusinessKinds::label(BusinessKinds::of($school)),
            'phone' => $school->phone,
        ];
    }

    /** Products with prices and stock, and customers: kept on the phone for offline orders. */
    public function catalog(): JsonResponse
    {
        $user = Auth::user();
        $products = Product::with('category')->where('is_active', true)
            ->withCount(['units as in_stock' => fn ($q) => $q->where('status', 'in_stock')])
            ->orderBy('brand')->orderBy('name')->get()
            ->map(fn ($p) => ['id' => $p->id, 'label' => $p->label(), 'category' => $p->category?->name, 'retail' => (int) $p->sale_price,
                'wholesale' => (int) ($p->wholesale_price ?: $p->sale_price), 'stock' => (int) $p->in_stock, 'code' => $p->code]);
        // What each customer still owes on credit sales (udhaar), shown on the customer's page.
        $due = Invoice::whereNull('voided_at')->whereColumn('paid', '<', 'total')->groupBy('customer_id')
            ->selectRaw('customer_id, SUM(total - paid) as due')->pluck('due', 'customer_id');
        $customers = Customer::orderBy('name')->get(['id', 'code', 'name', 'phone', 'address'])
            ->map(fn ($c) => ['id' => $c->id, 'code' => $c->code, 'name' => $c->name, 'phone' => $c->phone, 'address' => $c->address, 'due' => (int) ($due[$c->id] ?? 0)]);

        return response()->json(['me' => $this->profile($user), 'products' => $products, 'customers' => $customers, 'at' => now()->toIso8601String()]);
    }

    /** A salesman sees his own orders; the owner sees everyone's, newest first. */
    public function orders(Request $request): JsonResponse
    {
        $user = Auth::user();
        $orders = Order::with(['lines', 'customer', 'salesman', 'invoice'])
            ->when($user->role === 'salesman', fn ($q) => $q->where('user_id', $user->id))
            ->when($request->query('status'), fn ($q, $s) => $q->where('status', $s))
            ->latest('ordered_at')->latest('id')->limit(150)->get();
        $today = Order::when($user->role === 'salesman', fn ($q) => $q->where('user_id', $user->id))
            ->where('status', '!=', 'cancelled')->whereDate('ordered_at', today());

        return response()->json([
            'orders' => $orders->map->toApp(),
            'summary' => [
                'new' => Order::when($user->role === 'salesman', fn ($q) => $q->where('user_id', $user->id))->where('status', 'new')->count(),
                'today_count' => (clone $today)->count(),
                'today_total' => (int) (clone $today)->sum('total'),
            ],
        ]);
    }

    /** Orders from the phone, often several made offline. Each one saves or fails on its own. */
    public function store(Request $request, Orders $service): JsonResponse
    {
        $request->validate(['orders' => 'required|array|min:1|max:100']);
        $user = Auth::user();
        $results = [];
        foreach ($request->input('orders') as $data) {
            $uuid = is_array($data) ? (string) ($data['uuid'] ?? '') : '';
            try {
                $order = $service->place($user->school, $user, is_array($data) ? $data : []);
                $results[] = ['uuid' => $uuid, 'ok' => true, 'order' => $order->toApp()];
            } catch (InvalidArgumentException $e) {
                $results[] = ['uuid' => $uuid, 'ok' => false, 'message' => $e->getMessage()];
            }
        }

        return response()->json(['results' => $results]);
    }

    /** The owner accepts or cancels an order from the phone. */
    public function update(Request $request, int $order, Orders $service): JsonResponse
    {
        abort_unless(Auth::user()->role !== 'salesman', 403);
        // Looked up here, after sign-in, so it is always this business's order.
        $order = Order::findOrFail($order);
        $data = $request->validate(['action' => 'required|in:accept,cancel', 'reason' => 'nullable|string|max:255']);
        try {
            $data['action'] === 'accept' ? $service->accept($order) : $service->cancel($order, $data['reason'] ?? '');
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['order' => $order->fresh(['lines', 'customer', 'salesman', 'invoice'])->toApp()]);
    }
}
