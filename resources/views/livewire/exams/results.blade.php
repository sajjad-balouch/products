<div>
    <x-page-header title="Results" subtitle="Class result, positions and which subjects need help." />
    <div class="card-pad mb-4 flex flex-wrap gap-3">
        <select class="input w-auto" aria-label="Exam" wire:model.live="exam_id"><option value="">Select exam</option>@foreach ($exams as $e)<option value="{{ $e->id }}">{{ $e->name }}</option>@endforeach</select>
        <select class="input w-auto" aria-label="Class" wire:model.live="class_id"><option value="">Select class</option>@foreach ($classes as $c)<option value="{{ $c->id }}">{{ $c->label }}</option>@endforeach</select>
    </div>

    @if ($rows->isNotEmpty())
        <div class="mb-4 grid gap-3 sm:grid-cols-3">
            <x-stat label="Students" :value="$rows->count()" />
            <x-stat label="Passed" :value="$passCount" :note="round($passCount / $rows->count() * 100).'% pass rate'" tone="green" />
            <x-stat label="Failed" :value="$rows->count() - $passCount" tone="red" />
        </div>
        <div class="card mb-4 overflow-x-auto">
            <table class="tbl">
                <thead><tr><th>Subject</th><th>Class average</th><th>Failed</th></tr></thead>
                <tbody>
                @foreach ($subjectStats as $stat)
                    <tr><td>{{ $stat['subject'] }}</td>
                        <td><div class="flex items-center gap-2"><div class="h-2 w-32 overflow-hidden rounded-full bg-slate-100"><div @class(['h-full', 'bg-red-500' => ($stat['average'] ?? 0) < 50, 'bg-brand-500' => ($stat['average'] ?? 0) >= 50]) style="width: {{ $stat['average'] ?? 0 }}%"></div></div>{{ $stat['average'] ?? '-' }}%</div></td>
                        <td>{{ $stat['failed'] }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div class="card overflow-x-auto">
            <table class="tbl">
                <thead><tr><th>Pos.</th><th>Student</th>@foreach ($papers as $p)<th>{{ $p->subject->name }}</th>@endforeach<th>Total</th><th>%</th><th>Grade</th><th></th></tr></thead>
                <tbody>
                @foreach ($rows as $row)
                    <tr wire:key="r-{{ $row['student']->id }}">
                        <td class="font-semibold">{{ $row['position'] }}</td>
                        <td class="font-medium whitespace-nowrap">{{ $row['student']->name }}</td>
                        @foreach ($papers as $p)
                            @php($m = $row['papers'][$p->id])
                            <td @class(['font-medium text-red-700' => $m['failed']])>{{ $m['obtained'] === null ? 'Abs' : (float) $m['obtained'] }}</td>
                        @endforeach
                        <td>{{ (float) $row['obtained'] }}/{{ $row['total'] }}</td>
                        <td>{{ $row['percent'] }}</td>
                        <td><span @class(['badge', 'badge-green' => $row['passed'], 'badge-red' => ! $row['passed']])>{{ $row['grade'] }}</span></td>
                        <td class="text-end"><a class="btn btn-sm" href="{{ route('print.report-card', [$exam->id, $row['student']->id]) }}" target="_blank">Report card</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @elseif ($exam_id && $class_id)
        <div class="card-pad text-sm text-slate-500">No students in this class.</div>
    @endif
</div>
