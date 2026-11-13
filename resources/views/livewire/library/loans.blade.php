<div>
    <x-page-header title="Book loans" />
    <form wire:submit="issue" class="card-pad mb-4 grid gap-3 sm:grid-cols-4">
        <x-field label="Book" name="book_id" class="sm:col-span-2"><select id="book_id" class="input" wire:model="book_id"><option value="">Select</option>@foreach ($books as $b)<option value="{{ $b->id }}">{{ $b->title }}</option>@endforeach</select></x-field>
        <x-field label="Admission number" name="admission_no"><input id="admission_no" class="input" wire:model="admission_no"></x-field>
        <x-field label="Days" name="days"><input id="days" class="input" type="number" wire:model="days"></x-field>
        <button class="btn btn-primary sm:col-span-4">Issue book</button>
    </form>
    <div class="card overflow-x-auto">
        <table class="tbl">
            <thead><tr><th>Book</th><th>Student</th><th>Issued</th><th>Due</th><th></th></tr></thead>
            <tbody>
            @forelse ($loans as $loan)
                <tr wire:key="l-{{ $loan->id }}"><td>{{ $loan->book->title }}</td><td>{{ $loan->student->name }}</td><td>{{ $loan->issued_on->format('j M') }}</td><td @class(['font-medium text-red-700' => $loan->due_on->isPast()])>{{ $loan->due_on->format('j M') }}</td>
                    <td class="text-end"><button class="btn btn-sm" wire:click="returnBook({{ $loan->id }})">Returned</button></td></tr>
            @empty
                <tr><td colspan="5" class="py-10 text-center text-slate-500">No books are out.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
