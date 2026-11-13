<form wire:submit="save">
    <x-page-header title="Fee structure" subtitle="Amount per student, in rupees. Leave a box empty if that head does not apply to the class.">
        <button class="btn btn-primary" wire:loading.attr="disabled">Save</button>
    </x-page-header>
    @if ($classes->isEmpty() || $heads->isEmpty())
        <div class="card-pad text-sm text-slate-500">Add classes and fee heads first.</div>
    @else
        <div class="card overflow-x-auto">
            <table class="tbl">
                <thead><tr><th>Class</th>@foreach ($heads as $h)<th>{{ $h->name }}{{ $h->is_monthly ? ' (monthly)' : '' }}</th>@endforeach</tr></thead>
                <tbody>
                @foreach ($classes as $c)
                    <tr wire:key="c-{{ $c->id }}">
                        <td class="font-medium whitespace-nowrap">{{ $c->label }}</td>
                        @foreach ($heads as $h)
                            <td><input class="input min-w-24" type="number" min="0" aria-label="{{ $c->label }} {{ $h->name }}" wire:model="amounts.{{ $c->id }}.{{ $h->id }}"></td>
                        @endforeach
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @endif
    @error('amounts.*.*')<p class="err">{{ $message }}</p>@enderror
</form>
