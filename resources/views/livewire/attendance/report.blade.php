<div>
    <x-page-header title="Attendance report" subtitle="Monthly register for one class." />
    <div class="card-pad mb-4 flex flex-wrap gap-3">
        <select class="input w-auto" aria-label="Class" wire:model.live="section_id">
            @foreach ($sections as $s)<option value="{{ $s->id }}">{{ $s->label }}</option>@endforeach
        </select>
        <input class="input w-auto" type="month" aria-label="Month" wire:model.live="month">
    </div>
    <div class="card overflow-x-auto">
        <table class="w-full text-xs">
            <thead><tr class="bg-slate-50">
                <th class="sticky start-0 bg-slate-50 px-3 py-2 text-start">Student</th>
                @for ($d = 1; $d <= $days; $d++)<th @class(['px-1 py-2 text-center font-medium', 'text-slate-400' => $monthDate->copy()->day($d)->isSunday()])>{{ $d }}</th>@endfor
                <th class="px-3 py-2 text-end">%</th>
            </tr></thead>
            <tbody>
            @forelse ($students as $student)
                @php($row = $records->get($student->id, collect()))
                @php($attended = $row->filter(fn ($s) => in_array($s, ['P', 'L']))->count())
                <tr class="border-t border-slate-100">
                    <td class="sticky start-0 bg-white px-3 py-1.5 font-medium whitespace-nowrap">{{ $student->name }}</td>
                    @for ($d = 1; $d <= $days; $d++)
                        @php($s = $row->get($d))
                        <td @class(['px-1 py-1.5 text-center', 'font-semibold text-red-700' => $s === 'A', 'text-amber-600' => $s === 'L', 'text-sky-700' => $s === 'V', 'text-emerald-700' => $s === 'P'])>{{ $s === 'P' ? '•' : $s }}</td>
                    @endfor
                    <td class="px-3 py-1.5 text-end font-medium">{{ $row->count() ? round($attended / $row->count() * 100) : '' }}</td>
                </tr>
            @empty
                <tr><td colspan="{{ $days + 2 }}" class="py-8 text-center text-slate-500">No students to show.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <p class="mt-2 text-xs text-slate-500">• present · A absent · L late · V leave</p>
</div>
