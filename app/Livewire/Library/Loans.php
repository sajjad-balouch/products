<?php

namespace App\Livewire\Library;

use App\Models\Book;
use App\Models\BookLoan;
use App\Models\Student;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Book loans')]
class Loans extends Component
{
    public string $book_id = '';

    public string $admission_no = '';

    public int $days = 14;

    public function issue(): void
    {
        $schoolId = Auth::user()->school_id;

        $this->validate([
            'book_id' => ['required', Rule::exists('books', 'id')->where('school_id', $schoolId)],
            'admission_no' => 'required|string',
            'days' => 'required|integer|min:1|max:90',
        ]);

        $student = Student::active()->where('admission_no', trim($this->admission_no))->first();
        if (! $student) {
            $this->addError('admission_no', 'No active student has this admission number.');

            return;
        }

        $book = Book::withCount(['loans as out_count' => fn ($q) => $q->whereNull('returned_on')])->findOrFail($this->book_id);
        if ($book->out_count >= $book->copies) {
            $this->addError('book_id', 'All copies of this book are out.');

            return;
        }

        BookLoan::create(['book_id' => $book->id, 'student_id' => $student->id, 'issued_on' => now(), 'due_on' => now()->addDays($this->days)]);
        $this->reset('book_id', 'admission_no');
        $this->dispatch('toast', message: "Issued to {$student->name}.");
    }

    public function returnBook(int $id): void
    {
        BookLoan::findOrFail($id)->update(['returned_on' => now()]);
    }

    public function render()
    {
        return view('livewire.library.loans', [
            'books' => Book::orderBy('title')->get(),
            'loans' => BookLoan::with(['book', 'student'])->whereNull('returned_on')->orderBy('due_on')->get(),
        ]);
    }
}
