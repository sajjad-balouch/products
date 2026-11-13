// The salesman / owner phone app. It works offline: products and customers are kept on the
// phone, new orders wait in an outbox, and everything is sent as soon as there is internet.
//
// Laid out like a standard field-sales app: Home, Orders, a big + for a new order, Customers, More.
// A new order goes customer → catalog → cart → send. Screens stack, so the phone's Back button
// closes the top one instead of leaving the app.
import Alpine from 'alpinejs';

const CFG = window.SALES_APP;
const KEY = (name) => 'msk.sales.' + name;
const load = (name, fallback) => { try { const v = localStorage.getItem(KEY(name)); return v ? JSON.parse(v) : fallback; } catch (e) { return fallback; } };
const save = (name, value) => { try { localStorage.setItem(KEY(name), JSON.stringify(value)); } catch (e) {} };
const uuid = () => (crypto.randomUUID ? crypto.randomUUID() : 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => { const r = Math.random() * 16 | 0; return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16); }));
const rs = (n) => 'Rs ' + Math.round(Number(n) || 0).toLocaleString('en-PK');
const blankCart = () => ({ customer: null, newCustomer: null, priceList: 'wholesale', lines: [], notes: '' });
const dayKey = (iso) => new Date(iso).toDateString();

// Line icons (24 × 24, drawn with the current text colour).
const ICONS = {
    home: '<path d="M3 10.5L12 3l9 7.5"/><path d="M5 9v11h5v-6h4v6h5V9"/>',
    store: '<path d="M3 9l1.5-5h15L21 9"/><path d="M3 9h18v1a3 3 0 0 1-6 0 3 3 0 0 1-6 0 3 3 0 0 1-6 0z"/><path d="M5 12.5V20h14v-7.5"/><path d="M10 20v-5h4v5"/>',
    user: '<circle cx="12" cy="8" r="4"/><path d="M4 21v-1a6 6 0 0 1 6-6h4a6 6 0 0 1 6 6v1"/>',
    users: '<path d="M16 19v-1a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v1"/><circle cx="9" cy="7" r="4"/><path d="M22 19v-1a4 4 0 0 0-3-3.9M16 3.1a4 4 0 0 1 0 7.8"/>',
    userPlus: '<circle cx="9" cy="8" r="4"/><path d="M2 21v-1a6 6 0 0 1 6-6h2"/><path d="M19 14v6M16 17h6"/>',
    lock: '<rect x="4" y="11" width="16" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>',
    phone: '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 1.9.7 2.8a2 2 0 0 1-.5 2.1L8 9.9a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.4c.9.3 1.8.6 2.8.7a2 2 0 0 1 1.7 2z"/>',
    whatsapp: '<path d="M3 21l1.7-5A9 9 0 1 1 8 19.3z"/><path d="M9 9.5c0 3 2.5 5.5 5.5 5.5l1.2-1.3-2-1-1 1a4 4 0 0 1-2.4-2.4l1-1-1-2z"/>',
    pin: '<path d="M12 21s-7-5.6-7-11a7 7 0 0 1 14 0c0 5.4-7 11-7 11z"/><circle cx="12" cy="10" r="2.5"/>',
    map: '<path d="M9 4L3 6v14l6-2 6 2 6-2V4l-6 2z"/><path d="M9 4v14M15 6v14"/>',
    search: '<circle cx="11" cy="11" r="7"/><path d="M21 21l-4.3-4.3"/>',
    plus: '<path d="M12 5v14M5 12h14"/>',
    minus: '<path d="M5 12h14"/>',
    cart: '<circle cx="9" cy="20" r="1.5"/><circle cx="18" cy="20" r="1.5"/><path d="M2 3h3l2.7 12.4a2 2 0 0 0 2 1.6h7.6a2 2 0 0 0 2-1.5L21 8H6"/>',
    send: '<path d="M22 2L11 13"/><path d="M22 2l-7 20-4-9-9-4z"/>',
    wifi: '<path d="M5 12.6a10 10 0 0 1 14 0M8.5 16.1a5 5 0 0 1 7 0M2 8.8a15 15 0 0 1 20 0"/><circle cx="12" cy="20" r="1"/>',
    wifiOff: '<path d="M2 2l20 20M8.5 16.1a5 5 0 0 1 7 0M5 12.6a10 10 0 0 1 4.2-2.5M2 8.8A15 15 0 0 1 6.6 6M17.3 10.9A10 10 0 0 1 19 12.6M10.7 5.1A15 15 0 0 1 22 8.8"/><circle cx="12" cy="20" r="1"/>',
    refresh: '<path d="M21 12a9 9 0 0 1-15.5 6.3L3 16M3 12a9 9 0 0 1 15.5-6.3L21 8"/><path d="M21 3v5h-5M3 21v-5h5"/>',
    clock: '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>',
    check: '<path d="M20 6L9 17l-5-5"/>',
    checkCircle: '<circle cx="12" cy="12" r="9"/><path d="M8 12.5l2.7 2.7L16 9.8"/>',
    x: '<path d="M18 6L6 18M6 6l12 12"/>',
    xCircle: '<circle cx="12" cy="12" r="9"/><path d="M15 9l-6 6M9 9l6 6"/>',
    bell: '<path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/>',
    truck: '<path d="M1 4h13v12H1zM14 8h4l4 4v4h-8z"/><circle cx="5.5" cy="18.5" r="2"/><circle cx="18.5" cy="18.5" r="2"/>',
    receipt: '<path d="M5 2h14v20l-3-2-2 2-2-2-2 2-2-2-3 2z"/><path d="M9 7h6M9 11h6M9 15h4"/>',
    tag: '<path d="M20.6 13.4l-7.2 7.2a2 2 0 0 1-2.8 0L2 12V2h10l8.6 8.6a2 2 0 0 1 0 2.8z"/><circle cx="7" cy="7" r="1.5"/>',
    note: '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h5"/>',
    list: '<path d="M9 6h11M9 12h11M9 18h11M4 6h.01M4 12h.01M4 18h.01"/>',
    grid: '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
    coins: '<circle cx="9" cy="9" r="6"/><path d="M15.5 9.5A6 6 0 1 1 9.5 15.5"/><path d="M9 6.5v5M7.5 8h3"/>',
    wallet: '<path d="M3 7a2 2 0 0 1 2-2h13v4"/><path d="M3 7v11a2 2 0 0 0 2 2h15V9H5a2 2 0 0 1-2-2z"/><circle cx="16" cy="14.5" r="1.2"/>',
    logout: '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
    back: '<path d="M15 18l-6-6 6-6"/>',
    chevron: '<path d="M9 18l6-6-6-6"/>',
    more: '<circle cx="5" cy="12" r="1.5"/><circle cx="12" cy="12" r="1.5"/><circle cx="19" cy="12" r="1.5"/>',
    repeat: '<path d="M17 2l4 4-4 4"/><path d="M3 11V9a3 3 0 0 1 3-3h15M7 22l-4-4 4-4"/><path d="M21 13v2a3 3 0 0 1-3 3H3"/>',
    trash: '<path d="M3 6h18M8 6V4h8v2M6 6l1 15h10l1-15"/>',
    edit: '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>',
    history: '<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5M12 7v5l3 2"/>',
    shield: '<path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>',
    info: '<circle cx="12" cy="12" r="9"/><path d="M12 11v5M12 8h.01"/>',
    // Products, by what they are.
    box: '<path d="M21 8l-9-5-9 5v8l9 5 9-5z"/><path d="M3 8l9 5 9-5M12 13v8"/>',
    smartphone: '<rect x="6" y="2" width="12" height="20" rx="2.5"/><path d="M11 18h2"/>',
    plug: '<path d="M9 2v6M15 2v6M6 8h12v3a6 6 0 0 1-12 0z"/><path d="M12 17v5"/>',
    battery: '<rect x="2" y="7" width="17" height="10" rx="2"/><path d="M22 11v2M6 10v4M10 10v4"/>',
    headphones: '<path d="M3 18v-6a9 9 0 0 1 18 0v6"/><path d="M21 19a2 2 0 0 1-2 2h-1v-6h3zM3 19a2 2 0 0 0 2 2h1v-6H3z"/>',
    bike: '<circle cx="5.5" cy="17" r="3.5"/><circle cx="18.5" cy="17" r="3.5"/><path d="M5.5 17l4-7h5l4 7M9.5 10L8 6H5M14.5 10l1.5-4h3"/>',
    tv: '<rect x="2" y="5" width="20" height="13" rx="2"/><path d="M8 22h8M12 18v4"/>',
    fridge: '<rect x="5" y="2" width="14" height="20" rx="2"/><path d="M5 10h14M9 5v2M9 13v3"/>',
    sofa: '<path d="M4 11V8a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3v3"/><path d="M2 13a2 2 0 0 1 4 0v2h12v-2a2 2 0 0 1 4 0v5H2zM5 18v2M19 18v2"/>',
    solar: '<rect x="3" y="11" width="18" height="9" rx="1"/><path d="M3 15.5h18M9 11v9M15 11v9M12 2v3M5.6 4.6l1.4 1.4M18.4 4.6L17 6"/>',
    car: '<path d="M5 17h14M3 17v-4l2-5a2 2 0 0 1 1.9-1.3h10.2A2 2 0 0 1 19 8l2 5v4"/><circle cx="7" cy="17" r="2"/><circle cx="17" cy="17" r="2"/>',
};
const icon = (name, cls = 'h-5 w-5') => '<svg class="' + cls + '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' + (ICONS[name] || ICONS.box) + '</svg>';

// A product's picture from its category or name: a phone, a charger, a bike, a fridge…
const productIcon = (p) => {
    const t = ((p.category || '') + ' ' + (p.label || '')).toLowerCase();
    if (/power ?bank|battery/.test(t)) return 'battery';
    if (/charger|cable|adapter|usb/.test(t)) return 'plug';
    if (/earphone|headphone|audio|airpod|buds|speaker/.test(t)) return 'headphones';
    if (/cover|protector|glass|case/.test(t)) return 'shield';
    if (/mobile|phone|galaxy|infinix|tecno|vivo|oppo|redmi|iphone/.test(t)) return 'smartphone';
    if (/motorcycle|bike|honda|yamaha|suzuki|cd ?70|ybr/.test(t)) return 'bike';
    if (/fridge|refrigerator|freezer|washing/.test(t)) return 'fridge';
    if (/\btv\b|led|television|\bac\b|inverter ac|split|appliance|electronic/.test(t)) return 'tv';
    if (/sofa|bed|furniture|table|chair|almari/.test(t)) return 'sofa';
    if (/solar|panel|inverter/.test(t)) return 'solar';
    if (/\bcar\b|cars|corolla|alto|cultus/.test(t)) return 'car';
    return 'box';
};

// Soft colours for a name's round badge, always the same for the same name.
const TONES = ['bg-sky-100 text-sky-700', 'bg-violet-100 text-violet-700', 'bg-amber-100 text-amber-800', 'bg-emerald-100 text-emerald-700', 'bg-rose-100 text-rose-700', 'bg-teal-100 text-teal-700', 'bg-indigo-100 text-indigo-700'];
const toneOf = (name) => TONES[[...(name || '?')].reduce((s, c) => s + c.charCodeAt(0), 0) % TONES.length];

window.salesApp = () => ({
    token: load('token', null),
    me: load('me', null),
    products: load('products', []),
    customers: load('customers', []),
    orders: load('orders', []),
    summary: load('summary', { new: 0, today_count: 0, today_total: 0 }),
    outbox: load('outbox', []),
    failed: load('failed', []),
    syncedAt: load('syncedAt', null),
    cart: load('cart', blankCart()),

    online: navigator.onLine,
    syncing: false,
    tab: 'home',            // home | orders | customers | more
    stack: [],              // screens open over the tab: [{ name: 'customer', id }, { name: 'catalog' }, ...]
    filter: 'all',
    category: '',
    productSearch: '',
    customerSearch: '',
    newCustomer: { name: '', phone: '', address: '' },
    cancelReason: '',
    toast: null,
    done: null,             // the order just placed, for the "sent" sheet
    pull: 0,                // pull-to-refresh distance
    login: { username: '', password: '', error: '', busy: false, show: false },
    rs,
    ic: icon,
    productIcon,
    toneOf,

    init() {
        window.addEventListener('online', () => { this.online = true; this.sync(); });
        window.addEventListener('offline', () => { this.online = false; });
        document.addEventListener('visibilitychange', () => { if (document.visibilityState === 'visible') this.sync(); });
        // The phone's Back button closes the top screen; on a tab it goes Home before leaving.
        window.addEventListener('popstate', () => {
            if (this.stack.length) this.stack.pop();
            else if (this.tab !== 'home') this.tab = 'home';
            else return;
            history.pushState({ app: true }, '');
        });
        history.replaceState({ app: true }, '');
        history.pushState({ app: true }, '');
        this.$watch('cart', (v) => save('cart', v), { deep: true });
        if (this.token) this.sync(true);
        setInterval(() => this.sync(), 30000);
    },

    // ---- Where we are ----
    get screen() { return this.stack[this.stack.length - 1] || null; },
    open(name, extra = {}) { this.stack.push({ name, ...extra }); this.$nextTick(() => document.getElementById('screen-' + name)?.scrollTo(0, 0)); },
    back() { if (this.stack.length) history.back(); },
    closeTo(name) { while (this.stack.length && this.screen.name !== name) this.stack.pop(); },
    go(tab) { this.stack = []; this.tab = tab; if (tab === 'orders') this.sync(); document.getElementById('main')?.scrollTo(0, 0); },
    get title() { return ({ home: this.me?.business || '', orders: 'Orders', customers: 'Customers', more: 'More' })[this.tab]; },

    get isOwner() { return this.me?.role === 'owner'; },
    get firstName() { return (this.me?.name || '').split(' ')[0]; },
    get greeting() { const h = new Date().getHours(); return h < 12 ? 'Good morning' : h < 17 ? 'Good afternoon' : 'Good evening'; },
    get cartTotal() { return this.cart.lines.reduce((s, l) => s + (Number(l.qty) || 0) * (Number(l.price) || 0), 0); },
    get cartCount() { return this.cart.lines.reduce((s, l) => s + (Number(l.qty) || 0), 0); },
    get customerName() { return this.cart.customer?.name || this.cart.newCustomer?.name || ''; },

    get categories() { return [...new Set(this.products.map((p) => p.category).filter(Boolean))].sort(); },
    get productMatches() {
        const q = this.productSearch.trim().toLowerCase();
        let list = this.category ? this.products.filter((p) => p.category === this.category) : this.products;
        if (q) list = list.filter((p) => (p.label + ' ' + (p.code || '') + ' ' + (p.category || '')).toLowerCase().includes(q));
        return list.slice(0, 80);
    },
    // What this customer bought before, most often first: one tap to order it again.
    get usualProducts() {
        const id = this.cart.customer?.id;
        if (!id) return [];
        const count = {};
        this.orders.filter((o) => o.customer_id === id && o.status !== 'cancelled').forEach((o) => o.lines.forEach((l) => { count[l.product_id] = (count[l.product_id] || 0) + l.quantity; }));
        return Object.keys(count).sort((a, b) => count[b] - count[a]).map((pid) => this.products.find((p) => p.id === Number(pid))).filter(Boolean).slice(0, 8);
    },
    get customerMatches() {
        const q = this.customerSearch.trim().toLowerCase();
        const list = q ? this.customers.filter((c) => (c.name + ' ' + (c.phone || '') + ' ' + (c.code || '') + ' ' + (c.address || '')).toLowerCase().includes(q)) : this.customers;
        return list.slice(0, 100);
    },
    // Shops this salesman ordered for lately, newest first: the usual round.
    get recentCustomers() {
        const seen = new Set(); const out = [];
        for (const o of this.orders) {
            if (o.customer_id && !seen.has(o.customer_id)) { seen.add(o.customer_id); const c = this.customers.find((x) => x.id === o.customer_id); if (c) out.push(c); }
            if (out.length >= 6) break;
        }
        return out;
    },
    get customer() { const s = this.stack.find((x) => x.name === 'customer'); return s ? this.customers.find((c) => c.id === s.id) : null; },
    get customerOrders() { const c = this.customer; return c ? this.orders.filter((o) => o.customer_id === c.id) : []; },
    get order() { const s = this.stack.find((x) => x.name === 'order'); return s ? this.allOrders.find((o) => o.uuid === s.uuid) : null; },

    // Orders still on the phone come first, then the ones the server has.
    get allOrders() {
        const waiting = this.outbox.map((o) => ({
            ...o, status: 'waiting', number: 'Not sent yet', customer: o._customerName, total: o._total, salesman: this.me?.name,
            lines: o.lines.map((l) => ({ ...l, label: this.products.find((p) => p.id === l.product_id)?.label || 'Product' })),
        }));
        return waiting.concat(this.orders);
    },
    get shownOrders() { return this.filter === 'all' ? this.allOrders : this.allOrders.filter((o) => o.status === this.filter); },
    // Orders under "Today", "Yesterday" and dates, like a phone's message list.
    get orderGroups() {
        const groups = [];
        for (const o of this.shownOrders) {
            const k = dayKey(o.ordered_at);
            let g = groups.find((x) => x.key === k);
            if (!g) groups.push(g = { key: k, label: this.dayLabel(o.ordered_at), orders: [], total: 0 });
            g.orders.push(o); g.total += o.status === 'cancelled' ? 0 : Number(o.total) || 0;
        }
        return groups;
    },
    get recentOrders() { return this.allOrders.slice(0, 5); },
    get waitingForOwner() { return this.orders.filter((o) => o.status === 'new').slice(0, 5); },
    get weekTotal() { const from = Date.now() - 7 * 864e5; return this.orders.filter((o) => o.status !== 'cancelled' && new Date(o.ordered_at) >= from).reduce((s, o) => s + o.total, 0); },
    count(status) { return status === 'all' ? this.allOrders.length : this.allOrders.filter((o) => o.status === status).length; },

    say(message, tone = 'ok') {
        this.toast = { message, tone };
        clearTimeout(this._toast);
        this._toast = setTimeout(() => { this.toast = null; }, 3200);
    },
    buzz(ms = 12) { try { navigator.vibrate && navigator.vibrate(ms); } catch (e) {} },

    async api(path, options = {}) {
        const res = await fetch(CFG.api + path, {
            ...options,
            headers: { Accept: 'application/json', 'Content-Type': 'application/json', ...(this.token ? { Authorization: 'Bearer ' + this.token } : {}), ...(options.headers || {}) },
        });
        if (res.status === 401) { this.signOut(true); throw new Error('Please sign in again.'); }
        const body = await res.json().catch(() => ({}));
        if (!res.ok) throw new Error(body.message || 'Something went wrong (' + res.status + ').');

        return body;
    },

    async signIn() {
        this.login.error = '';
        this.login.busy = true;
        try {
            const body = await this.api('/login', { method: 'POST', body: JSON.stringify({ username: this.login.username, password: this.login.password, device: navigator.userAgent.slice(0, 110) }) });
            this.token = body.token; save('token', body.token);
            this.me = body.me; save('me', body.me);
            this.login.password = '';
            this.tab = 'home'; this.stack = [];
            await this.sync(true);
        } catch (e) {
            this.login.error = navigator.onLine ? e.message : 'No internet. Sign in needs internet once. · Pehli dafa internet chahiye.';
        }
        this.login.busy = false;
    },

    signOut(expired = false) {
        if (!expired && this.outbox.length && !confirm(this.outbox.length + ' order(s) are not sent yet and will be lost. Sign out anyway?')) return;
        if (!expired && this.token) fetch(CFG.api + '/logout', { method: 'POST', headers: { Accept: 'application/json', Authorization: 'Bearer ' + this.token } }).catch(() => {});
        ['token', 'me', 'products', 'customers', 'orders', 'summary', 'outbox', 'failed', 'cart', 'syncedAt'].forEach((k) => { try { localStorage.removeItem(KEY(k)); } catch (e) {} });
        Object.assign(this, { token: null, me: null, products: [], customers: [], orders: [], outbox: [], failed: [], cart: blankCart(), syncedAt: null, stack: [], tab: 'home' });
        if (expired) this.login.error = 'Please sign in again. · Dobara login karein.';
    },

    // Send waiting orders, then fetch the latest products, customers and orders.
    async sync(full = false) {
        if (!this.token || !navigator.onLine || this.syncing) return;
        this.syncing = true;
        try {
            if (this.outbox.length) {
                const sending = this.outbox.slice(0, 50);
                const body = await this.api('/orders', { method: 'POST', body: JSON.stringify({ orders: sending.map(({ _customerName, _total, ...o }) => o) }) });
                const done = new Set();
                for (const r of body.results) {
                    done.add(r.uuid);
                    if (!r.ok) { const o = sending.find((s) => s.uuid === r.uuid); this.failed.push({ ...o, error: r.message }); }
                }
                this.outbox = this.outbox.filter((o) => !done.has(o.uuid)); save('outbox', this.outbox); save('failed', this.failed);
                const sent = body.results.filter((r) => r.ok).length;
                if (sent) this.say(sent + ' order(s) sent to the owner. · Order chala gaya.');
                if (body.results.some((r) => !r.ok)) this.say('An order could not be saved. See Orders. · Aik order save nahi hua.', 'bad');
            }
            if (full || !this.products.length || Date.now() - new Date(this.syncedAt || 0).getTime() > 5 * 60000) {
                const cat = await this.api('/catalog');
                this.me = cat.me; save('me', cat.me);
                this.products = cat.products; save('products', cat.products);
                this.customers = cat.customers; save('customers', cat.customers);
                this.syncedAt = cat.at; save('syncedAt', cat.at);
            }
            const before = this.summary.new;
            const firstTime = !this.orders.length && !this.syncedOrders;
            const list = await this.api('/orders');
            this.orders = list.orders; save('orders', list.orders);
            this.summary = list.summary; save('summary', list.summary);
            this.syncedOrders = true;
            // Right after signing in, orders already waiting are not "new" news.
            if (this.isOwner && !firstTime && list.summary.new > before) this.alertNewOrders(list.summary.new - before);
        } catch (e) {
            if (navigator.onLine) this.say(e.message, 'bad');
        }
        this.syncing = false;
    },

    // Pull the page down from the top to update, like any phone app.
    pullStart(e) { this._y = e.currentTarget.scrollTop <= 0 ? e.touches[0].clientY : null; },
    pullMove(e) { if (this._y != null) this.pull = Math.max(0, Math.min(90, (e.touches[0].clientY - this._y) * 0.5)); },
    pullEnd() { if (this.pull >= 60) { this.buzz(); this.sync(true); } this.pull = 0; this._y = null; },

    alertNewOrders(count) {
        this.say(count + ' new order(s) from salesmen. · Naya order aaya.');
        this.buzz([120, 80, 120]);
        if ('Notification' in window && Notification.permission === 'granted') {
            navigator.serviceWorker?.ready.then((reg) => reg.showNotification(this.me.business, { body: count + ' new order(s) · Naya order', icon: CFG.icon, tag: 'new-orders' })).catch(() => {});
        }
    },
    get canNotify() { return 'Notification' in window && Notification.permission === 'default'; },
    askNotifications() { if (this.canNotify) Notification.requestPermission(); },

    // ---- Taking an order: customer → catalog → cart → send ----
    newOrder() {
        if (this.cart.lines.length && this.customerName) return this.open('catalog'); // carry on with the order in progress
        this.customerSearch = '';
        this.open('pick');
    },
    orderFor(c) { if (this.cart.customer?.id !== c.id) this.cart = { ...blankCart(), priceList: this.cart.priceList, customer: c }; this.open('catalog'); },
    pickCustomer(c) {
        const changing = this.stack.some((s) => s.name === 'catalog' || s.name === 'cart'); // "Change" shop keeps the products
        const keep = this.cart.lines.length && (changing || this.cart.customer?.id === c.id || !this.customerName);
        this.cart = keep ? { ...this.cart, customer: c, newCustomer: null } : { ...blankCart(), priceList: this.cart.priceList, customer: c };
        this.customerSearch = '';
        this.afterPick();
    },
    // Changing the shop from the catalog or cart goes back there; a new order goes on to the catalog.
    afterPick() {
        const back = this.stack.some((s) => s.name === 'catalog' || s.name === 'cart');
        this.stack = this.stack.filter((s) => !['pick', 'newCustomer'].includes(s.name));
        if (!back) this.open('catalog');
    },
    startNewCustomer() { this.newCustomer = { name: this.customerSearch, phone: '', address: '' }; this.open('newCustomer'); },
    useNewCustomer() {
        if (!this.newCustomer.name.trim()) return this.say('Write the shop or customer name.', 'bad');
        this.cart = { ...this.cart, customer: null, newCustomer: { ...this.newCustomer } };
        this.customerSearch = '';
        this.afterPick();
    },
    priceOf(p) { return this.cart.priceList === 'retail' ? p.retail : p.wholesale; },
    inCart(p) { return this.cart.lines.find((l) => l.product_id === p.id); },
    add(p) {
        const line = this.inCart(p);
        if (line) line.qty = Number(line.qty) + 1;
        else this.cart.lines.push({ product_id: p.id, label: p.label, category: p.category, qty: 1, price: this.priceOf(p) });
        this.buzz();
    },
    less(p) { const line = this.inCart(p); if (line) this.step(line, -1); },
    step(line, by) { line.qty = Math.max(0, Number(line.qty) + by); if (!line.qty) this.cart.lines = this.cart.lines.filter((l) => l !== line); this.buzz(8); },
    remove(line) { this.cart.lines = this.cart.lines.filter((l) => l !== line); },
    switchPrices(list) {
        this.cart.priceList = list;
        for (const l of this.cart.lines) { const p = this.products.find((x) => x.id === l.product_id); if (p) l.price = this.priceOf(p); }
    },
    clearCart() { if (confirm('Clear this order?')) { this.cart = blankCart(); this.stack = []; } },

    async place() {
        if (!this.cart.customer && !this.cart.newCustomer) { this.open('pick'); return this.say('Choose the customer first. · Pehle gahak chunein.', 'bad'); }
        if (!this.cart.lines.length) return this.say('Add at least one product.', 'bad');
        const order = {
            uuid: uuid(), customer_id: this.cart.customer?.id || null, new_customer: this.cart.newCustomer, price_list: this.cart.priceList,
            notes: this.cart.notes, ordered_at: new Date().toISOString(),
            lines: this.cart.lines.map((l) => ({ product_id: l.product_id, quantity: Number(l.qty), unit_price: Number(l.price) })),
            _customerName: this.customerName, _total: this.cartTotal,
        };
        const where = await this.position();
        if (where) { order.lat = where.lat; order.lng = where.lng; }
        this.outbox.push(order); save('outbox', this.outbox);
        this.done = { name: this.customerName, total: this.cartTotal, count: this.cartCount, offline: !navigator.onLine };
        this.cart = blankCart();
        this.stack = []; this.tab = 'orders'; this.filter = 'all';
        this.buzz([20, 40, 20]);
        if (navigator.onLine) await this.sync();
    },

    // Same products for the same shop again, at today's prices.
    repeat(o) {
        const c = this.customers.find((x) => x.id === o.customer_id);
        if (!c) return this.say('This customer is not on the phone yet. Update first.', 'bad');
        this.cart = { ...blankCart(), priceList: o.price_list || 'wholesale', customer: c };
        for (const l of o.lines) {
            const p = this.products.find((x) => x.id === l.product_id);
            if (p) this.cart.lines.push({ product_id: p.id, label: p.label, category: p.category, qty: l.quantity, price: this.priceOf(p) });
        }
        this.stack = []; this.open('cart');
    },

    // Where the order was taken, if the phone allows it; never holds the order up for long.
    position() {
        if (!CFG.location || !navigator.geolocation) return Promise.resolve(null);
        return new Promise((resolve) => {
            const t = setTimeout(() => resolve(null), 4000);
            navigator.geolocation.getCurrentPosition((p) => { clearTimeout(t); resolve({ lat: p.coords.latitude, lng: p.coords.longitude }); }, () => { clearTimeout(t); resolve(null); }, { maximumAge: 300000, timeout: 3500 });
        });
    },

    retryFailed(o) { this.failed = this.failed.filter((f) => f !== o); const { error, ...order } = o; this.outbox.push(order); save('failed', this.failed); save('outbox', this.outbox); this.sync(); },
    dropFailed(o) { if (confirm('Delete this order from the phone?')) { this.failed = this.failed.filter((f) => f !== o); save('failed', this.failed); } },

    // ---- The owner ----
    async act(order, action) {
        if (action === 'cancel' && !this.cancelReason.trim()) return this.say('Write why it is cancelled.', 'bad');
        try {
            const body = await this.api('/orders/' + order.id, { method: 'POST', body: JSON.stringify({ action, reason: this.cancelReason }) });
            this.orders = this.orders.map((o) => (o.id === order.id ? body.order : o)); save('orders', this.orders);
            this.cancelReason = '';
            this.say(action === 'accept' ? 'Order accepted. · Manzoor.' : 'Order cancelled.');
            this.sync();
        } catch (e) { this.say(navigator.onLine ? e.message : 'Needs internet. · Internet chahiye.', 'bad'); }
    },

    // ---- Links ----
    tel(phone) { return 'tel:' + String(phone || '').replace(/[^\d+]/g, ''); },
    wa(phone) { const d = String(phone || '').replace(/\D/g, ''); return 'https://wa.me/' + (d.startsWith('0') ? '92' + d.slice(1) : d); },
    maps(c) { return 'https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent([c.name, c.address].filter(Boolean).join(', ')); },

    statusIcon(s) { return ({ waiting: 'wifiOff', new: 'bell', accepted: 'checkCircle', delivered: 'receipt', cancelled: 'xCircle' })[s] || 'clock'; },
    statusBg(s) { return ({ waiting: 'bg-amber-100 text-amber-700', new: 'bg-sky-100 text-sky-700', accepted: 'bg-violet-100 text-violet-700', delivered: 'bg-emerald-100 text-emerald-700', cancelled: 'bg-slate-100 text-slate-500' })[s] || 'bg-slate-100 text-slate-500'; },
    statusText(s) { return ({ waiting: 'Not sent', new: 'New', accepted: 'Accepted', delivered: 'Sale made', cancelled: 'Cancelled' })[s] || s; },
    statusTone(s) { return ({ waiting: 'bg-amber-100 text-amber-800', new: 'bg-sky-100 text-sky-800', accepted: 'bg-violet-100 text-violet-800', delivered: 'bg-emerald-100 text-emerald-800', cancelled: 'bg-slate-200 text-slate-600' })[s] || ''; },
    dayLabel(iso) {
        const d = new Date(iso); const today = new Date(); const y = new Date(Date.now() - 864e5);
        if (d.toDateString() === today.toDateString()) return 'Today · Aaj';
        if (d.toDateString() === y.toDateString()) return 'Yesterday · Kal';
        return d.toLocaleDateString('en-GB', { weekday: 'short', day: 'numeric', month: 'short' });
    },
    time(iso) { return new Date(iso).toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' }); },
    when(iso) { const d = new Date(iso); return d.toLocaleDateString('en-GB', { day: 'numeric', month: 'short' }) + ' · ' + this.time(iso); },
    ago(iso) {
        if (!iso) return 'never';
        const m = Math.round((Date.now() - new Date(iso)) / 60000);
        return m < 1 ? 'just now' : m < 60 ? m + ' min ago' : m < 1440 ? Math.round(m / 60) + ' h ago' : this.when(iso);
    },
    initials(name) { return (name || '?').split(/\s+/).filter(Boolean).slice(0, 2).map((w) => w[0]).join('').toUpperCase(); },
    version: CFG.version || '2.0',
});

Alpine.start();

if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => navigator.serviceWorker.register(CFG.sw, { scope: CFG.scope }).catch(() => {}));
}
