<?php

namespace App\Livewire\Students;

use App\Models\AuditLog;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Promote students')]
class Promote extends Component
{
    public string $from = '';

    public string $to = '';

    public array $held = [];

    public function promote(): void
    {
        $options = Section::pluck('id')->map(fn ($id) => (string) $id)->all();

        $this->validate([
            'from' => ['required', Rule::in($options)],
            'to' => ['required', 'different:from', 'nullable'],
        ], ['to.required' => 'Choose a class, or choose Graduate.']);

        $graduate = $this->to === 'graduate';
        abort_unless($graduate || in_array($this->to, $options, true), 422);

        $count = DB::transaction(function () use ($graduate) {
            $students = Student::active()->where('section_id', $this->from)->whereNotIn('id', $this->held)->get();

            foreach ($students as $student) {
                $student->update($graduate ? ['status' => 'graduated', 'left_on' => now()] : ['section_id' => $this->to]);
            }

            return $students->count();
        });

        AuditLog::record('students_promoted', null, null, null, "$count students from section {$this->from} to {$this->to}. Held back: ".count($this->held));
        $this->held = [];
        $this->dispatch('toast', message: "$count students moved.");
    }

    public function render()
    {
        return view('livewire.students.promote', [
            'sections' => Section::with('schoolClass')->get()->sortBy(fn ($s) => [$s->schoolClass->sort, $s->name]),
            'students' => $this->from !== '' ? Student::active()->where('section_id', $this->from)->orderBy('name')->get() : collect(),
        ]);
    }
}
