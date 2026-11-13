<?php

namespace App\Livewire\Attendance;

use App\Models\Attendance;
use App\Models\Message;
use App\Models\Section;
use App\Models\Student;
use App\Services\MessageService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Mark attendance')]
class Mark extends Component
{
    public string $section_id = '';

    public string $date = '';

    /** student id => P, A, L or V */
    public array $statuses = [];

    public bool $alreadyMarked = false;

    public function mount(): void
    {
        $this->date = now()->toDateString();
        $first = $this->sections()->first();
        if ($first) {
            $this->section_id = (string) $first->id;
            $this->load();
        }
    }

    public function updatedSectionId(): void
    {
        $this->load();
    }

    public function updatedDate(): void
    {
        $this->load();
    }

    /** Everyone starts as present, so a teacher only taps the absent students. */
    private function load(): void
    {
        $this->statuses = [];
        if ($this->section_id === '' || ! $this->allowed((int) $this->section_id)) {
            return;
        }

        $existing = Attendance::where('section_id', $this->section_id)->whereDate('date', $this->date)->pluck('status', 'student_id');
        $this->alreadyMarked = $existing->isNotEmpty();

        foreach (Student::active()->where('section_id', $this->section_id)->pluck('id') as $id) {
            $this->statuses[$id] = $existing[$id] ?? 'P';
        }
    }

    public function save(MessageService $messages): void
    {
        $this->validate([
            'date' => 'required|date|before_or_equal:today',
            'statuses.*' => 'required|in:P,A,L,V',
        ]);
        abort_unless($this->allowed((int) $this->section_id), 403);

        $date = Carbon::parse($this->date);
        $students = Student::active()->where('section_id', $this->section_id)->with('guardian', 'school')->get()->keyBy('id');
        $school = Auth::user()->school;
        $alerts = 0;

        DB::transaction(function () use ($students, $date, $messages, $school, &$alerts) {
            foreach ($this->statuses as $studentId => $status) {
                $student = $students->get($studentId);
                if (! $student) {
                    continue; // only students of this section
                }

                Attendance::updateOrCreate(
                    ['student_id' => $student->id, 'date' => $date->toDateString()],
                    ['school_id' => $student->school_id, 'section_id' => $student->section_id, 'status' => $status, 'marked_by' => Auth::id()],
                );

                if ($status === 'A' && $school->absent_alerts && $date->isToday()) {
                    // Saving twice must not message the parent twice.
                    $sent = Message::where('guardian_id', $student->guardian_id)->where('category', 'absent')
                        ->whereDate('created_at', $date)->where('body', 'like', "%{$student->name}%")->exists();

                    if (! $sent) {
                        $messages->absentAlert($student, $date);
                        $alerts++;
                    }
                }
            }
        });

        $this->alreadyMarked = true;
        $this->dispatch('toast', message: 'Attendance saved.'.($alerts ? " $alerts parent alerts are in the WhatsApp outbox." : ''));
    }

    private function sections()
    {
        $ids = Auth::user()->teachableSectionIds();

        return Section::with('schoolClass')->when($ids !== null, fn ($q) => $q->whereIn('id', $ids))->get()
            ->sortBy(fn ($s) => [$s->schoolClass->sort, $s->name])->values();
    }

    private function allowed(int $sectionId): bool
    {
        $ids = Auth::user()->teachableSectionIds();

        return Section::whereKey($sectionId)->exists() && ($ids === null || in_array($sectionId, $ids, true));
    }

    public function render()
    {
        $students = $this->section_id !== '' && $this->allowed((int) $this->section_id)
            ? Student::active()->where('section_id', $this->section_id)->orderBy('roll_no')->orderBy('name')->get()
            : collect();

        return view('livewire.attendance.mark', [
            'sections' => $this->sections(),
            'students' => $students,
            'counts' => array_count_values($this->statuses),
        ]);
    }
}
