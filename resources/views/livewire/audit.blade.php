<div>
    <x-page-header title="Audit log" subtitle="Who changed what, and when, across fees, payments, marks and students. Entries cannot be edited or deleted in the app." />
    <div class="card overflow-hidden">
        <div class="flex flex-wrap gap-2 border-b border-slate-200 p-3">
            <input class="input max-w-xs" type="search" placeholder="Person" aria-label="Person" wire:model.live.debounce.300ms="person">
            <select class="input w-auto" aria-label="Record type" wire:model.live="type">
                <option value="">All records</option>
                @foreach (['Payment', 'FeeVoucher', 'PaymentProof', 'Mark', 'Student', 'User', 'School'] as $t)<option value="{{ $t }}">{{ $t }}</option>@endforeach
            </select>
        </div>
        <div class="overflow-x-auto"><table class="tbl">
            <thead><tr><th>When</th><th>Who</th><th>Action</th><th>Record</th><th>Details</th></tr></thead>
            <tbody>
            @forelse ($logs as $log)
                <tr wire:key="a-{{ $log->id }}">
                    <td class="whitespace-nowrap">{{ $log->created_at->format('j M, g:i a') }}</td>
                    <td>{{ $log->user_name }}</td>
                    <td>{{ str_replace('_', ' ', $log->action) }}</td>
                    <td class="whitespace-nowrap">{{ $log->subject_type }} {{ $log->subject_id ? '#'.$log->subject_id : '' }}</td>
                    <td class="max-w-md text-xs">
                        {{ $log->summary }}
                        @if ($log->action === 'updated' && $log->changes)
                            @foreach ($log->changes['new'] ?? [] as $field => $value)
                                <div><span class="font-medium">{{ $field }}</span>: {{ is_scalar($log->changes['old'][$field] ?? null) ? $log->changes['old'][$field] : '-' }} → {{ is_scalar($value) ? $value : '-' }}</div>
                            @endforeach
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="py-10 text-center text-slate-500">No entries.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </div>
    <div class="mt-3">{{ $logs->links() }}</div>
</div>
