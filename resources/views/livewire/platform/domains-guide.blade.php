@php
    $ip = $serverIp ?: 'YOUR.SERVER.IP';
    $main = $base ?: 'your-domain.com';
    $school = $guide?->domain ?: 'school-domain.edu.pk';
@endphp
<div class="grid gap-5 xl:grid-cols-[1fr_18rem]">
    <div class="space-y-5">
        {{-- A. Once for the whole system --}}
        <section class="card-pad">
            <h2 class="panel-title"><span class="icon-chip tone-violet h-8! w-8! text-sm font-bold">A</span> One-time setup, done once for all schools</h2>
            <ol class="mt-4 space-y-4 text-sm">
                <li>
                    <div class="font-semibold">1. Point your own domain to the server</div>
                    <p class="text-slate-600">In the DNS settings of <strong>{{ $main }}</strong>, add these records. The star record gives every school a free address such as <span class="font-mono">school-name.{{ $main }}</span> with no more DNS work later.</p>
                    @include('livewire.platform.domains-records', ['rows' => [['A', '@', $ip], ['A', '*', $ip], ['A', 'www', $ip]]])
                </li>
                <li>
                    <div class="font-semibold">2. Tell the software its addresses</div>
                    <p class="text-slate-600">Put these lines in the <span class="font-mono">.env</span> file on the server, then run <span class="font-mono">php artisan config:cache</span>.</p>
                    <x-copy-block>APP_URL=https://{{ $main }}
SITE_BASE_DOMAIN={{ $main }}
SITE_SERVER_IP={{ $ip }}
SITE_SCHEME=https</x-copy-block>
                    <p class="mt-1 text-xs text-slate-500">Now: base domain <strong>{{ $base ?: 'not set' }}</strong>, server IP <strong>{{ $serverIp ?: 'not set' }}</strong>, software runs at <strong>{{ $appHost }}</strong>.</p>
                </li>
                <li>
                    <div class="font-semibold">3. Let the web server answer for every address</div>
                    <div class="mt-2 grid gap-3 lg:grid-cols-2">
                        <div class="rounded-xl border border-slate-200 p-3">
                            <div class="mb-1 flex items-center gap-2 font-medium"><x-icon name="layers" class="h-4 w-4 text-sky-600" /> Shared hosting (cPanel)</div>
                            <ul class="list-disc space-y-1 ps-5 text-slate-600">
                                <li>Put the software's <span class="font-mono">public</span> folder as the document root of {{ $main }}.</li>
                                <li>Domains → Create a new domain → <span class="font-mono">*.{{ $main }}</span>, with the same document root.</li>
                                <li>SSL for the star address: many hosts' AutoSSL cannot make it. Ask the host for wildcard SSL, or move the DNS of {{ $main }} to Cloudflare (free) and keep the orange cloud on. Cloudflare then gives SSL to every free address.</li>
                            </ul>
                        </div>
                        <div class="rounded-xl border border-slate-200 p-3">
                            <div class="mb-1 flex items-center gap-2 font-medium"><x-icon name="device" class="h-4 w-4 text-emerald-600" /> VPS with Caddy (best for many schools)</div>
                            <p class="text-slate-600">Caddy makes the SSL for every school's address by itself. It first asks this software whether the address belongs to a school.</p>
                            <x-copy-block>{
    on_demand_tls {
        ask {{ route('domains.allowed') }}
    }
}

https:// {
    tls {
        on_demand
    }
    root * /var/www/msk-devs/public
    php_fastcgi unix//run/php/php8.2-fpm.sock
    encode gzip
    file_server
}</x-copy-block>
                        </div>
                    </div>
                </li>
            </ol>
        </section>

        {{-- B. Each school with its own domain --}}
        <section class="card-pad">
            <h2 class="panel-title"><span class="icon-chip tone-brand h-8! w-8! text-sm font-bold">B</span> For each school that gets its own domain</h2>
            @if ($guideSites->isNotEmpty())
                <label class="mt-3 flex flex-wrap items-center gap-2 text-sm">
                    <span class="text-slate-600">Fill the steps for</span>
                    <select class="input w-auto" wire:model.live="guideSite">
                        <option value="">Example domain</option>
                        @foreach ($guideSites as $item)<option value="{{ $item->id }}">{{ $item->school->name }} ({{ $item->domain }})</option>@endforeach
                    </select>
                </label>
            @endif
            <ol class="mt-4 space-y-4 text-sm">
                <li>
                    <div class="font-semibold">1. Buy the domain</div>
                    <ul class="list-disc space-y-1 ps-5 text-slate-600">
                        <li><span class="font-mono">.pk</span> and <span class="font-mono">.edu.pk</span>: from PKNIC or one of its resellers. <span class="font-mono">.edu.pk</span> usually needs the school's registration papers on its letterhead, so ask the school for them first.</li>
                        <li><span class="font-mono">.com</span> and others: from Namecheap, GoDaddy or any registrar.</li>
                        <li>Keep the domain account login safe with you, and write the renewal date in this panel so it shows when it is near.</li>
                    </ul>
                </li>
                <li>
                    <div class="font-semibold">2. Set its DNS records</div>
                    <p class="text-slate-600">In the DNS settings of <strong>{{ $school }}</strong>, delete any old A records or parking page, then add:</p>
                    @include('livewire.platform.domains-records', ['rows' => [['A', '@', $ip], ['CNAME', 'www', $school]]])
                    <p class="mt-1 text-xs text-slate-500">Changes usually work within an hour, but can take up to 24 hours.</p>
                </li>
                <li>
                    <div class="font-semibold">3. Add it on the server</div>
                    <ul class="list-disc space-y-1 ps-5 text-slate-600">
                        <li><strong>cPanel:</strong> Domains → Create a new domain → <span class="font-mono">{{ $school }}</span>, document root the same <span class="font-mono">public</span> folder. Then SSL/TLS Status → Run AutoSSL.</li>
                        <li><strong>VPS with Caddy:</strong> nothing to do. SSL is made on the first visit.</li>
                    </ul>
                </li>
                <li>
                    <div class="font-semibold">4. Record it here and check</div>
                    <p class="text-slate-600">On the Schools and addresses screen press <strong>Set address</strong>, write the domain, save, then press <strong>Check</strong>. The check confirms three things:</p>
                    <ul class="mt-1 space-y-1">
                        <li class="flex items-center gap-2"><x-icon name="check-circle" class="h-4 w-4 text-emerald-600" /> DNS points to {{ $ip }}</li>
                        <li class="flex items-center gap-2"><x-icon name="check-circle" class="h-4 w-4 text-emerald-600" /> The domain opens this school's website, not another one</li>
                        <li class="flex items-center gap-2"><x-icon name="check-circle" class="h-4 w-4 text-emerald-600" /> SSL works (https)</li>
                    </ul>
                    <p class="mt-1 text-slate-600">When all three pass, it turns <span class="badge badge-green">Working</span> by itself. Every link then uses the new domain, and old addresses move to it.</p>
                </li>
                <li>
                    <div class="font-semibold">5. Hand it over</div>
                    <p class="text-slate-600">Send the school its address. They keep editing the website from their own panel as before. Only you can change the address.</p>
                </li>
            </ol>
        </section>
    </div>

    <aside class="space-y-4">
        <div class="card-pad text-sm">
            <h2 class="panel-title"><x-icon name="alert-circle" class="h-5 w-5 text-amber-600" /> When the check fails</h2>
            <dl class="mt-3 space-y-3">
                <div><dt class="font-medium">DNS not found</dt><dd class="text-slate-600">Records not saved yet, or still spreading. Wait an hour. Check the domain's name servers are the ones you edited.</dd></div>
                <div><dt class="font-medium">Points to another IP</dt><dd class="text-slate-600">An old A record or the registrar's parking page is still there. Delete it.</dd></div>
                <div><dt class="font-medium">Does not open this website</dt><dd class="text-slate-600">The domain is not added on the server (step B3), or it is written differently here.</dd></div>
                <div><dt class="font-medium">SSL not ready</dt><dd class="text-slate-600">Run AutoSSL again in cPanel, or wait a few minutes on Caddy. With Cloudflare, set SSL mode to Full.</dd></div>
            </dl>
        </div>
        <div class="card-pad text-sm">
            <h2 class="panel-title"><x-icon name="shield" class="h-5 w-5 text-brand-600" /> Good to know</h2>
            <ul class="mt-3 list-disc space-y-1.5 ps-5 text-slate-600">
                <li>A school's domain shows only its public website. Signing in and dashboards stay on {{ $appHost }}.</li>
                <li>If a school's subscription is locked, its website stops on every address.</li>
                <li>"Mark as working" skips the check. Use it only after opening the https address yourself.</li>
            </ul>
        </div>
    </aside>
</div>
