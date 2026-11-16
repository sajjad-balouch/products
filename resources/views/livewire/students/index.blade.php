<div>
    <x-page-header title="Students" subtitle="Search by name, admission number, father's name or phone.">
        @if (auth()->user()->isRole('admin'))
            @if ($section !== '')<a class="btn" href="{{ route('cards', ['section' => $section]) }}" wire:navigate><x-icon name="id-card" class="h-4 w-4" /> ID cards</a>@endif
            <a class="btn" href="{{ route('students.import') }}" wire:navigate>Import from Excel</a>
            <a class="btn btn-primary" href="{{ route('students.form') }}" wire:navigate>Add student</a>
        @endif
    </x-page-header>

    <div class="card overflow-hidden">
        <div class="flex flex-wrap gap-2 border-b border-slate-200 p-3">
            <input class="input max-w-xs" type="search" placeholder="Search" aria-label="Search" wire:model.live.debounce.300ms="search">
            <select class="input w-auto" wire:model.live="section" aria-label="Section">
                <option value="">All classes</option>
                @foreach ($sections as $s)<option value="{{ $s->id }}">{{ $s->label }}</option>@endforeach
            </select>
            <select class="input w-auto" wire:model.live="status" aria-label="Status">
                <option value="active">Active</option>
                <option value="left">Left</option>
                <option value="graduated">Graduated</option>
                <option value="">All</option>
            </select>
        </div>
        <div class="overflow-x-auto">
            <table class="tbl">
                <thead><tr><th>Adm. no.</th><th>Name</th><th>Class</th><th>Father / guardian</th><th>Phone</th><th>Status</th></tr></thead>
                <tbody>
                @forelse ($students as $student)
                    <tr wire:key="s-{{ $student->id }}">
                        <td class="font-mono text-xs">{{ $student->admission_no }}</td>
                        <td><div class="flex items-center gap-3"><x-avatar :name="$student->name" size="sm" /><a class="font-medium text-brand-600 hover:underline" href="{{ route('students.show', $student) }}" wire:navigate>{{ $student->name }}</a></div></td>
                        <td>{{ $student->section->label }}</td>
                        <td>{{ $student->father_name ?: $student->guardian->name }}</td>
                        <td>{{ $student->guardian->phone }}</td>
                        <td><span @class(['badge', 'badge-green' => $student->status === 'active', 'badge-slate' => $student->status !== 'active'])>{{ ucfirst($student->status) }}</span></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-10 text-center text-slate-500">No students found.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="mt-3">{{ $students->links() }}</div>
</div>
