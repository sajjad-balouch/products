<div class="mx-auto max-w-4xl">
    @php($now = now()->format('H:i'))
    <x-dash-hero :greeting="$greeting" :subtitle="$today->isEmpty() ? 'No periods for you today.' : 'You have '.$today->count().' '.str('period')->plural($today->count()).' today.'">
        <div class="rounded-xl bg-white/10 px-4 py-2.5 ring-1 ring-white/15">
            <div class="text-xs text-white/70">Classes marked</div>
            <div class="text-lg font-bold">{{ count($marked) }} of {{ $sections->count() }}</div>
        </div>
    </x-dash-hero>

    <livewire:staff.check-in />

    <x-quick-actions :actions="$actions" />

    <div class="grid gap-4 md:grid-cols-2">
        <div class="card-pad">
            <h2 class="panel-title"><x-icon name="check-square" class="h-5 w-5 text-brand-600" /> My classes</h2>
            <ul class="mt-3 space-y-2">
                @forelse ($sections as $section)
                    <li class="flex items-center justify-between gap-2 rounded-xl border border-slate-100 p-2.5">
                        <div class="flex items-center gap-3">
                            <div @class(['icon-chip h-9! w-9!', 'tone-green' => in_array($section->id, $marked), 'tone-amber' => ! in_array($section->id, $marked)])><x-icon :name="in_array($section->id, $marked) ? 'check-circle' : 'clock'" class="h-[18px] w-[18px]" /></div>
                            <span class="text-sm font-medium">{{ $section->label }}</span>
                        </div>
                        @if (in_array($section->id, $marked))
                            <span class="badge badge-green">Saved</span>
                        @else
                            <a class="btn btn-sm btn-primary" href="{{ route('attendance.mark') }}" wire:navigate>Mark now</a>
                        @endif
                    </li>
                @empty
                    <li class="text-sm text-slate-500">No class is assigned to you yet. Ask the office to link your staff record.</li>
                @endforelse
            </ul>
        </div>

        <div class="card-pad">
            <h2 class="panel-title"><x-icon name="clock" class="h-5 w-5 text-violet-600" /> My periods today</h2>
            <ol class="relative mt-4 space-y-3 border-s-2 border-slate-100 ps-5">
                @forelse ($today as $entry)
                    @php($current = $entry->period->starts_at <= $now && $now < $entry->period->ends_at)
                    @php($done = $entry->period->ends_at <= $now)
                    <li class="relative">
                        <span @class(['absolute -start-[27px] top-1.5 h-3 w-3 rounded-full ring-4 ring-white', 'bg-accent-500' => $current, 'bg-slate-300' => $done, 'bg-brand-500' => ! $current && ! $done])></span>
                        <div @class(['rounded-xl p-2.5', 'bg-amber-50 ring-1 ring-amber-200' => $current, 'opacity-60' => $done])>
                            <div class="flex items-center justify-between gap-2 text-xs text-slate-500"><span dir="ltr">{{ $entry->period->starts_at }} to {{ $entry->period->ends_at }}</span>@if ($current)<span class="badge badge-amber">Now</span>@endif</div>
                            <div class="text-sm font-semibold">{{ $entry->subject->name }}</div>
                            <div class="text-xs text-slate-600">{{ $entry->section->label }}</div>
                        </div>
                    </li>
                @empty
                    <li class="text-sm text-slate-500">No periods today.</li>
                @endforelse
            </ol>
        </div>
    </div>
</div>
