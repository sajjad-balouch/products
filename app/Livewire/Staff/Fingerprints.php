<?php

namespace App\Livewire\Staff;

use App\Models\FingerprintTemplate;
use App\Models\Staff;
use App\Services\FingerprintMatcher;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use RuntimeException;

/**
 * Registers staff fingers for the kiosk, using a USB reader on the computer this page is open on.
 * Each press becomes one template; three presses of the same finger make recognition reliable.
 * With the server's helper the picture is sent here; with the helper on this computer (shared
 * hosting) the browser turns it into a template first and only the template is sent.
 */
#[Title('Staff fingerprints')]
class Fingerprints extends Component
{
    public string $staffId = '';

    public string $finger = 'right-index';

    public int $presses = 0;

    public ?array $test = null;

    public function updatedStaffId(): void
    {
        $this->presses = 0;
    }

    public function updatedFinger(): void
    {
        $this->presses = 0;
    }

    private function validateChoice(): void
    {
        $this->validate([
            'staffId' => ['required', Rule::exists('staff', 'id')->where('school_id', auth()->user()->school_id)->where('status', 'active')],
            'finger' => ['required', Rule::in(array_keys(FingerprintTemplate::FINGERS))],
        ], [], ['staffId' => 'staff member']);
    }

    /** One press of the chosen finger, sent as a PNG from the reader, for the server's helper. */
    public function enrol(string $png, FingerprintMatcher $matcher): void
    {
        $this->validateChoice();
        abort_if(FingerprintMatcher::local() || strlen($png) > 4000000, 422);

        try {
            $template = $matcher->extract($png);
        } catch (RuntimeException $e) {
            $this->dispatch('toast', message: $e->getMessage(), error: true);

            return;
        }

        $this->save($template);
    }

    /** One press already turned into a template by the helper on this computer. */
    public function enrolTemplate(string $template): void
    {
        $this->validateChoice();
        abort_unless(FingerprintMatcher::local() && FingerprintMatcher::looksLikeTemplate($template), 422);

        $this->save($template);
    }

    private function save(string $template): void
    {
        FingerprintTemplate::create(['staff_id' => (int) $this->staffId, 'finger' => $this->finger, 'template' => $template]);
        $this->presses++;
        $this->dispatch('toast', message: $this->presses >= 3 ? 'Finger registered. You can add another finger as a spare.' : 'Saved press '.$this->presses.' of 3. Lift and press again.');
    }

    /** Checks who a press is recognised as, without marking attendance. */
    public function check(string $png, FingerprintMatcher $matcher): void
    {
        abort_if(FingerprintMatcher::local(), 422);

        try {
            $match = $matcher->identify($png, FingerprintTemplate::get(['staff_id', 'template']));
        } catch (RuntimeException $e) {
            $this->dispatch('toast', message: $e->getMessage(), error: true);

            return;
        }

        $this->showCheck($match['staff_id'], $match['score']);
    }

    /** This school's templates, for the helper on this computer to compare a check press with. */
    public function candidates(): array
    {
        abort_unless(FingerprintMatcher::local(), 422);

        return FingerprintTemplate::get(['staff_id', 'template'])->map(fn ($t) => ['id' => (string) $t->staff_id, 'template' => $t->template])->all();
    }

    public function showCheck(?int $staffId, float $score): void
    {
        $this->test = ['name' => $staffId ? Staff::find($staffId)?->name : null, 'score' => round($score)];
    }

    public function forget(int $staffId, string $finger): void
    {
        FingerprintTemplate::where('staff_id', $staffId)->where('finger', $finger)->get()->each->delete();
        $this->presses = 0;
    }

    public function render()
    {
        return view('livewire.staff.fingerprints', [
            'staff' => Staff::where('status', 'active')->orderBy('name')->get(),
            'enrolled' => FingerprintTemplate::selectRaw('staff_id, finger, count(*) as presses')->groupBy('staff_id', 'finger')->get()->groupBy('staff_id'),
            'fingers' => FingerprintTemplate::FINGERS,
            'ready' => FingerprintMatcher::ready(),
            'helper' => FingerprintMatcher::browserSettings(),
        ]);
    }
}
