<div>
    <x-page-header title="Certificates" subtitle="Each certificate has a QR code. Anyone can scan it to confirm it is genuine, which makes forged certificates easy to catch." />
    <form wire:submit="issue" class="card-pad mb-4 grid gap-3 sm:grid-cols-3">
        <x-field label="Admission number" name="admission_no"><input id="admission_no" class="input" wire:model="admission_no"></x-field>
        <x-field label="Type" name="type"><select id="type" class="input" wire:model="type">@foreach ($types as $k => $v)<option value="{{ $k }}">{{ $v }}</option>@endforeach</select></x-field>
        <x-field label="Remarks" name="remarks"><input id="remarks" class="input" wire:model="remarks"></x-field>
        <button class="btn btn-primary sm:col-span-3">Issue certificate</button>
    </form>
    <div class="card overflow-x-auto">
        <table class="tbl">
            <thead><tr><th>Serial</th><th>Student</th><th>Type</th><th>Issued</th><th></th></tr></thead>
            <tbody>
            @forelse ($certificates as $c)
                <tr><td class="font-mono text-xs">{{ $c->serial }}</td><td>{{ $c->student->name }}</td><td>{{ $types[$c->type] }}</td><td>{{ $c->issued_on->format('j M Y') }}</td>
                    <td class="text-end"><a class="btn btn-sm" href="{{ route('print.certificate', $c->id) }}" target="_blank">Print</a></td></tr>
            @empty
                <tr><td colspan="5" class="py-10 text-center text-slate-500">No certificates yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
