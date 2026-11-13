<div class="mx-auto max-w-4xl">
    <x-page-header title="Import students from Excel" subtitle="In Excel, choose Save As, then CSV UTF-8. Check the preview before anything is saved.">
        <a class="btn" href="{{ route('students.import.template') }}">Download template</a>
    </x-page-header>

    @if ($result)
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-900">
            Import finished: {{ $result['created'] }} students added, {{ $result['updated'] }} updated.
            <a class="underline" href="{{ route('students.index') }}" wire:navigate>View students</a>
        </div>
    @endif

    <div class="card-pad">
        <x-field label="CSV file" name="file" hint="Required columns: name, phone, class. Optional: admission_no, father_name, section, gender, dob, roll_no, address, monthly_concession. A row with an existing admission number updates that student.">
            <input id="file" class="input" type="file" accept=".csv,text/csv" wire:model="file">
        </x-field>
        <div wire:loading wire:target="file" class="mt-2 text-sm text-slate-500">Reading file...</div>
    </div>

    @if ($problems)
        <div class="mt-4 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-900">
            <p class="font-semibold">Fix these rows in your file and upload it again. Nothing has been saved.</p>
            <ul class="mt-2 list-inside list-disc">@foreach (array_slice($problems, 0, 30, true) as $line => $problem)<li>Row {{ $line }}: {{ $problem }}</li>@endforeach</ul>
            @if (count($problems) > 30)<p class="mt-2">And {{ count($problems) - 30 }} more.</p>@endif
        </div>
    @elseif ($rows)
        <div class="card mt-4 overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-200 p-3">
                <span class="text-sm font-medium">{{ count($rows) }} rows are ready. First 10 shown.</span>
                <button class="btn btn-primary" wire:click="import" wire:loading.attr="disabled">Import {{ count($rows) }} students</button>
            </div>
            <div class="overflow-x-auto"><table class="tbl">
                <thead><tr><th>Adm. no.</th><th>Name</th><th>Father</th><th>Phone</th><th>Class</th><th>Section</th></tr></thead>
                <tbody>
                @foreach (array_slice($rows, 0, 10) as $row)
                    <tr><td>{{ $row['admission_no'] ?: 'Auto' }}</td><td>{{ $row['name'] }}</td><td>{{ $row['father_name'] }}</td><td>{{ $row['phone'] }}</td><td>{{ $row['class'] }}</td><td>{{ $row['section'] ?: 'A' }}</td></tr>
                @endforeach
                </tbody>
            </table></div>
        </div>
    @endif
</div>
