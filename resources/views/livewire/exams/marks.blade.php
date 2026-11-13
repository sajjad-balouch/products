<div class="mx-auto max-w-3xl">
    <x-page-header title="Enter marks" subtitle="Leave a box empty if the student was absent. Teachers see only their own class and subject." />
    <div class="card-pad mb-4 grid gap-3 sm:grid-cols-2">
        <x-field label="Exam" name="exam_id">
            <select id="exam_id" class="input" wire:model.live="exam_id"><option value="">Select</option>@foreach ($exams as $e)<option value="{{ $e->id }}">{{ $e->name }}</option>@endforeach</select>
        </x-field>
        <x-field label="Paper" name="paper_id">
            <select id="paper_id" class="input" wire:model.live="paper_id"><option value="">Select</option>@foreach ($papers as $p)<option value="{{ $p->id }}">{{ $p->schoolClass->label }} · {{ $p->subject->name }}</option>@endforeach</select>
        </x-field>
    </div>
    @if ($paper)
        <form wire:submit="save" class="card overflow-hidden">
            <div class="border-b border-slate-200 p-3 text-sm text-slate-600">Out of {{ $paper->total_marks }}. Pass marks {{ $paper->pass_marks }}.</div>
            <table class="tbl">
                <thead><tr><th>Student</th><th>Section</th><th class="w-32">Marks</th></tr></thead>
                <tbody>
                @forelse ($students as $student)
                    <tr wire:key="m-{{ $student->id }}">
                        <td class="font-medium">{{ $student->name }}</td>
                        <td>{{ $student->section->name }}</td>
                        <td>
                            <input class="input" type="number" step="0.5" min="0" max="{{ $paper->total_marks }}" aria-label="Marks for {{ $student->name }}" wire:model="marks.{{ $student->id }}">
                            @error('marks.'.$student->id)<p class="err">{{ $message }}</p>@enderror
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="3" class="py-6 text-center text-slate-500">No students in this class.</td></tr>
                @endforelse
                </tbody>
            </table>
            <div class="sticky bottom-0 border-t border-slate-200 bg-white p-3"><button class="btn btn-primary w-full" wire:loading.attr="disabled">Save marks</button></div>
        </form>
    @endif
</div>
