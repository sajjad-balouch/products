<?php

namespace App\Livewire\Staff;

use App\Models\Staff;
use App\Models\StaffAttendance;
use App\Services\StaffAttendanceService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

/** One day for every staff member: teachers, office, attendants, drivers and guards. */
#[Title('Staff attendance')]
class AttendanceSheet extends Component
{
    public string $date = '';

    #[Url]
    public string $category = '';

    /** By staff id: status, check-in and check-out times. */
    public array $rows = [];

    public function mount(): void
    {
        $this->date = now()->toDateString();
        $this->load();
    }

    public function updatedDate(): void
    {
        $this->load();
    }

    private function staff()
    {
        return Staff::where('status', 'active')->orderBy('name')->get();
    }

    private function load(): void
    {
        $existing = StaffAttendance::whereDate('date', $this->date)->get()->keyBy('staff_id');

        $this->rows = $this->staff()->mapWithKeys(fn ($s) => [$s->id => [
            'status' => $existing[$s->id]->status ?? 'P',
            'in' => (string) ($existing[$s->id]->check_in ?? ''),
            'out' => (string) ($existing[$s->id]->check_out ?? ''),
        ]])->all();
    }

    /** Typing an arrival time after the duty start marks the person late, and an earlier time puts them back to present. */
    public function updatedRows($value, string $key): void
    {
        [$id, $field] = array_pad(explode('.', $key), 2, null);

        if ($field !== 'in' || ! preg_match('/^\d{2}:\d{2}$/', (string) $value) || ! in_array($this->rows[$id]['status'] ?? '', ['P', 'L'], true)) {
            return;
        }

        $person = Staff::find($id);
        if ($person) {
            $this->rows[$id]['status'] = app(StaffAttendanceService::class)->isLate($person, Auth::user()->school, $value) ? 'L' : 'P';
        }
    }

    public function allPresent(): void
    {
        $locked = $this->locked();
        foreach ($this->visible()->pluck('id')->diff($locked) as $id) {
            $this->rows[$id]['status'] = 'P';
        }
    }

    private function visible()
    {
        return $this->staff()->when($this->category !== '', fn ($c) => $c->where('category', $this->category))->values();
    }

    public function save(): void
    {
        $this->validate([
            'date' => 'required|date|before_or_equal:today',
            'rows.*.status' => ['required', Rule::in(array_keys(StaffAttendanceService::STATUSES))],
            'rows.*.in' => 'nullable|date_format:H:i',
            'rows.*.out' => 'nullable|date_format:H:i',
        ], [], ['rows.*.in' => 'arrival time', 'rows.*.out' => 'leaving time']);

        $ids = $this->staff()->pluck('id');
        $locked = $this->locked();
        foreach ($this->rows as $id => $row) {
            if ($ids->contains((int) $id) && ! $locked->contains((int) $id)) {
                StaffAttendance::updateOrCreate(['staff_id' => $id, 'date' => $this->date], [
                    'status' => $row['status'],
                    'check_in' => in_array($row['status'], ['A', 'V'], true) ? null : ($row['in'] ?: null),
                    'check_out' => in_array($row['status'], ['A', 'V'], true) ? null : ($row['out'] ?: null),
                ]);
            }
        }

        $this->dispatch('toast', message: 'Staff attendance saved.');
    }

    /** In strict mode a day recorded by a machine or by the person checking in cannot be changed here. */
    private function locked()
    {
        if (! Auth::user()->school->strict_attendance) {
            return collect();
        }

        return StaffAttendance::whereDate('date', $this->date)->whereIn('source', ['device', 'self'])->pluck('staff_id');
    }

    public function render()
    {
        $all = $this->staff();
        $school = Auth::user()->school;

        return view('livewire.staff.attendance-sheet', [
            'staff' => $this->visible(),
            'categories' => collect(Staff::CATEGORIES)->map(fn ($label, $key) => ['label' => explode(' (', $label)[0], 'count' => $all->where('category', $key)->count()])->filter(fn ($c) => $c['count'] > 0),
            'total' => $all->count(),
            'counts' => collect($this->rows)->only($all->pluck('id')->all())->countBy('status'),
            'saved' => StaffAttendance::whereDate('date', $this->date)->get()->keyBy('staff_id'),
            'school' => $school,
            'statuses' => StaffAttendanceService::STATUSES,
            'locked' => $this->locked(),
        ]);
    }
}
