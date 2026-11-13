<div>
    <x-page-header title="Early warning" subtitle="Students who may drop out or fail, found from attendance, marks and fee history. Each flag shows its reasons so you can check them. It is a prompt to talk to the family, not a verdict." />
    <div class="grid gap-3 md:grid-cols-2">
        @forelse ($rows as $row)
            <a href="{{ route('students.show', $row['student']) }}" wire:navigate wire:key="r-{{ $row['student']->id }}" class="card-pad block hover:border-brand-500">
                <div class="flex items-start justify-between gap-2">
                    <div><div class="font-medium">{{ $row['student']->name }}</div><div class="text-xs text-slate-500">{{ $row['student']->section->label }}</div></div>
                    <span @class(['badge', 'badge-red' => $row['level'] === 'high', 'badge-amber' => $row['level'] === 'watch'])>{{ $row['level'] === 'high' ? 'High' : 'Watch' }} · {{ $row['score'] }}</span>
                </div>
                <ul class="mt-2 list-inside list-disc text-sm text-slate-600">@foreach ($row['reasons'] as $reason)<li>{{ $reason }}</li>@endforeach</ul>
            </a>
        @empty
            <div class="card-pad text-sm text-slate-500 md:col-span-2">No students are flagged. Flags appear once attendance, published exams and fee records exist.</div>
        @endforelse
    </div>
</div>
