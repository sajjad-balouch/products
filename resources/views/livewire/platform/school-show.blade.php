<div>
    <x-page-header :title="$school->name" :subtitle="$school->email.' · '.$school->phone.' · joined '.$school->created_at->format('j M Y')">
        <a class="btn" href="{{ route('platform.schools') }}" wire:navigate>All schools</a>
        @if ($reminder = $school->reminderUrl())<a class="btn" href="{{ $reminder }}" target="_blank" rel="noopener">Remind on WhatsApp</a>@endif
        @if ($admin)<button class="btn btn-primary" wire:click="loginAs({{ $admin->id }})">Open as school admin</button>@endif
    </x-page-header>

    <div class="card-pad mb-4 flex flex-wrap items-center gap-x-6 gap-y-2 text-sm">
        <x-school-status :school="$school" />
        <span>Access until <strong>{{ $school->accessUntil()?->format('j M Y') ?? 'no end date' }}</strong>@if ($school->daysLeft() !== null) <span class="text-slate-500">({{ $school->daysLeft() >= 0 ? $school->daysLeft().' days left' : abs($school->daysLeft()).' days ago' }})</span>@endif</span>
        <span>Monthly fee <strong>{{ $school->monthly_fee ? 'Rs '.number_format($school->monthly_fee) : 'not set' }}</strong></span>
        <span>Plan <strong>{{ $school->planName() }}</strong></span>
        <button class="btn btn-sm ms-auto {{ $school->is_active ? 'btn-danger' : 'btn-primary' }}" wire:click="toggleActive" wire:confirm="{{ $school->is_active ? 'Suspend '.$school->name.'? All its users are signed out at once.' : 'Activate '.$school->name.'?' }}">{{ $school->is_active ? 'Suspend school' : 'Activate school' }}</button>
    </div>

    @if ($issued)
        <div class="mb-4 flex flex-wrap items-start gap-3 rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900" x-data="{ done: false }">
            <x-icon name="key" class="mt-0.5 h-5 w-5 shrink-0" />
            <div class="min-w-0 flex-1">
                <p class="font-semibold">New password{{ isset($issued['name']) ? ' for '.$issued['name'] : '' }}. It is shown only once, so send it now.</p>
                <p class="mt-1">Username <span class="font-mono font-semibold">{{ $issued['username'] }}</span> · Password <span class="font-mono font-semibold">{{ $issued['password'] }}</span></p>
                <p class="mt-1 text-xs">{{ ($issued['mustChange'] ?? true) ? 'They must choose their own password when they sign in.' : 'They can keep using this password.' }} They were signed out everywhere.</p>
            </div>
            <div class="flex gap-2">
                <button type="button" class="btn btn-sm" @click="navigator.clipboard.writeText(@js('Username: '.$issued['username'].'  Password: '.$issued['password'])); done = true">
                    <x-icon name="clipboard" class="h-3.5 w-3.5" /> <span x-text="done ? 'Copied' : 'Copy'">Copy</span>
                </button>
                <button type="button" class="btn btn-sm" wire:click="$set('issued', null)">Done</button>
            </div>
        </div>
    @endif

    <div class="mb-4 flex flex-wrap gap-1 rounded-lg border border-slate-300 bg-white p-0.5 sm:w-fit">
        @foreach ($tabs as $key => $label)
            <button wire:key="tab-{{ $key }}" @class(['rounded-md px-3 py-1.5 text-sm', 'bg-brand-600 text-white' => $tab === $key, 'text-slate-600' => $tab !== $key]) wire:click="$set('tab', '{{ $key }}')">{{ $label }}</button>
        @endforeach
    </div>

    @if ($tab === 'overview')
        <div class="mb-4 grid gap-3 sm:grid-cols-3 lg:grid-cols-6">
            @foreach ($stats as $label => $value)<x-stat :label="$label" :value="$value" />@endforeach
        </div>
        <form wire:submit="saveDetails" class="card-pad grid max-w-3xl gap-3 sm:grid-cols-2">
            <x-field label="Name" name="details.name"><input id="details.name" class="input" wire:model="details.name"></x-field>
            <div class="text-sm sm:pt-6">Plan: <strong>{{ $school->planName() }}</strong> <button type="button" class="text-brand-600 underline" wire:click="$set('tab', 'billing')">change</button></div>
            <x-field label="Phone" name="details.phone"><input id="details.phone" class="input" wire:model="details.phone"></x-field>
            <x-field label="Email" name="details.email"><input id="details.email" class="input" wire:model="details.email"></x-field>
            <x-field label="Address" name="details.address" class="sm:col-span-2"><input id="details.address" class="input" wire:model="details.address"></x-field>
            <x-field label="Your private note" name="details.owner_note" hint="Only you see this" class="sm:col-span-2"><textarea id="details.owner_note" class="input" rows="3" wire:model="details.owner_note"></textarea></x-field>
            <div class="flex justify-end sm:col-span-2"><button class="btn btn-primary">Save details</button></div>
        </form>

        <div class="mt-6 flex max-w-3xl flex-wrap items-center justify-between gap-3 rounded-xl border border-red-200 bg-red-50/60 p-4">
            <div class="flex items-start gap-3">
                <div class="icon-chip tone-red"><x-icon name="warning" /></div>
                <div>
                    <div class="font-semibold text-red-800">Delete this school</div>
                    <p class="text-sm text-red-700">Removes the school and everything in it for good: students, fees, staff, logins, website and uploaded files. To only stop it for now, use Suspend instead.</p>
                </div>
            </div>
            <button type="button" class="btn btn-danger" wire:click="openDelete"><x-icon name="x-circle" class="h-4 w-4" /> Delete school</button>
        </div>
    @endif

    @if ($tab === 'billing')
        <div class="grid gap-4 lg:grid-cols-2">
            <form wire:submit="recordPayment" class="card-pad space-y-3">
                <h2 class="font-semibold">Record a payment</h2>
                <div class="grid gap-3 sm:grid-cols-2">
                    <x-field label="Amount (Rs)" name="amount"><input id="amount" class="input" type="number" min="1" wire:model="amount"></x-field>
                    <x-field label="Months it pays for" name="months"><input id="months" class="input" type="number" min="1" max="36" wire:model="months"></x-field>
                    <x-field label="Paid by" name="method"><select id="method" class="input" wire:model="method">@foreach (\App\Models\SubscriptionEntry::METHODS as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></x-field>
                    <x-field label="Payment date" name="paidOn"><input id="paidOn" class="input" type="date" wire:model="paidOn"></x-field>
                    <x-field label="Reference" name="reference" hint="Transaction or receipt number"><input id="reference" class="input" wire:model="reference"></x-field>
                    <x-field label="Note" name="payNote"><input id="payNote" class="input" wire:model="payNote"></x-field>
                </div>
                <p class="text-xs text-slate-500">The months are added after the current end date, or from today if it has passed. A suspended school is activated.</p>
                <div class="flex justify-end"><button class="btn btn-primary" wire:loading.attr="disabled">Record payment</button></div>
            </form>

            <div class="space-y-4">
                <form wire:submit="applyPlan" class="card-pad space-y-3">
                    <h2 class="font-semibold">Plan</h2>
                    <x-field label="Put this school on" name="planCode">
                        <select id="planCode" class="input" wire:model="planCode">
                            @if ($school->plan === 'trial')<option value="trial" disabled>Free period (choose a plan)</option>@endif
                            @foreach ($plans as $p)<option value="{{ $p->code }}">{{ $p->name }}, {{ $p->monthly_price ? 'Rs '.number_format($p->monthly_price).' a month' : 'price not set' }}, {{ count($p->features) }} features</option>@endforeach
                        </select>
                    </x-field>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="keepFee"> Keep this school's own monthly fee (a special price)</label>
                    <p class="text-xs text-slate-500">Sets the school's features to the plan's, and its fee unless you keep its own. <a class="underline" href="{{ route('platform.plans') }}" wire:navigate>Edit plans</a></p>
                    <div class="flex justify-end"><button class="btn" wire:loading.attr="disabled">Apply plan</button></div>
                </form>

                <form wire:submit="giveFree" class="card-pad space-y-3">
                    <h2 class="font-semibold">Give free months</h2>
                    <div class="grid gap-3 sm:grid-cols-[8rem_1fr]">
                        <x-field label="Months" name="freeMonths"><input id="freeMonths" class="input" type="number" min="1" max="36" wire:model="freeMonths"></x-field>
                        <x-field label="Reason" name="freeNote" hint="For example: launch offer"><input id="freeNote" class="input" wire:model="freeNote"></x-field>
                    </div>
                    <div class="flex justify-end"><button class="btn" wire:loading.attr="disabled">Add free months</button></div>
                </form>

                <form wire:submit="saveBilling" class="card-pad space-y-3">
                    <h2 class="font-semibold">Fee settings</h2>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <x-field label="Monthly fee (Rs)" name="monthlyFee"><input id="monthlyFee" class="input" type="number" min="0" wire:model="monthlyFee"></x-field>
                        <x-field label="Extra days after the end date" name="graceDays" hint="Time to pay before the school is locked"><input id="graceDays" class="input" type="number" min="0" max="90" wire:model="graceDays"></x-field>
                    </div>
                    <label class="flex items-center gap-2 text-sm"><input type="checkbox" wire:model="lockWhenDue"> Lock the school when the extra days run out</label>
                    <p class="text-xs text-slate-500">A locked school's staff and parents see a "subscription ended" page. Nothing is deleted, and it opens again as soon as you record a payment or free months.</p>
                    <div class="flex justify-end"><button class="btn" wire:loading.attr="disabled">Save fee settings</button></div>
                </form>

                <form wire:submit="setDate" class="card-pad space-y-3">
                    <h2 class="font-semibold">Set the end date by hand</h2>
                    <div class="grid gap-3 sm:grid-cols-[11rem_1fr]">
                        <x-field label="Access until" name="manualDate"><input id="manualDate" class="input" type="date" wire:model="manualDate"></x-field>
                        <x-field label="Reason" name="manualNote"><input id="manualNote" class="input" wire:model="manualNote"></x-field>
                    </div>
                    <div class="flex justify-end"><button class="btn" wire:loading.attr="disabled">Change date</button></div>
                </form>
            </div>
        </div>

        <div class="card mt-4 overflow-x-auto">
            <div class="border-b border-slate-200 px-4 py-3 text-sm font-semibold">History</div>
            <table class="tbl">
                <thead><tr><th>Date</th><th>What</th><th>Amount</th><th>Period</th><th>Details</th><th>By</th><th></th></tr></thead>
                <tbody>
                @forelse ($entries as $entry)
                    <tr wire:key="en-{{ $entry->id }}">
                        <td>{{ ($entry->paid_on ?? $entry->created_at)->format('j M Y') }}</td>
                        <td>{{ \App\Models\SubscriptionEntry::TYPES[$entry->type] }}@if ($entry->months) · {{ $entry->months }} {{ str('month')->plural($entry->months) }}@endif</td>
                        <td>{{ $entry->amount ? 'Rs '.number_format($entry->amount) : '' }}</td>
                        <td>{{ $entry->from_date ? $entry->from_date->format('j M Y').' to ' : 'Until ' }}{{ $entry->to_date->format('j M Y') }}</td>
                        <td class="text-xs">{{ $entry->method ? \App\Models\SubscriptionEntry::METHODS[$entry->method] : '' }} {{ $entry->reference }} {{ $entry->note }}</td>
                        <td class="text-xs">{{ $entry->user?->name }}</td>
                        <td class="text-end">@if ($entry->type === 'payment')<a class="btn btn-sm" href="{{ route('platform.receipt', $entry) }}" target="_blank" rel="noopener">Receipt</a>@endif</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="p-6 text-center text-slate-500">Nothing recorded yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    @endif

    @if ($tab === 'features')
        <form wire:submit="saveFeatures" class="card-pad max-w-3xl space-y-3">
            <p class="text-sm text-slate-600">Untick a feature to switch it off for this school. Its menu links disappear and its pages are blocked. The school's records are kept, and ticking it again brings everything back.</p>
            <div class="grid gap-2 sm:grid-cols-2">
                @foreach (\App\Models\School::featuresFor($school->product()) as $key => [$label])
                    <label class="flex items-center gap-2 rounded-lg border border-slate-200 p-2.5 text-sm" wire:key="ft-{{ $key }}"><input type="checkbox" value="{{ $key }}" wire:model="features"> {{ $label }}</label>
                @endforeach
            </div>
            <p class="text-xs text-slate-500">Students, classes, settings and logins are always on.</p>
            <div class="flex justify-end"><button class="btn btn-primary" wire:loading.attr="disabled">Save features</button></div>
        </form>
    @endif

    @if ($tab === 'users')
        @if ($admin)
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-center gap-3">
                    <div class="icon-chip tone-brand"><x-icon name="key" /></div>
                    <div>
                        <div class="font-semibold">Principal forgot their password?</div>
                        <div class="text-sm text-slate-500">Set a new one for <strong>{{ $admin->name }}</strong> ({{ $admin->username }}) and send it to them.</div>
                    </div>
                </div>
                <button class="btn btn-primary" wire:click="openPassword({{ $admin->id }})"><x-icon name="key" class="h-4 w-4" /> Change principal's password</button>
            </div>
        @endif
        <div class="card overflow-x-auto">
            <table class="tbl">
                <thead><tr><th>Name</th><th>Username</th><th>Role</th><th>Last sign-in</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse ($staffUsers as $user)
                    <tr wire:key="us-{{ $user->id }}">
                        <td class="font-medium">{{ $user->name }}</td>
                        <td class="font-mono text-xs">{{ $user->username }}</td>
                        <td>{{ \App\Models\User::ROLES[$user->role] ?? str_replace('_', ' ', $user->role) }}</td>
                        <td class="text-xs">{{ $user->last_login_at?->diffForHumans() ?? 'Never' }}</td>
                        <td><span @class(['badge', 'badge-green' => $user->is_active, 'badge-red' => ! $user->is_active])>{{ $user->is_active ? 'Active' : 'Disabled' }}</span></td>
                        <td class="text-end whitespace-nowrap">
                            <button class="btn btn-sm" wire:click="loginAs({{ $user->id }})">Sign in as</button>
                            <button @class(['btn btn-sm', 'btn-primary' => $user->role === 'admin']) wire:click="openPassword({{ $user->id }})"><x-icon name="key" class="h-3.5 w-3.5" /> Change password</button>
                            <button class="btn btn-sm" wire:click="toggleUser({{ $user->id }})">{{ $user->is_active ? 'Disable' : 'Enable' }}</button>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="p-6 text-center text-slate-500">No staff logins.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <p class="mt-2 text-xs text-slate-500">{{ number_format($parentCount) }} parent and student logins are not listed. "Sign in as" opens the system exactly as that person sees it. A purple bar takes you back, and every step is in the school's audit log.</p>
    @endif

    @if ($tab === 'website')
        <div class="card-pad max-w-3xl space-y-4 text-sm">
            @if (! $site)
                <p>This school has not opened its website yet.</p>
                <button class="btn btn-primary" wire:click="createSite">Create website</button>
            @else
                <div class="flex flex-wrap items-center gap-3">
                    <span @class(['badge', 'badge-green' => $site->is_published, 'badge-amber' => ! $site->is_published])>{{ $site->is_published ? 'Public' : 'Draft' }}</span>
                    <a class="font-mono text-xs text-brand-600 underline" href="{{ $site->url() }}" target="_blank" rel="noopener">{{ $site->url() }}</a>
                    <span class="text-slate-500">{{ $sitePages }} {{ str('page')->plural($sitePages) }}, {{ $siteMessages }} unread {{ str('message')->plural($siteMessages) }}</span>
                    @unless ($school->hasFeature('website'))<span class="badge badge-red">Website feature is off</span>@endunless
                </div>
                <div class="flex flex-wrap gap-2">
                    <a class="btn" href="{{ $site->url() }}" target="_blank" rel="noopener">View website</a>
                    @if ($admin)<button class="btn btn-primary" wire:click="loginAs({{ $admin->id }}, 'website')">Edit website as school admin</button>@endif
                    @if ($site->is_published)
                        <button class="btn" wire:click="setSitePublished(false)" wire:confirm="Take this school's website offline?">Make it a draft</button>
                    @else
                        <button class="btn" wire:click="setSitePublished(true)" wire:confirm="Make this school's website public?">Publish</button>
                    @endif
                    <button class="btn btn-danger" wire:click="useSchoolTemplate" wire:confirm="Replace all of this school's website pages and menus with the complete school template? Colours, logo, footer and contact details stay.">Use school template</button>
                </div>
                <p class="text-xs text-slate-500">To change sections, pictures, colours or menus, use "Edit website as school admin". You get the school's full website manager.</p>
                <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200 p-3">
                    <div class="flex items-center gap-3">
                        <div class="icon-chip tone-blue"><x-icon name="link" /></div>
                        <div>
                            <div class="font-medium">Web address</div>
                            <div class="text-xs text-slate-500">
                                Free address: {{ $site->subdomainHost() ?? ($site->subdomain ?: 'not set') }}
                                · Own domain: {{ $site->domain ? $site->domain.' ('.($site->domain_status === 'live' ? 'working' : 'being set up').')' : 'none' }}
                            </div>
                        </div>
                    </div>
                    <a class="btn btn-sm" href="{{ route('platform.domains', ['search' => $school->name]) }}" wire:navigate>Manage address</a>
                </div>
            @endif
        </div>
    @endif

    @if ($passwordUser)
        <x-modal :title="'Change password: '.$passwordUser->name" close="$set('passwordUserId', null)">
            <form wire:submit="setPassword" class="space-y-4">
                <p class="text-sm text-slate-600">Username <span class="font-mono font-semibold">{{ $passwordUser->username }}</span> · {{ \App\Models\User::ROLES[$passwordUser->role] ?? $passwordUser->role }}</p>
                <fieldset class="grid gap-2 sm:grid-cols-2">
                    <legend class="sr-only">How to set it</legend>
                    @foreach (['type' => ['Type a password', 'pencil', 'You choose it and tell them.'], 'generate' => ['Make one for me', 'sparkles', 'A strong random password.']] as $mode => [$label, $icon, $hint])
                        <label @class(['flex cursor-pointer items-start gap-3 rounded-xl border p-3', 'border-brand-500 bg-brand-50/50 ring-1 ring-brand-500' => $passwordMode === $mode, 'border-slate-200' => $passwordMode !== $mode])>
                            <input type="radio" class="sr-only" value="{{ $mode }}" wire:model.live="passwordMode">
                            <x-icon :name="$icon" class="mt-0.5 h-5 w-5 text-brand-600" />
                            <span><span class="block text-sm font-medium">{{ $label }}</span><span class="block text-xs text-slate-500">{{ $hint }}</span></span>
                        </label>
                    @endforeach
                </fieldset>
                @if ($passwordMode === 'type')
                    <div class="grid gap-3 sm:grid-cols-2" x-data="{ show: false }">
                        <x-field label="New password" name="newPassword" hint="At least 8 characters.">
                            <input id="newPassword" class="input" :type="show ? 'text' : 'password'" wire:model="newPassword" autocomplete="new-password">
                        </x-field>
                        <x-field label="Type it again" name="newPassword_confirmation">
                            <input id="newPassword_confirmation" class="input" :type="show ? 'text' : 'password'" wire:model="newPassword_confirmation" autocomplete="new-password">
                        </x-field>
                        <label class="flex items-center gap-2 text-sm sm:col-span-2"><input type="checkbox" x-model="show"> Show password</label>
                    </div>
                @endif
                <label class="flex items-start gap-2 text-sm"><input type="checkbox" class="mt-1" wire:model="mustChange"> Ask them to choose their own password when they next sign in</label>
                <p class="rounded-lg bg-slate-50 p-2.5 text-xs text-slate-600">The old password stops working at once, and they are signed out on every device. You see the new password once, after saving.</p>
                <div class="flex justify-end gap-2">
                    <button type="button" class="btn" wire:click="$set('passwordUserId', null)">Cancel</button>
                    <button class="btn btn-primary" wire:loading.attr="disabled">Save new password</button>
                </div>
            </form>
        </x-modal>
    @endif

    @if ($showDelete)
        <x-modal :title="'Delete '.$school->name" close="$set('showDelete', false)">
            <form wire:submit="deleteSchool" class="space-y-4">
                <div class="flex items-start gap-3 rounded-xl bg-red-50 p-3 text-sm text-red-800 ring-1 ring-red-200">
                    <x-icon name="warning" class="mt-0.5 h-5 w-5 shrink-0" />
                    <div>
                        <p class="font-semibold">This cannot be undone.</p>
                        <p class="mt-1">Everything below is deleted for good, with the school's settings, attendance, results, timetable, audit log, website and uploaded pictures:</p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
                    @foreach ($deleteSummary as $what => $count)
                        <div class="rounded-lg border border-slate-200 p-2 text-center"><div class="text-lg font-bold">{{ number_format($count) }}</div><div class="text-xs text-slate-500">{{ ucfirst($what) }}</div></div>
                    @endforeach
                </div>
                <x-field label="Type the school name to confirm" name="confirmName" :hint="$school->name">
                    <input id="confirmName" class="input" wire:model="confirmName" autocomplete="off" placeholder="{{ $school->name }}">
                </x-field>
                <x-field label="Your own password" name="ownerPassword" hint="The password you sign in to the owner panel with.">
                    <input id="ownerPassword" class="input" type="password" wire:model="ownerPassword" autocomplete="current-password">
                </x-field>
                <div class="flex justify-end gap-2">
                    <button type="button" class="btn" wire:click="$set('showDelete', false)">Cancel</button>
                    <button class="btn btn-danger" wire:loading.attr="disabled">Delete everything</button>
                </div>
            </form>
        </x-modal>
    @endif
</div>
