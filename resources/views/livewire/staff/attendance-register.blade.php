@php($tones = ['P' => 'text-emerald-700', 'L' => 'bg-amber-100 text-amber-800', 'H' => 'bg-violet-100 text-violet-800', 'A' => 'bg-red-100 text-red-800', 'V' => 'bg-sky-100 text-sky-800'])
<div>
    <style>@media print { @page { size: A4 landscape; margin: 8mm; } }</style>
    <div class="mb-3 hidden print:block">
        <div class="text-xl font-bold">{{ $school->name }}</div>
        <div class="text-sm">Staff attendance register, {{ $monthDate->format('F Y') }}{{ $category ? ' · '.explode(' (', \App\Models\Staff::CATEGORIES[$category])[0] : '' }}</div>
    </div>

    <x-page-header title="Staff attendance register" subtitle="Every staff member for the whole month. P present, L late, ½ half day, A absent, Lv leave.">
        <a class="btn" href="{{ route('staff.attendance') }}" wire:navigate>Mark a day</a>
        <button class="btn" onclick="window.print()">Print</button>
    </x-page-header>

    <div class="card-pad mb-4 flex flex-wrap gap-2 no-print">
        <select class="input w-auto" aria-label="Month" wire:model.live="month">@foreach ($months as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select>
        <select class="input w-auto" aria-label="Group" wire:model.live="category">
            <option value="">All staff</option>
            @foreach (\App\Models\Staff::CATEGORIES as $key => $label)<option value="{{ $key }}">{{ explode(' (', $label)[0] }}</option>@endforeach
        </select>
        <p class="w-full text-xs text-slate-500">
            Days off pay = absent days + half of the half days{{ $school->lates_per_absent ? ' + one day for every '.$school->lates_per_absent.' lates' : '' }}.
            Payroll uses the same count.
        </p>
    </div>

    <div class="card overflow-x-auto print:overflow-visible print:border-0 print:shadow-none">
        <table class="w-full border-collapse text-xs">
            <thead>
                <tr class="bg-slate-50">
                    <th class="sticky start-0 z-10 bg-slate-50 px-2 py-2 text-start">Staff</th>
                    @foreach ($days as $day)
                        <th @class(['w-6 px-0.5 py-2 text-center font-medium', 'text-red-600' => $day->isSunday()])>{{ $day->day }}<div class="text-[9px] font-normal text-slate-400">{{ $day->format('D')[0] }}</div></th>
                    @endforeach
                    @foreach (['P', 'L', 'H', 'A', 'V'] as $code)<th class="px-1.5 py-2 text-center">{{ $code === 'H' ? '½' : ($code === 'V' ? 'Lv' : $code) }}</th>@endforeach
                    <th class="px-2 py-2 text-center" title="Days off pay">Off pay</th>
                </tr>
            </thead>
            <tbody>
            @forelse ($staff as $person)
                @php($t = $totals[$person->id])
                <tr class="border-t border-slate-100" wire:key="reg-{{ $person->id }}">
                    <td class="sticky start-0 z-10 bg-white px-2 py-1.5 whitespace-nowrap"><div class="font-medium">{{ $person->name }}</div><div class="text-[10px] text-slate-500">{{ $person->designation }}</div></td>
                    @foreach ($days as $day)
                        @php($mark = $marks->get($person->id.'-'.$day->day))
                        <td @class(['px-0.5 py-1.5 text-center font-semibold', $tones[$mark->status ?? ''] ?? '', 'bg-slate-50' => ! $mark && $day->isSunday()]) title="{{ $mark ? \App\Services\StaffAttendanceService::STATUSES[$mark->status].($mark->check_in ? ', in '.$mark->check_in : '').($mark->check_out ? ', out '.$mark->check_out : '') : '' }}">
                            {{ $mark ? ($mark->status === 'H' ? '½' : ($mark->status === 'V' ? 'Lv' : $mark->status)) : '' }}
                        </td>
                    @endforeach
                    @foreach (['P', 'L', 'H', 'A', 'V'] as $code)<td class="px-1.5 text-center">{{ $t[$code] ?: '' }}</td>@endforeach
                    <td @class(['px-2 text-center font-semibold', 'text-red-700' => $t['deducted'] > 0])>{{ $t['deducted'] + 0 }}</td>
                </tr>
            @empty
                <tr><td colspan="{{ $days->count() + 7 }}" class="p-6 text-center text-slate-500">No staff in this group.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
