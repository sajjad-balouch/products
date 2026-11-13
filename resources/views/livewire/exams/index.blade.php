<div>
    <x-page-header title="Exams and papers" subtitle="Parents see a result only after you publish the exam." />
    <div class="grid gap-4 lg:grid-cols-3">
        <div class="space-y-4">
            <form wire:submit="createExam" class="card-pad space-y-3">
                <h2 class="font-semibold">New exam</h2>
                <x-field label="Name" name="name"><input id="name" class="input" wire:model="name" placeholder="First Term 2026"></x-field>
                <x-field label="Starts on" name="starts_on"><input id="starts_on" class="input" type="date" wire:model="starts_on"></x-field>
                <button class="btn btn-primary w-full">Create exam</button>
            </form>
            <div class="card overflow-hidden">
                <ul class="divide-y divide-slate-100">
                    @forelse ($exams as $exam)
                        <li wire:key="x-{{ $exam->id }}" @class(['flex items-center justify-between gap-2 p-3 text-sm', 'bg-brand-50' => $openId === $exam->id])>
                            <button class="min-w-0 text-start" wire:click="$set('openId', {{ $exam->id }})">
                                <div class="truncate font-medium">{{ $exam->name }}</div>
                                <div class="text-xs text-slate-500">{{ $exam->papers_count }} papers</div>
                            </button>
                            <button class="btn btn-sm" wire:click="togglePublish({{ $exam->id }})">{{ $exam->is_published ? 'Published' : 'Publish' }}</button>
                        </li>
                    @empty
                        <li class="p-4 text-sm text-slate-500">No exams yet.</li>
                    @endforelse
                </ul>
            </div>
        </div>
        <div class="lg:col-span-2">
            @if ($current)
                <div class="card-pad">
                    <h2 class="mb-3 font-semibold">{{ $current->name }}: add papers</h2>
                    <form wire:submit="addClassPapers" class="grid gap-3 sm:grid-cols-4">
                        <x-field label="Class" name="class_id" class="sm:col-span-2">
                            <select id="class_id" class="input" wire:model="class_id"><option value="">Select</option>@foreach ($classes as $c)<option value="{{ $c->id }}">{{ $c->label }}</option>@endforeach</select>
                        </x-field>
                        <x-field label="Total" name="total_marks"><input id="total_marks" class="input" type="number" wire:model="total_marks"></x-field>
                        <x-field label="Pass" name="pass_marks"><input id="pass_marks" class="input" type="number" wire:model="pass_marks"></x-field>
                        <button class="btn btn-primary sm:col-span-4">Add all subjects of this class</button>
                    </form>
                </div>
                <div class="card mt-4 overflow-x-auto">
                    <table class="tbl">
                        <thead><tr><th>Class</th><th>Subject</th><th>Total</th><th>Pass</th><th></th></tr></thead>
                        <tbody>
                        @forelse ($current->papers->sortBy('school_class_id') as $paper)
                            <tr wire:key="pp-{{ $paper->id }}"><td>{{ $paper->schoolClass->label }}</td><td>{{ $paper->subject->name }}</td><td>{{ $paper->total_marks }}</td><td>{{ $paper->pass_marks }}</td>
                                <td class="text-end"><button class="btn btn-sm btn-danger" wire:click="removePaper({{ $paper->id }})">Remove</button></td></tr>
                        @empty
                            <tr><td colspan="5" class="py-6 text-center text-slate-500">No papers yet.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            @else
                <div class="card-pad text-sm text-slate-500">Choose an exam to set up its papers.</div>
            @endif
        </div>
    </div>
</div>
