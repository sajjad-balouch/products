<?php

namespace App\Livewire\Fees;

use App\Models\FeeStructure;
use App\Models\FeeVoucher;
use App\Models\SchoolClass;
use App\Services\FeeService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Generate vouchers')]
class Generate extends Component
{
    public string $month = '';

    public string $due_on = '';

    public string $class_id = '';

    public ?array $result = null;

    public function mount(): void
    {
        $school = Auth::user()->school;
        $this->month = now()->format('Y-m');
        $this->due_on = now()->day(min($school->fee_due_day, now()->daysInMonth))->toDateString();
    }

    public function generate(FeeService $fees): void
    {
        $school = Auth::user()->school;

        $this->validate([
            'month' => 'required|date_format:Y-m',
            'due_on' => 'required|date',
            'class_id' => ['nullable', Rule::exists('school_classes', 'id')->where('school_id', $school->id)],
        ]);

        $this->result = $fees->generateMonthly(
            $school,
            Carbon::createFromFormat('Y-m-d', $this->month.'-01')->startOfDay(),
            Carbon::parse($this->due_on),
            $this->class_id ? (int) $this->class_id : null,
        );
    }

    public function applyLateFees(FeeService $fees): void
    {
        $count = $fees->applyLateFees(Auth::user()->school);
        $this->dispatch('toast', message: "Late fee updated on $count vouchers.");
    }

    public function render()
    {
        $school = Auth::user()->school;
        $monthDate = preg_match('/^\d{4}-\d{2}$/', $this->month) ? $this->month.'-01' : now()->startOfMonth()->toDateString();

        return view('livewire.fees.generate', [
            'classes' => SchoolClass::orderBy('sort')->get(),
            'school' => $school,
            'existing' => FeeVoucher::whereDate('month', $monthDate)->where('status', '!=', 'void')->count(),
            'hasStructure' => FeeStructure::exists(),
        ]);
    }
}
