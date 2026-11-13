<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Attendance · {{ $school->name }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-brand-900 font-sans text-white antialiased">
    <main class="mx-auto flex min-h-screen max-w-3xl flex-col items-center justify-center gap-8 p-6 text-center">
        <div>
            <div class="text-lg font-semibold opacity-80">{{ $school->name }}</div>
            <div class="text-sm opacity-60">{{ $device->name }}</div>
        </div>
        <div>
            <div class="text-7xl font-bold tabular-nums" id="clock">--:--</div>
            <div class="mt-1 text-lg opacity-80" id="date"></div>
        </div>

        <div id="card" class="w-full max-w-md rounded-3xl bg-white/10 p-8 transition-colors" role="status" aria-live="polite">
            <div class="mx-auto mb-4 grid h-24 w-24 place-items-center rounded-full bg-white/15 text-5xl" aria-hidden="true" id="icon">☝</div>
            <div class="text-2xl font-semibold" id="title">Starting the reader...</div>
            <div class="mt-2 opacity-80" id="detail"></div>
        </div>

        <ul class="w-full max-w-md space-y-1 text-start text-sm opacity-80" id="recent" aria-label="Marked just now"></ul>
    </main>

    @include('fingerprint-reader')
    <script>
        const clock = () => {
            const now = new Date();
            document.getElementById('clock').textContent = now.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
            document.getElementById('date').textContent = now.toLocaleDateString([], { weekday: 'long', day: 'numeric', month: 'long' });
        };
        clock();
        setInterval(clock, 1000);

        const card = document.getElementById('card');
        let busy = false;
        let resetTimer = null;
        const show = (tone, icon, title, detail = '') => {
            card.className = 'w-full max-w-md rounded-3xl p-8 transition-colors ' + ({ ok: 'bg-emerald-500/90', bad: 'bg-red-500/90', wait: 'bg-white/20' }[tone] || 'bg-white/10');
            document.getElementById('icon').textContent = icon;
            document.getElementById('title').textContent = title;
            document.getElementById('detail').textContent = detail;
            clearTimeout(resetTimer);
            if (tone === 'ok' || tone === 'bad') resetTimer = setTimeout(() => show('', '☝', 'Place your finger on the reader'), 4000);
        };

        const settings = @js($helper);
        const helper = window.fingerHelper(settings);
        const headers = { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-Kiosk': '1' };
        let candidates = [];
        let loadedAt = 0;

        // With the helper on this computer, keep a fresh copy of the registered fingers to compare with.
        const loadCandidates = async (force = false) => {
            if (! force && Date.now() - loadedAt < 5 * 60 * 1000) return;
            const response = await fetch(@js(route('kiosk.candidates')), { headers, credentials: 'same-origin' });
            if (! response.ok) throw new Error('Could not load the registered fingers from the school system.');
            candidates = await response.json();
            loadedAt = Date.now();
        };

        // Says plainly when the helper is not running, and checks again until it is.
        const watchHelper = async () => {
            try {
                await helper.health();
                await loadCandidates();
            } catch (e) {
                show('bad', '!', 'Fingerprint helper not ready', e.message);
            }
            setTimeout(watchHelper, 30000);
        };
        if (settings.local) watchHelper();

        window.fingerReader(async (png) => {
            if (busy) return;
            busy = true;
            show('wait', '⏳', 'Checking...');
            try {
                let body = { image: png };
                if (settings.local) {
                    await loadCandidates();
                    const match = await helper.identify(png, candidates);
                    if (! match.id) {
                        show('bad', '✕', 'Not recognised. Press the same finger again, or see the office.');
                        return;
                    }
                    body = { staff_id: Number(match.id), score: match.score };
                }
                const response = await fetch(@js(route('kiosk.punch')), { method: 'POST', headers, credentials: 'same-origin', body: JSON.stringify(body) });
                const result = await response.json().catch(() => ({}));
                if (response.ok) {
                    show('ok', '✓', result.action + ', ' + result.name, result.detail);
                    const item = document.createElement('li');
                    item.textContent = result.time + '  ' + result.name + ' · ' + result.detail;
                    const list = document.getElementById('recent');
                    list.prepend(item);
                    while (list.children.length > 5) list.lastChild.remove();
                } else {
                    show('bad', '✕', result.message || 'Not recognised. Try again.');
                }
            } catch (e) {
                show('bad', '✕', e && e.message && e.message.includes('helper') ? e.message : 'No connection to the school system. Check the internet.');
            } finally {
                busy = false;
            }
        }, (state, message) => {
            if (state === 'ready') show('', '☝', 'Place your finger on the reader');
            else if (state === 'retry') show('bad', '✕', message);
            else show('bad', '!', 'Reader not ready', message);
        });
    </script>
</body>
</html>
