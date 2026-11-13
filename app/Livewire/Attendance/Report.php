<?php

namespace App\Livewire\Attendance;

use App\Models\Attendance;
use App\Models\Section;
use App\Models\Student;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Attendance report')]
class Report extends Component
{
    public string $section_id = '';

    public string $month = '';

    public function mount(): void
    {
        $this->month = now()->format('Y-m');
        $this->section_id = (string) ($this->sections()->first()?->id ?? '');
    }

    private function sections()
    {
        $ids = Auth::user()->teachableSectionIds();

        return Section::with('schoolClass')->when($ids !== null, fn ($q) => $q->whereIn('id', $ids))->get()
            ->sortBy(fn ($s) => [$s->schoolClass->sort, $s->name])->values();
    }

    public function render()
    {
        $sections = $this->sections();
        $month = preg_match('/^\d{4}-\d{2}$/', $this->month) ? Carbon::parse($this->month.'-01') : now()->startOfMonth();
        $allowed = $sections->contains('id', (int) $this->section_id);

        $students = $allowed ? Student::active()->where('section_id', $this->section_id)->orderBy('name')->get() : collect();
        $records = $allowed
            ? Attendance::where('section_id', $this->section_id)->whereBetween('date', [$month->copy(), $month->copy()->endOfMonth()])->get()
                ->groupBy('student_id')->map(fn ($rows) => $rows->mapWithKeys(fn ($r) => [$r->date->day => $r->status]))
            : collect();

        return view('livewire.attendance.report', [
            'sections' => $sections,
            'students' => $students,
            'records' => $records,
            'days' => $month->daysInMonth,
            'monthDate' => $month,
        ]);
    }
}
