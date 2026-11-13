<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#0b3b33">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <title>{{ $appName }}</title>
    <link rel="manifest" href="{{ route('app.manifest') }}">
    <link rel="icon" href="{{ asset('app-icons/icon-192.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('app-icons/icon-192.png') }}">
    <script>
        window.SALES_APP = @json($config);
    </script>
    @vite(['resources/css/app.css', 'resources/js/sales-app.js'])
    <style>
        [x-cloak] { display: none !important; }
        html, body { height: 100%; overscroll-behavior-y: none; -webkit-tap-highlight-color: transparent; }
        body { font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Noto Sans", sans-serif; user-select: none; }
        input, textarea { user-select: text; }
        .safe-top { padding-top: max(env(safe-area-inset-top), 0.5rem); }
        .safe-bottom { padding-bottom: max(env(safe-area-inset-bottom), 0.35rem); }
        .no-bar::-webkit-scrollbar { display: none; }
        .no-bar { scrollbar-width: none; }
        /* A box with its icon inside on the left, and its hint underneath. */
        .ibox { position: relative; }
        .ibox > i { position: absolute; inset-inline-start: 0.85rem; top: 50%; transform: translateY(-50%); color: #64748b; pointer-events: none; display: grid; }
        .ibox > .input { padding-inline-start: 2.7rem; }
        .hint { margin-top: 0.3rem; display: flex; gap: 0.35rem; font-size: 0.72rem; line-height: 1.35; color: #64748b; }
        .hint > i { flex-shrink: 0; margin-top: 0.05rem; color: #0f766e; display: grid; }
        .press { transition: transform .08s ease; } .press:active { transform: scale(.97); }
        .card { background: #fff; border-radius: 1.25rem; box-shadow: 0 1px 2px rgb(15 23 42 / .06); outline: 1px solid rgb(226 232 240); }
        .section-title { display: flex; align-items: center; justify-content: space-between; padding: 0 .25rem .5rem; font-size: .8rem; font-weight: 700; letter-spacing: .02em; color: #475569; text-transform: uppercase; }
        .sheet-enter { transition: transform .22s cubic-bezier(.2,.8,.2,1), opacity .22s; }
    </style>
</head>
<body class="bg-slate-100 text-slate-900 antialiased">
<div x-data="salesApp()" class="relative mx-auto h-full max-w-xl overflow-hidden bg-slate-100">

    {{-- ================= Sign in ================= --}}
    <template x-if="!token">
        <div class="flex h-full flex-col overflow-y-auto bg-gradient-to-b from-brand-900 via-brand-800 to-brand-700 text-white">
            <div class="flex flex-1 flex-col items-center justify-center px-6 pt-14 pb-8">
                <div class="grid h-24 w-24 place-items-center rounded-[1.75rem] bg-white shadow-2xl shadow-black/30">
                    <img src="{{ asset('app-icons/icon-192.png') }}" alt="" class="h-20 w-20 rounded-3xl">
                </div>
                <h1 class="mt-5 text-3xl font-bold tracking-tight">{{ $appName }}</h1>
                <p class="mt-1.5 text-center text-brand-100">Take orders anywhere, even without internet.<br><span class="text-brand-200">Kahin se bhi order, seedha maalik tak.</span></p>
            </div>
            <form x-on:submit.prevent="signIn()" class="safe-bottom space-y-4 rounded-t-[2rem] bg-white px-5 pt-6 pb-8 text-slate-900 shadow-2xl">
                <div>
                    <h2 class="text-xl font-bold">Sign in</h2>
                    <p class="text-sm text-slate-500">With the login your owner gave you.</p>
                </div>
                <div>
                    <div class="ibox"><i x-html="ic('user', 'h-5 w-5')"></i><input x-model="login.username" class="input rounded-2xl py-3.5 text-base" autocomplete="username" inputmode="email" placeholder="Phone or email" required></div>
                    <p class="hint"><i x-html="ic('info', 'h-3.5 w-3.5')"></i><span>Jo login maalik ne diya.</span></p>
                </div>
                <div>
                    <div class="ibox">
                        <i x-html="ic('lock', 'h-5 w-5')"></i>
                        <input x-model="login.password" :type="login.show ? 'text' : 'password'" class="input rounded-2xl py-3.5 pe-16 text-base" autocomplete="current-password" placeholder="Password" required>
                        <button type="button" x-on:click="login.show = !login.show" class="absolute inset-y-0 end-0 px-4 text-sm font-semibold text-brand-700" x-text="login.show ? 'Hide' : 'Show'"></button>
                    </div>
                    <p class="hint"><i x-html="ic('info', 'h-3.5 w-3.5')"></i><span>Bhool gaye to maalik se naya password lein.</span></p>
                </div>
                <p x-show="login.error" class="flex gap-2 rounded-2xl bg-red-50 p-3 text-sm text-red-700"><i x-html="ic('xCircle', 'h-5 w-5 shrink-0')"></i><span x-text="login.error"></span></p>
                <button class="press flex w-full items-center justify-center gap-2 rounded-2xl bg-brand-600 py-4 text-base font-semibold text-white shadow-lg shadow-brand-600/30 disabled:opacity-60" :disabled="login.busy">
                    <i x-show="login.busy" x-html="ic('refresh', 'h-5 w-5 animate-spin')"></i><span x-text="login.busy ? 'Signing in…' : 'Sign in'"></span>
                </button>
                <p class="flex items-center justify-center gap-1.5 text-xs text-slate-500"><i x-html="ic('wifi', 'h-3.5 w-3.5')"></i>Internet is needed only the first time.</p>
            </form>
        </div>
    </template>

    {{-- ================= The app ================= --}}
    <template x-if="token">
        <div class="flex h-full flex-col">

            {{-- Top app bar --}}
            <header class="safe-top z-10 shrink-0 bg-brand-900 text-white">
                <div class="flex h-14 items-center gap-3 px-4">
                    <template x-if="tab === 'home'">
                        <div class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-white/15" x-html="ic('store', 'h-5 w-5')"></div>
                    </template>
                    <h1 class="min-w-0 flex-1 truncate text-lg font-semibold" x-text="title"></h1>
                    <button x-on:click="sync(true)" class="press flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium" :class="online ? 'bg-white/10' : 'bg-amber-400 text-amber-950'" aria-label="Update now">
                        <i x-html="ic(syncing ? 'refresh' : (online ? 'wifi' : 'wifiOff'), 'h-4 w-4 ' + (syncing ? 'animate-spin' : ''))"></i>
                        <span x-text="syncing ? 'Updating' : (online ? 'Online' : 'Offline')"></span>
                    </button>
                    <button x-on:click="openBell()" class="press relative grid h-9 w-9 shrink-0 place-items-center rounded-full bg-white/10" aria-label="Notifications">
                        <i x-html="ic('bell', 'h-5 w-5')"></i>
                        <span x-show="badge" class="absolute -top-1 -right-1 grid h-5 min-w-5 place-items-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white ring-2 ring-brand-900" x-text="bubble(badge)"></span>
                    </button>
                    <button x-on:click="go('more')" class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-white text-sm font-bold text-brand-800" x-text="initials(me?.name)" aria-label="Account"></button>
                </div>
            </header>
            <div x-show="outbox.length" x-cloak class="flex shrink-0 items-center gap-2 bg-amber-100 px-4 py-2 text-sm text-amber-900">
                <i x-html="ic('wifiOff', 'h-4 w-4 shrink-0')"></i>
                <span class="flex-1"><b x-text="outbox.length"></b> order(s) on the phone, not sent yet. They go by themselves with internet.</span>
            </div>

            <main id="main" class="no-bar relative min-h-0 flex-1 overflow-y-auto pb-28" x-on:touchstart.passive="pullStart($event)" x-on:touchmove.passive="pullMove($event)" x-on:touchend="pullEnd()">
                {{-- Pull to refresh --}}
                <div class="flex justify-center overflow-hidden transition-[height]" :style="'height:' + (syncing && !pull ? 0 : pull) + 'px'">
                    <div class="mt-2 grid h-9 w-9 place-items-center rounded-full bg-white text-brand-700 shadow" :style="'transform: rotate(' + pull * 4 + 'deg)'" x-html="ic('refresh', 'h-5 w-5')"></div>
                </div>

                {{-- ---------- Home ---------- --}}
                <section x-show="tab === 'home'" class="space-y-5 p-4">
                    <div class="overflow-hidden rounded-[1.5rem] bg-gradient-to-br from-brand-700 to-brand-900 p-5 text-white shadow-lg shadow-brand-900/20">
                        <div class="text-sm text-brand-100" x-text="greeting + ','"></div>
                        <div class="text-2xl font-bold" x-text="firstName"></div>
                        <div class="mt-0.5 text-xs text-brand-200" x-text="(isOwner ? 'Owner' : 'Salesman') + ' · ' + (me?.business || '')"></div>
                        <div class="mt-5 grid grid-cols-3 divide-x divide-white/15 rounded-2xl bg-white/10 py-3 text-center">
                            <div><div class="text-xl font-bold" x-text="summary.today_count"></div><div class="text-[11px] text-brand-100">Orders today</div></div>
                            <div><div class="truncate px-1 text-xl font-bold" x-text="rs(summary.today_total).replace('Rs ', '')"></div><div class="text-[11px] text-brand-100">Rs today</div></div>
                            <div><div class="text-xl font-bold" x-text="isOwner ? summary.new : outbox.length"></div><div class="text-[11px] text-brand-100" x-text="isOwner ? 'To accept' : 'Not sent'"></div></div>
                        </div>
                    </div>

                    <div class="grid grid-cols-4 gap-2 text-center text-xs font-medium text-slate-700">
                        <button x-on:click="newOrder()" class="press flex flex-col items-center gap-1.5"><span class="grid h-14 w-14 place-items-center rounded-2xl bg-brand-600 text-white shadow-md shadow-brand-600/30" x-html="ic('cart', 'h-6 w-6')"></span>New order</button>
                        <button x-on:click="customerSearch = ''; open('pick'); $nextTick(() => startNewCustomer())" class="press flex flex-col items-center gap-1.5"><span class="grid h-14 w-14 place-items-center rounded-2xl bg-white text-brand-700 ring-1 ring-slate-200" x-html="ic('userPlus', 'h-6 w-6')"></span>New shop</button>
                        <button x-on:click="go('orders')" class="press flex flex-col items-center gap-1.5"><span class="grid h-14 w-14 place-items-center rounded-2xl bg-white text-brand-700 ring-1 ring-slate-200" x-html="ic('receipt', 'h-6 w-6')"></span>Orders</button>
                        <button x-on:click="sync(true)" class="press flex flex-col items-center gap-1.5"><span class="grid h-14 w-14 place-items-center rounded-2xl bg-white text-brand-700 ring-1 ring-slate-200" x-html="ic('refresh', 'h-6 w-6 ' + (syncing ? 'animate-spin' : ''))"></span>Update</button>
                    </div>

                    {{-- An order in progress --}}
                    <button x-show="cart.lines.length" x-cloak x-on:click="customerName ? open('cart') : open('pick')" class="press card flex w-full items-center gap-3 p-3.5 text-start">
                        <span class="grid h-11 w-11 place-items-center rounded-xl bg-amber-100 text-amber-700" x-html="ic('cart', 'h-5 w-5')"></span>
                        <span class="min-w-0 flex-1"><span class="block text-sm font-semibold">Order in progress</span><span class="block truncate text-xs text-slate-500" x-text="(customerName || 'No customer yet') + ' · ' + cartCount + ' pcs · ' + rs(cartTotal)"></span></span>
                        <span class="text-sm font-semibold text-brand-700">Continue</span>
                    </button>

                    <template x-if="isOwner && waitingForOwner.length">
                        <div>
                            <div class="section-title"><span>Waiting for you</span><button x-on:click="filter = 'new'; go('orders')" class="normal-case text-brand-700">See all</button></div>
                            <div class="card divide-y divide-slate-100 overflow-hidden">
                                <template x-for="o in waitingForOwner" :key="o.uuid">
                                    <button x-on:click="open('order', { uuid: o.uuid })" class="flex w-full items-center gap-3 px-3.5 py-3 text-start active:bg-slate-50">
                                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full text-sm font-bold" :class="toneOf(o.customer)" x-text="initials(o.customer)"></span>
                                        <span class="min-w-0 flex-1"><span class="block truncate text-sm font-semibold" x-text="o.customer"></span><span class="block truncate text-xs text-slate-500" x-text="(o.salesman || '') + ' · ' + time(o.ordered_at)"></span></span>
                                        <span class="text-sm font-bold" x-text="rs(o.total)"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>

                    <div>
                        <div class="section-title"><span>Recent orders</span><button x-show="allOrders.length" x-on:click="filter = 'all'; go('orders')" class="normal-case text-brand-700">See all</button></div>
                        <div class="card divide-y divide-slate-100 overflow-hidden">
                            <template x-for="o in recentOrders" :key="o.uuid">
                                <button x-on:click="open('order', { uuid: o.uuid })" class="flex w-full items-center gap-3 px-3.5 py-3 text-start active:bg-slate-50">
                                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl" :class="statusBg(o.status)" x-html="ic(statusIcon(o.status), 'h-5 w-5')"></span>
                                    <span class="min-w-0 flex-1"><span class="block truncate text-sm font-semibold" x-text="o.customer || 'Customer'"></span><span class="block text-xs text-slate-500" x-text="statusText(o.status) + ' · ' + when(o.ordered_at)"></span></span>
                                    <span class="text-sm font-bold" x-text="rs(o.total)"></span>
                                </button>
                            </template>
                            <div x-show="!recentOrders.length" class="flex flex-col items-center gap-2 p-8 text-center text-sm text-slate-500">
                                <span class="grid h-14 w-14 place-items-center rounded-2xl bg-slate-100 text-slate-400" x-html="ic('receipt', 'h-7 w-7')"></span>
                                No orders yet. Tap <b class="text-brand-700">+</b> to take the first one.
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div class="card p-3.5"><div class="flex items-center gap-1.5 text-xs text-slate-500"><i x-html="ic('chart', 'h-4 w-4')"></i>Last 7 days</div><div class="mt-1 truncate text-lg font-bold" x-text="rs(weekTotal)"></div></div>
                        <div class="card p-3.5"><div class="flex items-center gap-1.5 text-xs text-slate-500"><i x-html="ic('users', 'h-4 w-4')"></i>Customers</div><div class="mt-1 text-lg font-bold" x-text="customers.length"></div></div>
                    </div>
                </section>

                {{-- ---------- Orders ---------- --}}
                <section x-show="tab === 'orders'" class="space-y-4 p-4">
                    <div class="no-bar -mx-4 flex gap-2 overflow-x-auto px-4">
                        <template x-for="[key, label] in [['all', 'All'], ['waiting', 'Not sent'], ['new', 'New'], ['accepted', 'Accepted'], ['delivered', 'Sale made'], ['cancelled', 'Cancelled']]" :key="key">
                            <button x-on:click="filter = key" class="press flex shrink-0 items-center gap-1.5 rounded-full px-3.5 py-2 text-sm font-medium" :class="filter === key ? 'bg-brand-700 text-white' : 'bg-white text-slate-600 ring-1 ring-slate-200'">
                                <span x-text="label"></span><span class="rounded-full px-1.5 text-[11px]" :class="filter === key ? 'bg-white/20' : 'bg-slate-100'" x-text="count(key)"></span>
                            </button>
                        </template>
                    </div>

                    <template x-for="o in failed" :key="'f' + o.uuid">
                        <div class="rounded-2xl bg-red-50 p-3.5 text-sm ring-1 ring-red-200">
                            <div class="flex items-center gap-2 font-semibold text-red-800"><i x-html="ic('xCircle', 'h-5 w-5')"></i><span x-text="(o._customerName || 'Order') + ' · ' + rs(o._total)"></span></div>
                            <div class="mt-1 text-red-700" x-text="o.error"></div>
                            <div class="mt-2.5 flex gap-2"><button x-on:click="retryFailed(o)" class="btn btn-sm gap-1"><i x-html="ic('refresh', 'h-3.5 w-3.5')"></i>Try again</button><button x-on:click="dropFailed(o)" class="btn btn-sm btn-danger gap-1"><i x-html="ic('trash', 'h-3.5 w-3.5')"></i>Delete</button></div>
                        </div>
                    </template>

                    <template x-for="g in orderGroups" :key="g.key">
                        <div>
                            <div class="section-title"><span x-text="g.label"></span><span class="normal-case" x-text="g.orders.length + ' · ' + rs(g.total)"></span></div>
                            <div class="card divide-y divide-slate-100 overflow-hidden">
                                <template x-for="o in g.orders" :key="o.uuid">
                                    <button x-on:click="open('order', { uuid: o.uuid })" class="flex w-full items-center gap-3 px-3.5 py-3 text-start active:bg-slate-50">
                                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full text-sm font-bold" :class="toneOf(o.customer)" x-text="initials(o.customer)"></span>
                                        <span class="min-w-0 flex-1">
                                            <span class="block truncate font-semibold" x-text="o.customer || 'Customer'"></span>
                                            <span class="block truncate text-xs text-slate-500" x-text="o.number + ' · ' + time(o.ordered_at) + (isOwner && o.salesman ? ' · ' + o.salesman : '')"></span>
                                        </span>
                                        <span class="flex shrink-0 flex-col items-end gap-1">
                                            <span class="text-sm font-bold" x-text="rs(o.total)"></span>
                                            <span class="rounded-full px-2 py-0.5 text-[10px] font-semibold" :class="statusTone(o.status)" x-text="statusText(o.status)"></span>
                                        </span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>
                    <div x-show="!shownOrders.length && !failed.length" class="flex flex-col items-center gap-2 py-16 text-center text-sm text-slate-500">
                        <span class="grid h-16 w-16 place-items-center rounded-3xl bg-white text-slate-400 ring-1 ring-slate-200" x-html="ic('receipt', 'h-8 w-8')"></span>
                        <span class="font-semibold text-slate-700">No orders here</span>
                        <span>Pull down to update.</span>
                    </div>
                </section>

                {{-- ---------- Customers ---------- --}}
                <section x-show="tab === 'customers'" class="space-y-3 p-4">
                    <div>
                        <div class="ibox"><i x-html="ic('search', 'h-5 w-5')"></i><input x-model="customerSearch" type="search" placeholder="Search name, phone, area" class="input rounded-2xl border-0 py-3 text-base ring-1 ring-slate-200"></div>
                        <p class="hint"><i x-html="ic('info', 'h-3.5 w-3.5')"></i><span>Naam, phone ya ilaqa likhein. Dukaan khol kar order, call ya raasta.</span></p>
                    </div>
                    <div class="card divide-y divide-slate-100 overflow-hidden">
                        <template x-for="c in customerMatches" :key="c.id">
                            <button x-on:click="open('customer', { id: c.id })" class="flex w-full items-center gap-3 px-3.5 py-3 text-start active:bg-slate-50">
                                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full text-sm font-bold" :class="toneOf(c.name)" x-text="initials(c.name)"></span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate font-semibold" x-text="c.name"></span>
                                    <span class="block truncate text-xs text-slate-500" x-text="[c.address, c.phone].filter(Boolean).join(' · ') || c.code"></span>
                                </span>
                                <span x-show="c.due" class="shrink-0 rounded-full bg-amber-50 px-2 py-0.5 text-[11px] font-semibold text-amber-800" x-text="rs(c.due)"></span>
                                <i class="text-slate-300" x-html="ic('chevron', 'h-5 w-5')"></i>
                            </button>
                        </template>
                        <div x-show="!customerMatches.length" class="p-8 text-center text-sm text-slate-500">No customer found.</div>
                    </div>
                    <button x-on:click="customerSearch = customerSearch; open('pick'); $nextTick(() => startNewCustomer())" class="press flex w-full items-center justify-center gap-2 rounded-2xl border-2 border-dashed border-brand-300 py-3.5 text-sm font-semibold text-brand-700"><i x-html="ic('userPlus', 'h-5 w-5')"></i>Add a new shop</button>
                </section>

                {{-- ---------- More ---------- --}}
                <section x-show="tab === 'more'" class="space-y-4 p-4">
                    <div class="card flex items-center gap-4 p-4">
                        <span class="grid h-14 w-14 place-items-center rounded-full bg-brand-700 text-xl font-bold text-white" x-text="initials(me?.name)"></span>
                        <div class="min-w-0"><div class="truncate text-lg font-bold" x-text="me?.name"></div><div class="truncate text-sm text-slate-500" x-text="(isOwner ? 'Owner' : 'Salesman') + ' · ' + me?.business"></div></div>
                    </div>

                    <div class="card divide-y divide-slate-100 overflow-hidden">
                        <button x-on:click="sync(true)" :disabled="!online" class="flex w-full items-center gap-3 px-4 py-3.5 text-start disabled:opacity-50">
                            <span class="grid h-9 w-9 place-items-center rounded-xl bg-brand-50 text-brand-700" x-html="ic('refresh', 'h-5 w-5 ' + (syncing ? 'animate-spin' : ''))"></span>
                            <span class="flex-1"><span class="block font-medium">Update products and customers</span><span class="block text-xs text-slate-500" x-text="'Last updated ' + ago(syncedAt)"></span></span>
                        </button>
                        <div class="flex items-center gap-3 px-4 py-3.5">
                            <span class="grid h-9 w-9 place-items-center rounded-xl bg-slate-100 text-slate-600" x-html="ic('box', 'h-5 w-5')"></span>
                            <span class="flex-1 font-medium">Products on the phone</span><span class="font-semibold text-slate-600" x-text="products.length"></span>
                        </div>
                        <div class="flex items-center gap-3 px-4 py-3.5">
                            <span class="grid h-9 w-9 place-items-center rounded-xl bg-slate-100 text-slate-600" x-html="ic('users', 'h-5 w-5')"></span>
                            <span class="flex-1 font-medium">Customers on the phone</span><span class="font-semibold text-slate-600" x-text="customers.length"></span>
                        </div>
                        <button x-show="isOwner && canNotify" x-on:click="askNotifications()" class="flex w-full items-center gap-3 px-4 py-3.5 text-start">
                            <span class="grid h-9 w-9 place-items-center rounded-xl bg-sky-50 text-sky-700" x-html="ic('bell', 'h-5 w-5')"></span>
                            <span class="flex-1"><span class="block font-medium">Alert me about new orders</span><span class="block text-xs text-slate-500">Phone batayega jab salesman order bheje.</span></span>
                        </button>
                    </div>

                    <div class="card divide-y divide-slate-100 overflow-hidden">
                        <div class="px-4 py-3.5 text-sm text-slate-600">
                            <div class="mb-2 flex items-center gap-2 font-semibold text-slate-900"><i class="text-brand-700" x-html="ic('info', 'h-5 w-5')"></i>How it works · Kaise chalti hai</div>
                            <ol class="list-decimal space-y-1 ps-5">
                                <li>Tap <b>+</b>, choose the shop, add products, send.</li>
                                <li>No internet? The order waits on the phone and goes by itself later.</li>
                                <li>The owner accepts it and makes the sale.</li>
                            </ol>
                        </div>
                        <button x-on:click="signOut()" class="flex w-full items-center gap-3 px-4 py-3.5 text-start text-red-600">
                            <span class="grid h-9 w-9 place-items-center rounded-xl bg-red-50" x-html="ic('logout', 'h-5 w-5')"></span>
                            <span class="flex-1"><span class="block font-medium">Sign out</span><span class="block text-xs text-red-500/80">Pehle "not sent" orders bhej dein.</span></span>
                        </button>
                    </div>
                    <p class="text-center text-xs text-slate-400" x-text="'{{ $appName }} · version ' + version"></p>
                </section>
            </main>

            {{-- Bottom navigation with the big + in the middle --}}
            <nav class="safe-bottom absolute inset-x-0 bottom-0 z-20 border-t border-slate-200 bg-white/95 backdrop-blur">
                <div class="grid grid-cols-5 items-end">
                    <template x-for="[key, label, iconName] in [['home', 'Home', 'home'], ['orders', 'Orders', 'receipt'], ['+', '', ''], ['customers', 'Customers', 'store'], ['more', 'More', 'more']]" :key="key">
                        <div class="flex justify-center">
                            <template x-if="key === '+'">
                                <button x-on:click="newOrder()" class="press relative -mt-6 grid h-16 w-16 place-items-center rounded-full bg-brand-600 text-white shadow-xl shadow-brand-700/40 ring-4 ring-white" aria-label="New order">
                                    <i x-html="ic('plus', 'h-8 w-8')"></i>
                                    <span x-show="cart.lines.length" class="absolute -top-0.5 -right-0.5 grid h-6 min-w-6 place-items-center rounded-full bg-amber-400 px-1 text-xs font-bold text-amber-950" x-text="cartCount"></span>
                                </button>
                            </template>
                            <template x-if="key !== '+'">
                                <button x-on:click="go(key)" class="relative flex w-full flex-col items-center gap-0.5 pt-2 pb-1.5 text-[11px] font-medium" :class="tab === key ? 'text-brand-700' : 'text-slate-500'">
                                    <span class="grid h-8 w-14 place-items-center rounded-full transition-colors" :class="tab === key ? 'bg-brand-100' : ''" x-html="ic(iconName, 'h-5 w-5')"></span>
                                    <span x-text="label"></span>
                                    <span x-show="key === 'orders' && badge" class="absolute top-1 right-[16%] grid h-5 min-w-5 place-items-center rounded-full bg-red-500 px-1 text-[10px] font-bold text-white ring-2 ring-white" x-text="bubble(badge)"></span>                                </button>
                            </template>
                        </div>
                    </template>
                </div>
            </nav>

            {{-- ================= Screens that open over the tabs ================= --}}

            {{-- Choose the customer --}}
            <div x-show="screen?.name === 'pick'" x-cloak x-transition:enter="sheet-enter" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0" class="absolute inset-0 z-30 flex flex-col bg-slate-100">
                <header class="safe-top shrink-0 bg-white shadow-sm">
                    <div class="flex h-14 items-center gap-2 px-2">
                        <button x-on:click="back()" class="grid h-10 w-10 place-items-center rounded-full active:bg-slate-100" x-html="ic('back', 'h-6 w-6')" aria-label="Back"></button>
                        <div class="flex-1"><div class="text-lg font-semibold leading-tight">Choose the shop</div><div class="text-xs text-slate-500">Step 1 of 3 · Gahak</div></div>
                    </div>
                    <div class="px-3 pb-3">
                        <div class="ibox"><i x-html="ic('search', 'h-5 w-5')"></i><input id="pick-search" x-model="customerSearch" type="search" placeholder="Shop name, phone or area" class="input rounded-2xl bg-slate-50 py-3 text-base"></div>
                        <p class="hint"><i x-html="ic('info', 'h-3.5 w-3.5')"></i><span>Kuch huroof likhein. Na mile to "New shop" dabayein.</span></p>
                    </div>
                </header>
                <div id="screen-pick" class="no-bar min-h-0 flex-1 space-y-4 overflow-y-auto p-4">
                    <button x-on:click="startNewCustomer()" class="press flex w-full items-center gap-3 rounded-2xl bg-brand-600 p-3.5 text-start font-semibold text-white shadow-md shadow-brand-600/25"><span class="grid h-10 w-10 place-items-center rounded-xl bg-white/20" x-html="ic('userPlus', 'h-5 w-5')"></span><span class="flex-1">New shop · Naya gahak</span><i x-html="ic('chevron', 'h-5 w-5')"></i></button>
                    <template x-if="!customerSearch && recentCustomers.length">
                        <div>
                            <div class="section-title"><span>Recent</span></div>
                            <div class="no-bar -mx-4 flex gap-3 overflow-x-auto px-4 pb-1">
                                <template x-for="c in recentCustomers" :key="'r' + c.id">
                                    <button x-on:click="pickCustomer(c)" class="press flex w-20 shrink-0 flex-col items-center gap-1.5 text-center">
                                        <span class="grid h-14 w-14 place-items-center rounded-full text-base font-bold" :class="toneOf(c.name)" x-text="initials(c.name)"></span>
                                        <span class="line-clamp-2 text-xs font-medium" x-text="c.name"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>
                    <div>
                        <div class="section-title"><span x-text="customerSearch ? 'Found' : 'All shops'"></span><span class="normal-case" x-text="customerMatches.length"></span></div>
                        <div class="card divide-y divide-slate-100 overflow-hidden">
                            <template x-for="c in customerMatches" :key="c.id">
                                <button x-on:click="pickCustomer(c)" class="flex w-full items-center gap-3 px-3.5 py-3 text-start active:bg-brand-50">
                                    <span class="grid h-10 w-10 shrink-0 place-items-center rounded-full text-sm font-bold" :class="toneOf(c.name)" x-text="initials(c.name)"></span>
                                    <span class="min-w-0 flex-1"><span class="block truncate font-semibold" x-text="c.name"></span><span class="block truncate text-xs text-slate-500" x-text="[c.address, c.phone].filter(Boolean).join(' · ')"></span></span>
                                    <span x-show="cart.customer?.id === c.id" class="text-brand-600" x-html="ic('checkCircle', 'h-5 w-5')"></span>
                                </button>
                            </template>
                            <div x-show="!customerMatches.length" class="p-6 text-center text-sm text-slate-500">No shop found. Add it as a new shop.</div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- A new customer --}}
            <div x-show="screen?.name === 'newCustomer'" x-cloak x-transition:enter="sheet-enter" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0" class="absolute inset-0 z-30 flex flex-col bg-slate-100">
                <header class="safe-top shrink-0 bg-white shadow-sm">
                    <div class="flex h-14 items-center gap-2 px-2">
                        <button x-on:click="back()" class="grid h-10 w-10 place-items-center rounded-full active:bg-slate-100" x-html="ic('back', 'h-6 w-6')" aria-label="Back"></button>
                        <div class="text-lg font-semibold">New shop</div>
                    </div>
                </header>
                <form x-on:submit.prevent="useNewCustomer()" class="no-bar min-h-0 flex-1 space-y-4 overflow-y-auto p-4">
                    <div class="flex justify-center py-2"><span class="grid h-20 w-20 place-items-center rounded-full text-2xl font-bold" :class="toneOf(newCustomer.name)" x-html="newCustomer.name ? initials(newCustomer.name) : ic('store', 'h-9 w-9')"></span></div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold">Shop or customer name</label>
                        <div class="ibox"><i x-html="ic('store', 'h-5 w-5')"></i><input x-model="newCustomer.name" class="input rounded-2xl py-3 text-base" required></div>
                        <p class="hint"><i x-html="ic('info', 'h-3.5 w-3.5')"></i><span>Jo bill par likha jaye.</span></p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold">Phone</label>
                        <div class="ibox"><i x-html="ic('phone', 'h-5 w-5')"></i><input x-model="newCustomer.phone" type="tel" inputmode="tel" placeholder="0300 1234567" class="input rounded-2xl py-3 text-base"></div>
                        <p class="hint"><i x-html="ic('info', 'h-3.5 w-3.5')"></i><span>Yehi phone dobara aaye to wahi dukaan milti hai, nayi nahi banti.</span></p>
                    </div>
                    <div>
                        <label class="mb-1 block text-sm font-semibold">Market / area</label>
                        <div class="ibox"><i x-html="ic('pin', 'h-5 w-5')"></i><input x-model="newCustomer.address" placeholder="e.g. Hall Road, Lahore" class="input rounded-2xl py-3 text-base"></div>
                        <p class="hint"><i x-html="ic('info', 'h-3.5 w-3.5')"></i><span>Bazaar ya ilaqa, taake delivery wala dhoond le.</span></p>
                    </div>
                    <button class="press flex w-full items-center justify-center gap-2 rounded-2xl bg-brand-600 py-4 text-base font-semibold text-white shadow-lg shadow-brand-600/25"><i x-html="ic('check', 'h-5 w-5')"></i>Save and take order</button>
                </form>
            </div>

            {{-- Catalog: add products --}}
            <div x-show="screen?.name === 'catalog'" x-cloak x-transition:enter="sheet-enter" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0" class="absolute inset-0 z-30 flex flex-col bg-slate-100">
                <header class="safe-top shrink-0 bg-white shadow-sm">
                    <div class="flex h-14 items-center gap-2 px-2">
                        <button x-on:click="back()" class="grid h-10 w-10 place-items-center rounded-full active:bg-slate-100" x-html="ic('back', 'h-6 w-6')" aria-label="Back"></button>
                        <button x-on:click="customerSearch = ''; open('pick')" class="min-w-0 flex-1 text-start">
                            <div class="flex items-center gap-1 truncate text-lg leading-tight font-semibold"><span class="truncate" x-text="customerName || 'Choose shop'"></span><i class="shrink-0 text-slate-400" x-html="ic('edit', 'h-4 w-4')"></i></div>
                            <div class="text-xs text-slate-500">Step 2 of 3 · Products</div>
                        </button>
                        <div class="flex rounded-full bg-slate-100 p-0.5 text-xs font-semibold">
                            <template x-for="list in ['wholesale', 'retail']" :key="list">
                                <button x-on:click="switchPrices(list)" class="rounded-full px-2.5 py-1.5 capitalize" :class="cart.priceList === list ? 'bg-white text-brand-700 shadow' : 'text-slate-500'" x-text="list"></button>
                            </template>
                        </div>
                    </div>
                    <div class="px-3 pb-2">
                        <div class="ibox"><i x-html="ic('search', 'h-5 w-5')"></i><input x-model="productSearch" type="search" placeholder="Search products" class="input rounded-2xl bg-slate-50 py-3 text-base"></div>
                        <p class="hint"><i x-html="ic('info', 'h-3.5 w-3.5')"></i><span>Product ka naam ya code. Wholesale dukandar ke liye, retail aam gahak ke liye.</span></p>
                    </div>
                    <div class="no-bar flex gap-2 overflow-x-auto px-3 pb-3" x-show="categories.length > 1">
                        <button x-on:click="category = ''" class="shrink-0 rounded-full px-3.5 py-1.5 text-sm font-medium" :class="!category ? 'bg-brand-700 text-white' : 'bg-slate-100 text-slate-600'">All</button>
                        <template x-for="cat in categories" :key="cat">
                            <button x-on:click="category = cat" class="shrink-0 rounded-full px-3.5 py-1.5 text-sm font-medium" :class="category === cat ? 'bg-brand-700 text-white' : 'bg-slate-100 text-slate-600'" x-text="cat"></button>
                        </template>
                    </div>
                </header>
                <div id="screen-catalog" class="no-bar min-h-0 flex-1 space-y-4 overflow-y-auto p-4 pb-32">
                    <template x-if="usualProducts.length && !productSearch && !category">
                        <div>
                            <div class="section-title"><span class="flex items-center gap-1.5"><i x-html="ic('history', 'h-4 w-4')"></i>This shop usually takes</span></div>
                            <div class="no-bar -mx-4 flex gap-2.5 overflow-x-auto px-4 pb-1">
                                <template x-for="p in usualProducts" :key="'u' + p.id">
                                    <button x-on:click="add(p)" class="press card flex w-32 shrink-0 flex-col gap-2 p-3 text-start">
                                        <span class="flex items-start justify-between"><span class="grid h-9 w-9 place-items-center rounded-xl bg-brand-50 text-brand-700" x-html="ic(productIcon(p), 'h-5 w-5')"></span><span class="grid h-7 min-w-7 place-items-center rounded-full px-1.5 text-xs font-bold" :class="inCart(p) ? 'bg-brand-600 text-white' : 'bg-slate-100 text-brand-700'" x-html="inCart(p) ? inCart(p).qty : ic('plus', 'h-4 w-4')"></span></span>
                                        <span class="line-clamp-2 text-xs font-semibold leading-snug" x-text="p.label"></span>
                                        <span class="text-xs text-slate-500" x-text="rs(priceOf(p))"></span>
                                    </button>
                                </template>
                            </div>
                        </div>
                    </template>
                    <div>
                        <div class="section-title"><span x-text="category || (productSearch ? 'Found' : 'All products')"></span><span class="normal-case" x-text="productMatches.length"></span></div>
                        <div class="card divide-y divide-slate-100 overflow-hidden">
                            <template x-for="p in productMatches" :key="p.id">
                                <div class="flex items-center gap-3 px-3.5 py-3" :class="inCart(p) ? 'bg-brand-50/50' : ''">
                                    <span class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-slate-100 text-slate-600" x-html="ic(productIcon(p), 'h-6 w-6')"></span>
                                    <div class="min-w-0 flex-1">
                                        <div class="line-clamp-2 text-sm leading-snug font-semibold" x-text="p.label"></div>
                                        <div class="mt-0.5 flex items-center gap-2 text-xs">
                                            <span class="font-bold text-slate-900" x-text="rs(priceOf(p))"></span>
                                            <span :class="p.stock ? 'text-emerald-700' : 'text-red-600'" x-text="p.stock ? p.stock + ' in stock' : 'Out of stock'"></span>
                                        </div>
                                    </div>
                                    <template x-if="!inCart(p)">
                                        <button x-on:click="add(p)" class="press flex items-center gap-1 rounded-xl border border-brand-600 px-3 py-2 text-sm font-semibold text-brand-700"><i x-html="ic('plus', 'h-4 w-4')"></i>Add</button>
                                    </template>
                                    <template x-if="inCart(p)">
                                        <div class="flex items-center rounded-xl bg-brand-600 text-white">
                                            <button x-on:click="less(p)" class="grid h-9 w-9 place-items-center" x-html="ic('minus', 'h-4 w-4')" aria-label="One less"></button>
                                            <span class="w-7 text-center font-bold" x-text="inCart(p).qty"></span>
                                            <button x-on:click="add(p)" class="grid h-9 w-9 place-items-center" x-html="ic('plus', 'h-4 w-4')" aria-label="One more"></button>
                                        </div>
                                    </template>
                                </div>
                            </template>
                            <div x-show="!productMatches.length" class="p-8 text-center text-sm text-slate-500" x-text="products.length ? 'No product found.' : 'Products load the first time you are online.'"></div>
                        </div>
                    </div>
                </div>
                <div x-show="cart.lines.length" class="safe-bottom absolute inset-x-0 bottom-0 bg-gradient-to-t from-slate-100 via-slate-100 px-4 pt-3">
                    <button x-on:click="open('cart')" class="press mb-2 flex w-full items-center gap-3 rounded-2xl bg-brand-700 px-4 py-3.5 text-white shadow-xl shadow-brand-900/30">
                        <span class="relative grid h-10 w-10 place-items-center rounded-xl bg-white/15" x-html="ic('cart', 'h-5 w-5')"></span>
                        <span class="flex-1 text-start"><span class="block text-xs text-brand-100" x-text="cart.lines.length + ' products · ' + cartCount + ' pcs'"></span><span class="block text-lg font-bold" x-text="rs(cartTotal)"></span></span>
                        <span class="flex items-center gap-1 font-semibold">View order <i x-html="ic('chevron', 'h-5 w-5')"></i></span>
                    </button>
                </div>
            </div>

            {{-- Cart: check and send --}}
            <div x-show="screen?.name === 'cart'" x-cloak x-transition:enter="sheet-enter" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0" class="absolute inset-0 z-30 flex flex-col bg-slate-100">
                <header class="safe-top shrink-0 bg-white shadow-sm">
                    <div class="flex h-14 items-center gap-2 px-2">
                        <button x-on:click="back()" class="grid h-10 w-10 place-items-center rounded-full active:bg-slate-100" x-html="ic('back', 'h-6 w-6')" aria-label="Back"></button>
                        <div class="flex-1"><div class="text-lg leading-tight font-semibold">Check order</div><div class="text-xs text-slate-500">Step 3 of 3 · Bhejein</div></div>
                        <button x-show="cart.lines.length" x-on:click="clearCart()" class="grid h-10 w-10 place-items-center rounded-full text-red-600 active:bg-red-50" x-html="ic('trash', 'h-5 w-5')" aria-label="Clear order"></button>
                    </div>
                </header>
                <div id="screen-cart" class="no-bar min-h-0 flex-1 space-y-4 overflow-y-auto p-4 pb-36">
                    <button x-on:click="customerSearch = ''; open('pick')" class="press card flex w-full items-center gap-3 p-3.5 text-start">
                        <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full text-sm font-bold" :class="toneOf(customerName)" x-html="customerName ? initials(customerName) : ic('store', 'h-5 w-5')"></span>
                        <span class="min-w-0 flex-1"><span class="block text-xs text-slate-500">Shop</span><span class="block truncate font-semibold" x-text="customerName || 'Choose a shop'"></span></span>
                        <span class="text-sm font-semibold text-brand-700">Change</span>
                    </button>

                    <div>
                        <div class="section-title"><span x-text="cart.lines.length + ' products · ' + cart.priceList"></span><button x-on:click="back()" class="normal-case text-brand-700">+ Add more</button></div>
                        <div class="card divide-y divide-slate-100 overflow-hidden">
                            <template x-for="line in cart.lines" :key="line.product_id">
                                <div class="flex items-center gap-3 px-3.5 py-3">
                                    <span class="grid h-11 w-11 shrink-0 place-items-center rounded-2xl bg-slate-100 text-slate-600" x-html="ic(productIcon(line), 'h-5 w-5')"></span>
                                    <div class="min-w-0 flex-1">
                                        <div class="line-clamp-2 text-sm leading-snug font-semibold" x-text="line.label"></div>
                                        <label class="mt-1 flex items-center gap-1 text-xs text-slate-500">Rs <input type="number" inputmode="numeric" min="0" x-model.number="line.price" class="w-20 rounded-lg border border-slate-200 px-1.5 py-1 text-sm font-semibold text-slate-900" aria-label="Price each"> each</label>
                                        <div class="mt-0.5 text-xs font-semibold text-slate-700" x-text="'= ' + rs(line.qty * line.price)"></div>
                                    </div>
                                    <div class="flex items-center rounded-xl bg-slate-100">
                                        <button x-on:click="step(line, -1)" class="grid h-9 w-9 place-items-center text-slate-600" x-html="ic(line.qty > 1 ? 'minus' : 'trash', 'h-4 w-4')" aria-label="One less"></button>
                                        <input type="number" inputmode="numeric" min="0" x-model.number="line.qty" class="w-10 bg-transparent text-center font-bold" aria-label="Quantity">
                                        <button x-on:click="step(line, 1)" class="grid h-9 w-9 place-items-center text-brand-700" x-html="ic('plus', 'h-4 w-4')" aria-label="One more"></button>
                                    </div>
                                </div>
                            </template>
                            <div x-show="!cart.lines.length" class="p-8 text-center text-sm text-slate-500">No products yet. Tap "+ Add more".</div>
                        </div>
                        <p class="hint"><i x-html="ic('info', 'h-3.5 w-3.5')"></i><span>Qeemat badal sakte hain agar alag tay hui. Gintee 0 karne se product hat jata hai.</span></p>
                    </div>

                    <div>
                        <label class="mb-1 block px-1 text-sm font-semibold">Note for the owner</label>
                        <textarea x-model="cart.notes" rows="2" placeholder="Delivery day, discount asked…" class="input rounded-2xl text-sm"></textarea>
                        <p class="hint"><i x-html="ic('info', 'h-3.5 w-3.5')"></i><span>Zaroori nahi. Delivery ka din, discount waghera.</span></p>
                    </div>

                    <div class="card space-y-2 p-4 text-sm">
                        <div class="flex justify-between text-slate-600"><span>Pieces</span><span x-text="cartCount"></span></div>
                        <div class="flex justify-between text-slate-600"><span>Prices</span><span class="capitalize" x-text="cart.priceList"></span></div>
                        <div class="flex justify-between border-t border-slate-100 pt-2 text-base font-bold"><span>Total</span><span x-text="rs(cartTotal)"></span></div>
                    </div>
                </div>
                <div class="safe-bottom absolute inset-x-0 bottom-0 border-t border-slate-200 bg-white px-4 pt-3">
                    <button x-on:click="place()" :disabled="!cart.lines.length" class="press mb-2 flex w-full items-center justify-center gap-2 rounded-2xl bg-brand-600 py-4 text-base font-semibold text-white shadow-lg shadow-brand-600/30 disabled:opacity-50">
                        <i x-html="ic('send', 'h-5 w-5')"></i><span x-text="'Send order · ' + rs(cartTotal)"></span>
                    </button>
                    <p class="mb-1 text-center text-[11px] text-slate-500" x-text="online ? 'Goes to the owner now.' : 'No internet: saved on the phone, goes later by itself.'"></p>
                </div>
            </div>

            {{-- A customer's page --}}
            <div x-show="screen?.name === 'customer'" x-cloak x-transition:enter="sheet-enter" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0" class="absolute inset-0 z-30 flex flex-col bg-slate-100">
                <template x-if="customer">
                    <div class="flex h-full flex-col">
                        <header class="safe-top shrink-0 bg-brand-900 text-white">
                            <div class="flex h-14 items-center gap-2 px-2">
                                <button x-on:click="back()" class="grid h-10 w-10 place-items-center rounded-full active:bg-white/10" x-html="ic('back', 'h-6 w-6')" aria-label="Back"></button>
                                <div class="flex-1 text-lg font-semibold">Shop</div>
                            </div>
                            <div class="flex flex-col items-center px-4 pt-1 pb-4 text-center">
                                <span class="grid h-20 w-20 place-items-center rounded-full bg-white text-2xl font-bold text-brand-800" x-text="initials(customer.name)"></span>
                                <div class="mt-3 text-xl font-bold" x-text="customer.name"></div>
                                <div class="text-sm text-brand-100" x-text="[customer.code, customer.address].filter(Boolean).join(' · ')"></div>
                            </div>
                            <div class="grid grid-cols-4 gap-2 px-4 pb-4 text-slate-900">
                                <a :href="tel(customer.phone)" :class="customer.phone ? '' : 'pointer-events-none opacity-40'" class="press card flex flex-col items-center gap-1 py-3 text-xs font-medium"><i class="text-brand-700" x-html="ic('phone', 'h-5 w-5')"></i>Call</a>
                                <a :href="wa(customer.phone)" :class="customer.phone ? '' : 'pointer-events-none opacity-40'" class="press card flex flex-col items-center gap-1 py-3 text-xs font-medium"><i class="text-emerald-600" x-html="ic('whatsapp', 'h-5 w-5')"></i>WhatsApp</a>
                                <a :href="maps(customer)" target="_blank" rel="noopener" class="press card flex flex-col items-center gap-1 py-3 text-xs font-medium"><i class="text-sky-600" x-html="ic('map', 'h-5 w-5')"></i>Directions</a>
                                <button x-on:click="orderFor(customer)" class="press flex flex-col items-center gap-1 rounded-[1.25rem] bg-emerald-400 py-3 text-xs font-semibold text-emerald-950 shadow-md"><i x-html="ic('cart', 'h-5 w-5')"></i>Order</button>
                            </div>
                        </header>
                        <div id="screen-customer" class="no-bar min-h-0 flex-1 space-y-4 overflow-y-auto p-4 pb-28">
                            <div class="grid grid-cols-2 gap-3">
                                <div class="card p-3.5"><div class="flex items-center gap-1.5 text-xs text-slate-500"><i x-html="ic('wallet', 'h-4 w-4')"></i>Owes · Udhaar</div><div class="mt-1 text-lg font-bold" :class="customer.due ? 'text-amber-700' : ''" x-text="rs(customer.due)"></div></div>
                                <div class="card p-3.5"><div class="flex items-center gap-1.5 text-xs text-slate-500"><i x-html="ic('receipt', 'h-4 w-4')"></i>Orders</div><div class="mt-1 text-lg font-bold" x-text="customerOrders.length"></div></div>
                            </div>
                            <div x-show="customer.phone" class="card flex items-center gap-3 p-3.5 text-sm"><i class="text-slate-400" x-html="ic('phone', 'h-5 w-5')"></i><span class="flex-1" x-text="customer.phone"></span></div>
                            <div>
                                <div class="section-title"><span>Past orders</span></div>
                                <div class="card divide-y divide-slate-100 overflow-hidden">
                                    <template x-for="o in customerOrders" :key="o.uuid">
                                        <button x-on:click="open('order', { uuid: o.uuid })" class="flex w-full items-center gap-3 px-3.5 py-3 text-start active:bg-slate-50">
                                            <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl" :class="statusBg(o.status)" x-html="ic(statusIcon(o.status), 'h-5 w-5')"></span>
                                            <span class="min-w-0 flex-1"><span class="block text-sm font-semibold" x-text="o.number"></span><span class="block text-xs text-slate-500" x-text="when(o.ordered_at) + ' · ' + o.lines.length + ' products'"></span></span>
                                            <span class="text-sm font-bold" x-text="rs(o.total)"></span>
                                        </button>
                                    </template>
                                    <div x-show="!customerOrders.length" class="p-6 text-center text-sm text-slate-500">No orders for this shop yet.</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            {{-- One order --}}
            <div x-show="screen?.name === 'order'" x-cloak x-transition:enter="sheet-enter" x-transition:enter-start="translate-x-full" x-transition:enter-end="translate-x-0" class="absolute inset-0 z-30 flex flex-col bg-slate-100">
                <template x-if="order">
                    <div class="flex h-full flex-col">
                        <header class="safe-top shrink-0 bg-white shadow-sm">
                            <div class="flex h-14 items-center gap-2 px-2">
                                <button x-on:click="back()" class="grid h-10 w-10 place-items-center rounded-full active:bg-slate-100" x-html="ic('back', 'h-6 w-6')" aria-label="Back"></button>
                                <div class="min-w-0 flex-1"><div class="truncate text-lg leading-tight font-semibold" x-text="order.number"></div><div class="text-xs text-slate-500" x-text="when(order.ordered_at)"></div></div>
                                <span class="rounded-full px-2.5 py-1 text-xs font-semibold" :class="statusTone(order.status)" x-text="statusText(order.status)"></span>
                            </div>
                        </header>
                        <div id="screen-order" class="no-bar min-h-0 flex-1 space-y-4 overflow-y-auto p-4 pb-10">
                            <button x-on:click="order.customer_id && customers.some((c) => c.id === order.customer_id) && open('customer', { id: order.customer_id })" class="card flex w-full items-center gap-3 p-3.5 text-start">
                                <span class="grid h-11 w-11 shrink-0 place-items-center rounded-full text-sm font-bold" :class="toneOf(order.customer)" x-text="initials(order.customer)"></span>
                                <span class="min-w-0 flex-1"><span class="block truncate font-semibold" x-text="order.customer || 'Customer'"></span><span class="block truncate text-xs text-slate-500" x-text="order.salesman ? 'By ' + order.salesman : ''"></span></span>
                                <a x-show="order.customer_phone" :href="tel(order.customer_phone)" x-on:click.stop class="grid h-10 w-10 place-items-center rounded-full bg-brand-50 text-brand-700" x-html="ic('phone', 'h-5 w-5')" aria-label="Call"></a>
                                <a x-show="order.customer_phone" :href="wa(order.customer_phone)" x-on:click.stop class="grid h-10 w-10 place-items-center rounded-full bg-emerald-50 text-emerald-700" x-html="ic('whatsapp', 'h-5 w-5')" aria-label="WhatsApp"></a>
                            </button>

                            {{-- Where the order is --}}
                            <div class="card p-4">
                                <div class="flex items-center justify-between text-[11px] font-medium text-slate-500">
                                    <template x-for="[s, label] in [['waiting', 'Taken'], ['new', 'Sent'], ['accepted', 'Accepted'], ['delivered', 'Sale']]" :key="s">
                                        <div class="flex flex-1 flex-col items-center gap-1.5">
                                            <span class="grid h-7 w-7 place-items-center rounded-full" :class="order.status === 'cancelled' ? 'bg-slate-200 text-slate-400' : (['waiting', 'new', 'accepted', 'delivered'].indexOf(order.status) >= ['waiting', 'new', 'accepted', 'delivered'].indexOf(s) ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-400')" x-html="ic('check', 'h-4 w-4')"></span>
                                            <span x-text="label"></span>
                                        </div>
                                    </template>
                                </div>
                            </div>

                            <div class="card divide-y divide-slate-100 overflow-hidden">
                                <template x-for="l in order.lines" :key="l.product_id + l.label">
                                    <div class="flex items-center gap-3 px-3.5 py-3 text-sm">
                                        <span class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-slate-100 text-slate-600" x-html="ic(productIcon(l), 'h-5 w-5')"></span>
                                        <span class="min-w-0 flex-1"><span class="line-clamp-2 block font-medium" x-text="l.label"></span><span class="block text-xs text-slate-500" x-text="l.quantity + ' × ' + rs(l.unit_price)"></span></span>
                                        <span class="font-semibold" x-text="rs(l.quantity * l.unit_price)"></span>
                                    </div>
                                </template>
                                <div class="flex justify-between bg-slate-50 px-3.5 py-3 font-bold"><span>Total <span class="text-xs font-normal capitalize text-slate-500" x-text="'· ' + (order.price_list || '')"></span></span><span x-text="rs(order.total)"></span></div>
                            </div>

                            <p x-show="order.notes" class="flex gap-2 rounded-2xl bg-amber-50 p-3 text-sm text-amber-900"><i x-html="ic('note', 'h-4 w-4 shrink-0')"></i><span x-text="order.notes"></span></p>
                            <p x-show="order.invoice" class="flex gap-2 rounded-2xl bg-emerald-50 p-3 text-sm text-emerald-900"><i x-html="ic('receipt', 'h-4 w-4 shrink-0')"></i><span x-text="'Sale made: ' + order.invoice"></span></p>
                            <p x-show="order.cancel_reason" class="flex gap-2 rounded-2xl bg-slate-200/60 p-3 text-sm"><i x-html="ic('xCircle', 'h-4 w-4 shrink-0')"></i><span x-text="'Cancelled: ' + order.cancel_reason"></span></p>
                            <p x-show="order.status === 'waiting'" class="flex gap-2 rounded-2xl bg-amber-50 p-3 text-sm text-amber-900"><i x-html="ic('wifiOff', 'h-4 w-4 shrink-0')"></i><span>On the phone. It goes to the owner as soon as there is internet.</span></p>

                            <template x-if="isOwner && (order.status === 'new' || order.status === 'accepted')">
                                <div class="space-y-3">
                                    <button x-show="order.status === 'new'" x-on:click="act(order, 'accept')" class="press flex w-full items-center justify-center gap-2 rounded-2xl bg-brand-600 py-3.5 font-semibold text-white"><i x-html="ic('checkCircle', 'h-5 w-5')"></i>Accept · Manzoor karein</button>
                                    <div>
                                        <a :href="'{{ $config['saleUrl'] }}?order=' + order.id" class="press flex w-full items-center justify-center gap-2 rounded-2xl bg-white py-3.5 font-semibold text-brand-700 ring-1 ring-slate-200"><i x-html="ic('receipt', 'h-5 w-5')"></i>Make the sale</a>
                                        <p class="hint"><i x-html="ic('info', 'h-3.5 w-3.5')"></i><span>Sale ka form is order se bhar kar khulta hai; save karne par stock nikalta hai.</span></p>
                                    </div>
                                    <div>
                                        <div class="flex gap-2"><div class="ibox flex-1"><i x-html="ic('note', 'h-4 w-4')"></i><input x-model="cancelReason" placeholder="Why cancel?" class="input rounded-xl"></div><button x-on:click="act(order, 'cancel')" class="btn btn-danger gap-1"><i x-html="ic('xCircle', 'h-4 w-4')"></i>Cancel</button></div>
                                        <p class="hint"><i x-html="ic('info', 'h-3.5 w-3.5')"></i><span>Wajah likhein, masalan stock khatam. Salesman dekhega.</span></p>
                                    </div>
                                </div>
                            </template>
                            <button x-show="order.customer_id && order.status !== 'waiting'" x-on:click="repeat(order)" class="press flex w-full items-center justify-center gap-2 rounded-2xl bg-white py-3.5 font-semibold text-brand-700 ring-1 ring-slate-200"><i x-html="ic('repeat', 'h-5 w-5')"></i>Order the same again</button>
                        </div>
                    </div>
                </template>
            </div>

            {{-- Order placed --}}
            <div x-show="done" x-cloak class="absolute inset-0 z-40 flex items-end bg-black/40" x-on:click.self="done = null">
                <div x-show="done" x-transition:enter="sheet-enter" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0" class="safe-bottom w-full rounded-t-[2rem] bg-white px-5 pt-6 pb-4 text-center">
                    <template x-if="done">
                        <div>
                            <span class="mx-auto grid h-20 w-20 place-items-center rounded-full" :class="done.offline ? 'bg-amber-100 text-amber-600' : 'bg-emerald-100 text-emerald-600'" x-html="ic(done.offline ? 'wifiOff' : 'checkCircle', 'h-11 w-11')"></span>
                            <div class="mt-4 text-xl font-bold" x-text="done.offline ? 'Saved on the phone' : 'Order sent'"></div>
                            <p class="mt-1 text-sm text-slate-500" x-text="done.name + ' · ' + done.count + ' pcs · ' + rs(done.total)"></p>
                            <p class="mt-1 text-sm text-slate-500" x-text="done.offline ? 'It goes to the owner by itself when there is internet.' : 'The owner can see it now. · Maalik tak pohanch gaya.'"></p>
                            <div class="mt-6 grid grid-cols-2 gap-3">
                                <button x-on:click="done = null; go('orders')" class="press rounded-2xl bg-slate-100 py-3.5 font-semibold">See orders</button>
                                <button x-on:click="done = null; newOrder()" class="press rounded-2xl bg-brand-600 py-3.5 font-semibold text-white">Next order</button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- Toast --}}
            <div x-show="toast" x-cloak x-transition class="pointer-events-none absolute inset-x-0 bottom-24 z-50 mx-auto w-[min(92%,26rem)]">
                <div class="flex items-center gap-2 rounded-2xl px-4 py-3 text-sm font-medium text-white shadow-xl" :class="toast?.tone === 'bad' ? 'bg-red-600' : 'bg-slate-900'">
                    <i x-html="ic(toast?.tone === 'bad' ? 'xCircle' : 'checkCircle', 'h-5 w-5 shrink-0')"></i><span x-text="toast?.message"></span>
                </div>
            </div>
        </div>
    </template>
</div>
</body>
</html>
