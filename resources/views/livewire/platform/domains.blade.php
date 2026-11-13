<div>
    <x-page-header title="Domains" subtitle="Give each school its website address: a free address on your domain, and its own domain that you buy and set up for it. Schools cannot change these.">
        <a class="btn" href="{{ route('platform.domains', ['tab' => $tab === 'guide' ? 'list' : 'guide']) }}" wire:navigate>
            <x-icon :name="$tab === 'guide' ? 'list' : 'book-open'" class="h-4 w-4" /> {{ $tab === 'guide' ? 'Schools and addresses' : 'Setup guide' }}
        </a>
    </x-page-header>

    <nav class="mb-4 flex gap-1 border-b border-slate-200 text-sm" aria-label="Domain screens">
        @foreach (['list' => ['Schools and addresses', 'link'], 'guide' => ['Setup guide', 'book-open']] as $key => [$label, $icon])
            <a href="{{ route('platform.domains', ['tab' => $key]) }}" wire:navigate @class(['-mb-px flex items-center gap-2 border-b-2 px-3 py-2 font-medium', 'border-brand-600 text-brand-700' => $tab === $key, 'border-transparent text-slate-500 hover:text-slate-800' => $tab !== $key])>
                <x-icon :name="$icon" class="h-4 w-4" /> {{ $label }}
            </a>
        @endforeach
    </nav>

    @if (! $base || ! $serverIp)
        <div class="mb-4 flex items-start gap-3 rounded-xl bg-amber-50 p-3 text-sm text-amber-900 ring-1 ring-amber-200">
            <x-icon name="alert-circle" class="mt-0.5 h-5 w-5 shrink-0" />
            <div>
                <strong>One-time server setup is not finished.</strong>
                @unless ($base) Free addresses need <code class="rounded bg-white px-1">SITE_BASE_DOMAIN</code>. @endunless
                @unless ($serverIp) The guide and the DNS check need <code class="rounded bg-white px-1">SITE_SERVER_IP</code>. @endunless
                <a class="font-medium underline" href="{{ route('platform.domains', ['tab' => 'guide']) }}" wire:navigate>See step A of the guide.</a>
            </div>
        </div>
    @endif

    @if ($tab === 'list')
        <div class="mb-4 grid grid-cols-2 gap-3 lg:grid-cols-4">
            @foreach (['live' => ['Own domain working', 'check-circle', 'green'], 'waiting' => ['Being set up', 'clock', 'amber'], 'free' => ['Free addresses', 'link', 'blue'], 'renew' => ['Renew within 30 days', 'calendar', 'red']] as $key => [$label, $icon, $tone])
                <button wire:click="$set('filter', '{{ $key === 'free' || $filter === $key ? 'all' : $key }}')" @class(['action-tile text-start', 'ring-2 ring-brand-500' => $filter === $key])>
                    <div class="icon-chip tone-{{ $tone }}"><x-icon :name="$icon" /></div>
                    <div><div class="text-xl font-bold">{{ $counts[$key] }}</div><div class="text-xs text-slate-500">{{ $label }}</div></div>
                </button>
            @endforeach
        </div>

        <div class="card">
            <div class="flex flex-wrap items-center gap-2 border-b border-slate-200 p-3">
                <input class="input max-w-xs" type="search" placeholder="Search school or domain" wire:model.live.debounce.300ms="search" aria-label="Search">
                <select class="input w-auto" wire:model.live="filter" aria-label="Show">
                    <option value="all">All schools</option>
                    <option value="live">Own domain working</option>
                    <option value="waiting">Being set up</option>
                    <option value="none">No own domain</option>
                    <option value="renew">Renew within 30 days</option>
                </select>
            </div>
            <div class="overflow-x-auto">
                <table class="tbl">
                    <thead><tr><th>School</th><th>Free address</th><th>Own domain</th><th>Renewal</th><th></th></tr></thead>
                    <tbody>
                    @forelse ($schools as $school)
                        @php($site = $school->site)
                        @php($check = $site?->domain_check)
                        <tr wire:key="dom-{{ $school->id }}">
                            <td>
                                <a class="font-medium text-brand-600 hover:underline" href="{{ route('platform.school', [$school, 'tab' => 'website']) }}" wire:navigate>{{ $school->name }}</a>
                                <div class="text-xs text-slate-500">
                                    @if (! $site) No website yet @else {{ $site->is_published ? 'Website public' : 'Website is a draft' }} @endif
                                </div>
                                {{-- Any of these makes the school's address show "Not found". --}}
                                @if ($site && ! $site->is_published)
                                    <div class="text-xs font-medium text-red-700">Not found on its address until published</div>
                                @endif
                                @if (! $school->hasFeature('website'))
                                    <div class="text-xs font-medium text-red-700">Website is off in this school's plan</div>
                                @endif
                                @if ($school->isLocked())
                                    <div class="text-xs font-medium text-red-700">Account locked, website hidden</div>
                                @endif
                            </td>
                            <td>
                                @if ($site?->subdomainHost())
                                    <a class="font-mono text-xs text-brand-600 underline" href="{{ config('sites.scheme') }}://{{ $site->subdomainHost() }}" target="_blank" rel="noopener">{{ $site->subdomainHost() }}</a>
                                @elseif ($site?->subdomain)
                                    <span class="font-mono text-xs">{{ $site->subdomain }}</span> <span class="text-xs text-amber-700">needs SITE_BASE_DOMAIN</span>
                                @else
                                    <span class="text-xs text-slate-400">None</span>
                                @endif
                            </td>
                            <td>
                                @if ($site?->domain)
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="font-mono text-xs">{{ $site->domain }}</span>
                                        <span @class(['badge', 'badge-green' => $site->domain_status === 'live', 'badge-amber' => $site->domain_status === 'waiting'])>{{ $site->domain_status === 'live' ? 'Working' : 'Being set up' }}</span>
                                    </div>
                                    @if ($check)
                                        <div class="mt-1 flex flex-wrap gap-x-3 gap-y-0.5 text-[11px] text-slate-500">
                                            <span @class(['text-emerald-700' => $check['ips'], 'text-red-700' => ! $check['ips']])>DNS {{ $check['ips'] ? implode(', ', $check['ips']) : 'not found' }}@if ($check['points'] === false) (not this server)@endif</span>
                                            <span @class(['text-emerald-700' => $check['reaches'], 'text-red-700' => ! $check['reaches']])>{{ $check['reaches'] ? 'Opens this website' : 'Does not open this website' }}</span>
                                            <span @class(['text-emerald-700' => $check['https'], 'text-red-700' => ! $check['https']])>SSL {{ $check['https'] ? 'working' : 'not ready' }}</span>
                                            <span>checked {{ \Illuminate\Support\Carbon::parse($check['at'])->diffForHumans() }}</span>
                                        </div>
                                    @endif
                                @else
                                    <span class="text-xs text-slate-400">None</span>
                                @endif
                            </td>
                            <td>
                                @if ($site?->domain_expires_on)
                                    @php($days = (int) today()->diffInDays($site->domain_expires_on, false))
                                    <div @class(['text-sm', 'font-semibold text-red-700' => $days <= 30])>{{ $site->domain_expires_on->format('j M Y') }}</div>
                                    <div class="text-xs text-slate-500">{{ $days >= 0 ? $days.' days left' : abs($days).' days ago' }}{{ $site->domain_registrar ? ' · '.$site->domain_registrar : '' }}</div>
                                @else
                                    <span class="text-xs text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="text-end whitespace-nowrap">
                                <div class="flex justify-end gap-1.5">
                                    @if ($site?->domain || $site?->subdomainHost())
                                        <button class="btn btn-sm" wire:click="check({{ $site->id }})" wire:loading.attr="disabled" wire:target="check({{ $site->id }})">
                                            <x-icon name="repeat" class="h-3.5 w-3.5" /> <span wire:loading.remove wire:target="check({{ $site->id }})">Check</span><span wire:loading wire:target="check({{ $site->id }})">Checking…</span>
                                        </button>
                                    @endif
                                    @if ($site?->domain)
                                        <a class="btn btn-sm" href="{{ route('platform.domains', ['tab' => 'guide', 'guideSite' => $site->id]) }}" wire:navigate title="Setup guide for this domain"><x-icon name="book-open" class="h-3.5 w-3.5" /></a>
                                    @endif
                                    <button class="btn btn-sm btn-primary" wire:click="openForm({{ $school->id }})"><x-icon name="pencil" class="h-3.5 w-3.5" /> {{ $site?->domain || $site?->subdomain ? 'Edit' : 'Set address' }}</button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="p-6 text-center text-slate-500">No schools match.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    @if ($tab === 'guide')
        @include('livewire.platform.domains-guide')
    @endif

    @if ($showForm)
        @php($editing = \App\Models\Site::with('school')->find($siteId))
        <x-modal :title="'Website address: '.$editing?->school?->name" close="$set('showForm', false)">
            <form wire:submit="save" class="space-y-4">
                @if ($base)
                    <x-field label="Free address" name="subdomain" hint="Only the name, like school-name. Works as soon as you save, once the one-time setup is done. Small letters, numbers and dashes.">
                        <div class="flex items-center">
                            <input id="subdomain" class="input rounded-e-none" wire:model="subdomain" placeholder="school-name" dir="ltr">
                            <span class="rounded-e-lg border border-s-0 border-slate-300 bg-slate-50 px-3 py-2 text-sm whitespace-nowrap text-slate-500">.{{ $base }}</span>
                        </div>
                    </x-field>
                @else
                    <p class="rounded-lg bg-slate-50 px-3 py-2 text-sm text-slate-600">Free addresses (school-name.your-domain.com) are off. Write the school's own domain below.</p>
                @endif

                <x-field label="School's own domain" name="domain" hint="For example alhamraschool.edu.pk. Leave empty if the school does not have one.">
                    <input id="domain" class="input" wire:model="domain" placeholder="school.edu.pk" dir="ltr">
                </x-field>
                @if ($editing?->domain)
                    <div class="flex flex-wrap items-center gap-2 rounded-lg bg-slate-50 p-2.5 text-sm">
                        <span>Status: <strong>{{ $editing->domain_status === 'live' ? 'Working' : 'Being set up' }}</strong></span>
                        @if ($editing->domain_status === 'live')
                            <button type="button" class="btn btn-sm" wire:click="setStatus({{ $editing->id }}, 'waiting')" wire:confirm="Put this domain on hold? Links go back to the old address.">Put on hold</button>
                        @else
                            <button type="button" class="btn btn-sm" wire:click="setStatus({{ $editing->id }}, 'live')" wire:confirm="Mark it working without a check? Only do this if you opened https://{{ $editing->domain }} yourself and saw the school's website.">Mark as working</button>
                        @endif
                        <button type="button" class="btn btn-sm btn-danger" wire:click="removeDomain({{ $editing->id }})" wire:confirm="Remove {{ $editing->domain }} from this school?">Remove domain</button>
                    </div>
                @endif

                <div class="grid gap-3 sm:grid-cols-2">
                    <x-field label="Bought from" name="registrar" hint="PKNIC, Namecheap, GoDaddy…"><input id="registrar" class="input" wire:model="registrar"></x-field>
                    <x-field label="Renew by" name="expiresOn"><input id="expiresOn" class="input" type="date" wire:model="expiresOn"></x-field>
                </div>
                <x-field label="Private notes" name="notes" hint="Only you see these. Account name, who pays, anything to remember. Do not write passwords here.">
                    <textarea id="notes" class="input" rows="3" wire:model="notes"></textarea>
                </x-field>
                <div class="flex justify-end gap-2">
                    <button type="button" class="btn" wire:click="$set('showForm', false)">Cancel</button>
                    <button class="btn btn-primary" wire:loading.attr="disabled">Save</button>
                </div>
            </form>
        </x-modal>
    @endif
</div>
