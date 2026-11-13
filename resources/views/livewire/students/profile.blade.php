<div>
    <x-page-header :title="$student->name" :subtitle="$student->admission_no.' · '.$student->section->label">
        @if (auth()->user()->isRole('admin'))
            <button class="btn" wire:click="createParentLogin" wire:confirm="Issue a new password for this parent? Any old password will stop working.">{{ $student->guardian->user_id ? 'Reset parent login' : 'Create parent login' }}</button>
            <a class="btn btn-primary" href="{{ route('students.form', $student) }}" wire:navigate>Edit</a>
        @endif
    </x-page-header>

    @if ($newPassword)
        <div class="mb-4 rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
            <p class="font-semibold">This password is shown only once.</p>
            <p class="mt-1">Username <span class="font-mono">{{ $student->guardian->fresh()->user?->username }}</span>, password <span class="font-mono">{{ $newPassword }}</span>. The parent must change it at first sign-in.</p>
            <a class="btn btn-sm mt-2" href="{{ $whatsappPassword }}" target="_blank" rel="noopener">Send on WhatsApp</a>
        </div>
    @endif

    @if ($flag)
        <div @class(['mb-4 rounded-xl border p-4 text-sm', 'border-red-200 bg-red-50 text-red-900' => $flag['level'] === 'high', 'border-amber-200 bg-amber-50 text-amber-900' => $flag['level'] === 'watch'])>
            <p class="font-semibold">{{ $flag['level'] === 'high' ? 'Needs attention now' : 'Keep an eye on this student' }}</p>
            <ul class="mt-1 list-inside list-disc">@foreach ($flag['reasons'] as $reason)<li>{{ $reason }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="grid gap-4 lg:grid-cols-3">
        <div class="space-y-4">
            <div class="card-pad text-sm">
                <h2 class="mb-2 font-semibold">Details</h2>
                <dl class="space-y-1.5">
                    @foreach (['Father' => $student->father_name, 'Guardian' => $student->guardian->name, 'Phone' => $student->guardian->phone, 'Address' => $student->guardian->address, 'Gender' => ucfirst($student->gender), 'Date of birth' => $student->dob?->format('j M Y'), 'B-Form' => $student->b_form, 'Roll no.' => $student->roll_no, 'Admitted' => $student->admitted_on?->format('j M Y'), 'Transport' => $student->transportRoute?->name, 'Status' => ucfirst($student->status)] as $label => $value)
                        @if ($value)<div class="flex justify-between gap-3"><dt class="text-slate-500">{{ $label }}</dt><dd class="text-end text-slate-800">{{ $value }}</dd></div>@endif
                    @endforeach
                    @if ($student->monthly_concession)<div class="flex justify-between gap-3"><dt class="text-slate-500">Concession</dt><dd>Rs {{ number_format($student->monthly_concession) }} {{ $student->concession_reason ? '('.$student->concession_reason.')' : '' }}</dd></div>@endif
                </dl>
            </div>
            @if ($student->guardian->students->count() > 1)
                <div class="card-pad text-sm">
                    <h2 class="mb-2 font-semibold">Siblings</h2>
                    @foreach ($student->guardian->students->where('id', '!=', $student->id) as $sibling)
                        <a class="block text-brand-600 hover:underline" href="{{ route('students.show', $sibling) }}" wire:navigate>{{ $sibling->name }}</a>
                    @endforeach
                </div>
            @endif
            <div class="card-pad text-sm">
                <h2 class="mb-2 font-semibold">Attendance, last 30 days</h2>
                @if ($attendancePercent === null)
                    <p class="text-slate-500">Not marked yet.</p>
                @else
                    <div class="text-2xl font-semibold">{{ $attendancePercent }}%</div>
                    <p class="text-slate-500">Present {{ $attendance['P'] ?? 0 }}, late {{ $attendance['L'] ?? 0 }}, absent {{ $attendance['A'] ?? 0 }}, leave {{ $attendance['V'] ?? 0 }}</p>
                @endif
            </div>
        </div>

        <div class="space-y-4 lg:col-span-2">
            <div class="card overflow-hidden">
                <h2 class="border-b border-slate-200 px-4 py-3 font-semibold">Family fee history</h2>
                <div class="overflow-x-auto"><table class="tbl">
                    <thead><tr><th>Month</th><th>Voucher</th><th>Total</th><th>Paid</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                    @forelse ($vouchers as $v)
                        <tr wire:key="v-{{ $v->id }}">
                            <td>{{ $v->month->format('M Y') }}</td>
                            <td class="font-mono text-xs">{{ $v->voucher_no }}</td>
                            <td>Rs {{ number_format($v->total) }}</td>
                            <td>Rs {{ number_format($v->paid) }}</td>
                            <td>@include('partials.voucher-status', ['status' => $v->status])</td>
                            <td class="text-end"><a class="btn btn-sm" href="{{ route('print.voucher', $v->id) }}" target="_blank">Print</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-6 text-center text-slate-500">No vouchers yet.</td></tr>
                    @endforelse
                    </tbody>
                </table></div>
            </div>
            <div class="card overflow-hidden">
                <h2 class="border-b border-slate-200 px-4 py-3 font-semibold">Exams</h2>
                <div class="overflow-x-auto"><table class="tbl">
                    <thead><tr><th>Exam</th><th>Marks</th><th>Percent</th><th>Grade</th><th>Position</th><th></th></tr></thead>
                    <tbody>
                    @forelse ($exams as $item)
                        <tr wire:key="e-{{ $item['exam']->id }}">
                            <td>{{ $item['exam']->name }} @unless ($item['exam']->is_published)<span class="badge badge-slate">Draft</span>@endunless</td>
                            <td>{{ (float) $item['row']['obtained'] }} / {{ $item['row']['total'] }}</td>
                            <td>{{ $item['row']['percent'] }}%</td>
                            <td>{{ $item['row']['grade'] }}</td>
                            <td>{{ $item['row']['position'] }}</td>
                            <td class="text-end"><a class="btn btn-sm" href="{{ route('print.report-card', [$item['exam']->id, $student->id]) }}" target="_blank">Report card</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-6 text-center text-slate-500">No exam results yet.</td></tr>
                    @endforelse
                    </tbody>
                </table></div>
            </div>
        </div>
    </div>
</div>
