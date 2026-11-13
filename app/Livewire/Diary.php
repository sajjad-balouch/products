<?php

namespace App\Livewire;

use App\Models\Diary as DiaryEntry;
use App\Models\Section;
use App\Models\Subject;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Daily diary')]
class Diary extends Component
{
    public string $section_id = '';

    public string $subject_id = '';

    public string $date = '';

    public string $body = '';

    public function mount(): void
    {
        $this->date = now()->toDateString();
    }

    private function sections()
    {
        $ids = Auth::user()->teachableSectionIds();

        return Section::with('schoolClass')->when($ids !== null, fn ($q) => $q->whereIn('id', $ids))->get()
            ->sortBy(fn ($s) => [$s->schoolClass->sort, $s->name])->values();
    }

    public function save(): void
    {
        $this->validate([
            'section_id' => ['required', Rule::in($this->sections()->pluck('id')->all())],
            'subject_id' => ['nullable', Rule::exists('subjects', 'id')->where('school_id', Auth::user()->school_id)],
            'date' => 'required|date',
            'body' => 'required|string|max:3000',
        ]);

        DiaryEntry::create([
            'section_id' => $this->section_id,
            'subject_id' => $this->subject_id ?: null,
            'date' => $this->date,
            'body' => $this->body,
            'created_by' => Auth::id(),
        ]);

        $this->reset('body');
        $this->dispatch('toast', message: 'Saved. Parents can now see it in the portal.');
    }

    public function remove(int $id): void
    {
        $entry = DiaryEntry::findOrFail($id);
        abort_unless($this->sections()->contains('id', $entry->section_id), 403);
        $entry->delete();
    }

    public function render()
    {
        $sections = $this->sections();

        return view('livewire.diary', [
            'sections' => $sections,
            'subjects' => Subject::orderBy('name')->get(),
            'entries' => DiaryEntry::with(['section.schoolClass', 'subject'])->whereIn('section_id', $sections->pluck('id'))->latest('date')->latest('id')->limit(30)->get(),
        ]);
    }
}
