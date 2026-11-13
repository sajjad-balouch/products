<div>
    <x-dash-hero :greeting="$greeting" :subtitle="auth()->user()->school->name.' · '.now()->format('F').' at a glance'">
        <div class="flex flex-wrap gap-2">
            <div class="rounded-xl bg-white/10 px-4 py-2.5 ring-1 ring-white/15">
                <div class="text-xs text-white/70">Attendance today</div>
                <div class="text-lg font-bold">{{ $attendancePercent === null ? 'Not marked' : $attendancePercent.'%' }}</div>
            </div>
            <div class="rounded-xl bg-white/10 px-4 py-2.5 ring-1 ring-white/15">
                <div class="text-xs text-white/70">Received today</div>
                <div class="text-lg font-bold">Rs {{ number_format($todayCash) }}</div>
            </div>
        </div>
    </x-dash-hero>

    <livewire:staff.check-in />

    @if ($needsSetup && $isAdmin)
        <div class="card-pad mb-5 border-brand-500">
            <h2 class="panel-title"><x-icon name="sparkles" class="h-5 w-5 text-accent-500" /> Set up your school in four steps</h2>
            <div class="mt-3 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ([['students.import', 'Import students from Excel', 'upload'], ['fees.structure', 'Set class fees', 'layers'], ['settings', 'Add payment details', 'settings'], ['fees.generate', 'Generate first vouchers', 'file-plus']] as $i => [$route, $label, $icon])
                    <a class="action-tile" href="{{ route($route) }}" wire:navigate>
                        <div class="icon-chip tone-brand font-bold">{{ $i + 1 }}</div>
                        <div class="text-sm font-medium">{{ $label }}</div>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <x-quick-actions :actions="$actions" />

    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat icon="users" tone="blue" label="Active students" :value="number_format($students)" :href="route('students.index')" />
        <x-stat icon="percent" label="Fee recovery this month" :value="$recovery.'%'" :note="'Rs '.number_format($collected).' of Rs '.number_format($billed)" :tone="$recovery >= 80 ? 'green' : ($recovery >= 50 ? 'amber' : 'red')" :href="route('fees.vouchers')" />
        <x-stat icon="alert-circle" label="Overdue fees" :value="'Rs '.number_format($overdue)" tone="red" :href="route('fees.defaulters')" />
        <x-stat icon="cash" tone="green" label="Received today" :value="'Rs '.number_format($todayCash)" :href="route('fees.closing')" />
        <x-stat icon="check-square" tone="brand" label="Attendance today" :value="$attendancePercent === null ? 'Not marked' : $attendancePercent.'%'" :note="$absentToday.' absent'" />
        <x-stat icon="image" label="Payment proofs to review" :value="$pendingProofs" :tone="$pendingProofs ? 'amber' : 'violet'" :href="route('fees.proofs')" />
        <x-stat icon="message" label="WhatsApp messages to send" :value="$queuedMessages" :tone="$queuedMessages ? 'amber' : 'green'" :href="route('messages.index')" />
        <x-stat icon="receipt" tone="violet" label="Expenses this month" :value="'Rs '.number_format($expenses)" :href="route('finance.expenses')" />
    </div>

    <div class="mt-5 grid gap-4 lg:grid-cols-3">
        <div class="card-pad lg:col-span-2">
            @php($max = max(1, $trend->max('amount')))
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <h2 class="panel-title"><x-icon name="trend" class="h-5 w-5 text-brand-600" /> Fee received, last 6 months</h2>
                <span class="text-sm text-slate-500">Total Rs {{ number_format($trend->sum('amount')) }}</span>
            </div>
            <div class="relative mt-5 h-52" role="img" aria-label="Bar chart of fee received per month: {{ $trend->map(fn ($b) => $b['label'].' Rs '.number_format($b['amount']))->implode(', ') }}">
                <div class="absolute inset-x-0 top-0 bottom-6 flex flex-col justify-between" aria-hidden="true">
                    @foreach (range(1, 4) as $line)<div class="border-t border-dashed border-slate-200"></div>@endforeach
                </div>
                <div class="relative flex h-full items-end gap-3 sm:gap-5">
                    @foreach ($trend as $bar)
                        <div class="group flex h-full flex-1 flex-col items-center justify-end gap-1.5" title="{{ $bar['label'] }}: Rs {{ number_format($bar['amount']) }}">
                            <div class="text-[11px] font-semibold text-slate-600">{{ $bar['amount'] >= 1000 ? round($bar['amount'] / 1000).'k' : $bar['amount'] }}</div>
                            <div @class(['w-full max-w-16 rounded-t-lg transition group-hover:opacity-80', 'bg-gradient-to-t from-brand-600 to-brand-500' => ! $loop->last, 'bg-gradient-to-t from-accent-500 to-amber-400' => $loop->last]) style="height: {{ max(2, round($bar['amount'] / $max * 78)) }}%"></div>
                            <div @class(['text-xs', 'font-semibold text-slate-900' => $loop->last, 'text-slate-500' => ! $loop->last])>{{ $bar['label'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="card-pad">
            <div class="flex items-baseline justify-between">
                <h2 class="panel-title"><x-icon name="warning" class="h-5 w-5 text-red-600" /> Needs attention</h2>
                @if ($isAdmin)<a class="text-xs font-medium text-brand-600 hover:underline" href="{{ route('risk') }}" wire:navigate>{{ $riskCount }} flagged</a>@endif
            </div>
            <ul class="mt-3 space-y-2.5">
                @forelse ($highRisk as $flag)
                    <li>
                        <a class="flex items-center gap-3 rounded-lg p-1.5 hover:bg-slate-50" href="{{ route('students.show', $flag['student']) }}" wire:navigate>
                            <div class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-red-50 text-xs font-bold text-red-700">{{ collect(explode(' ', $flag['student']->name))->take(2)->map(fn ($w) => mb_substr($w, 0, 1))->implode('') }}</div>
                            <div class="min-w-0">
                                <div class="truncate text-sm font-medium text-slate-900">{{ $flag['student']->name }}</div>
                                <div class="truncate text-xs text-slate-500">{{ $flag['reasons'][0] ?? '' }}</div>
                            </div>
                        </a>
                    </li>
                @empty
                    <li class="flex items-center gap-2 text-sm text-slate-500"><x-icon name="check-circle" class="h-5 w-5 text-emerald-600" /> No students at high risk.</li>
                @endforelse
            </ul>
        </div>

        <div class="card-pad">
            <div class="flex items-baseline justify-between">
                <h2 class="panel-title"><x-icon name="clock" class="h-5 w-5 text-amber-600" /> Attendance not marked</h2>
                @if ($unmarked->isNotEmpty())<a class="text-xs font-medium text-brand-600 hover:underline" href="{{ route('attendance.mark') }}" wire:navigate>Mark now</a>@endif
            </div>
            @if ($unmarked->isEmpty())
                <p class="mt-3 flex items-center gap-2 text-sm text-slate-500"><x-icon name="check-circle" class="h-5 w-5 text-emerald-600" /> All classes are marked.</p>
            @else
                <div class="mt-3 flex flex-wrap gap-1.5">
                    @foreach ($unmarked->take(12) as $section)<span class="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-800 ring-1 ring-amber-200">{{ $section->label }}</span>@endforeach
                    @if ($unmarked->count() > 12)<span class="px-1 py-1 text-xs text-slate-500">and {{ $unmarked->count() - 12 }} more</span>@endif
                </div>
            @endif
        </div>

        @if ($isAdmin)
            <div class="card-pad lg:col-span-2">
                <div class="flex items-baseline justify-between">
                    <h2 class="panel-title"><x-icon name="user-plus" class="h-5 w-5 text-sky-600" /> Admission follow-ups due</h2>
                    <a class="text-xs font-medium text-brand-600 hover:underline" href="{{ route('admissions') }}" wire:navigate>{{ $newInquiries }} new</a>
                </div>
                <ul class="mt-3 divide-y divide-slate-100">
                    @forelse ($followUps as $inquiry)
                        <li class="flex items-center justify-between gap-3 py-2">
                            <div class="min-w-0">
                                <div class="truncate text-sm font-medium">{{ $inquiry->student_name }}</div>
                                <div class="text-xs text-slate-500">Follow up {{ $inquiry->follow_up_on->format('j M') }}</div>
                            </div>
                            <a class="btn btn-sm" href="tel:{{ $inquiry->phone }}"><x-icon name="phone" class="h-3.5 w-3.5" /> {{ $inquiry->phone }}</a>
                        </li>
                    @empty
                        <li class="flex items-center gap-2 py-2 text-sm text-slate-500"><x-icon name="check-circle" class="h-5 w-5 text-emerald-600" /> No follow-ups due.</li>
                    @endforelse
                </ul>
            </div>
        @endif
    </div>
</div>
