<?php

namespace App\Livewire;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Settings')]
class Settings extends Component
{
    public array $form = [];

    private const FIELDS = ['name', 'phone', 'email', 'address', 'principal_name', 'fee_due_day', 'late_fee_per_day', 'late_fee_max', 'sibling_discount_percent', 'bank_details', 'jazzcash_number', 'easypaisa_number', 'absent_alerts', 'risk_attendance_threshold', 'staff_starts_at', 'staff_ends_at', 'staff_late_minutes', 'lates_per_absent', 'staff_self_check_in', 'strict_attendance', 'check_in_networks', 'check_in_lat', 'check_in_lng', 'check_in_radius'];

    public function mount(): void
    {
        $this->form = Auth::user()->school->only(self::FIELDS);
    }

    /** Adds the address this computer is using, for an admin sitting at school on the school's internet. */
    public function addThisNetwork(): void
    {
        $list = array_filter(preg_split('/[\s,]+/', (string) ($this->form['check_in_networks'] ?? '')));
        $this->form['check_in_networks'] = implode("\n", array_unique([...$list, request()->ip()]));
    }

    public function save(): void
    {
        $data = $this->validate([
            'form.name' => 'required|string|max:150',
            'form.phone' => 'nullable|string|max:30',
            'form.email' => 'nullable|email|max:150',
            'form.address' => 'nullable|string|max:190',
            'form.principal_name' => 'nullable|string|max:120',
            'form.fee_due_day' => 'required|integer|min:1|max:28',
            'form.late_fee_per_day' => 'required|integer|min:0',
            'form.late_fee_max' => 'required|integer|min:0',
            'form.sibling_discount_percent' => 'required|integer|min:0|max:100',
            'form.bank_details' => 'nullable|string|max:500',
            'form.jazzcash_number' => 'nullable|string|max:30',
            'form.easypaisa_number' => 'nullable|string|max:30',
            'form.absent_alerts' => 'boolean',
            'form.risk_attendance_threshold' => 'required|integer|min:1|max:100',
            'form.staff_starts_at' => 'required|date_format:H:i',
            'form.staff_ends_at' => 'required|date_format:H:i',
            'form.staff_late_minutes' => 'required|integer|min:0|max:120',
            'form.lates_per_absent' => 'required|integer|min:0|max:31',
            'form.staff_self_check_in' => 'boolean',
            'form.strict_attendance' => 'boolean',
            'form.check_in_networks' => ['nullable', 'string', 'max:1000', function ($attribute, $value, $fail) {
                foreach (array_filter(preg_split('/[\s,]+/', (string) $value)) as $entry) {
                    [$ip, $bits] = array_pad(explode('/', $entry, 2), 2, null);
                    if (! filter_var($ip, FILTER_VALIDATE_IP) || ($bits !== null && (! ctype_digit($bits) || (int) $bits > (str_contains($ip, ':') ? 128 : 32)))) {
                        $fail("\"$entry\" is not an internet address. Use one like 39.45.12.7 or 39.45.12.0/24.");

                        return;
                    }
                }
            }],
            'form.check_in_lat' => 'nullable|numeric|between:-90,90|required_with:form.check_in_lng',
            'form.check_in_lng' => 'nullable|numeric|between:-180,180|required_with:form.check_in_lat',
            'form.check_in_radius' => 'required|integer|min:30|max:2000',
        ], [], ['form.check_in_networks' => 'school network', 'form.check_in_lat' => 'latitude', 'form.check_in_lng' => 'longitude', 'form.check_in_radius' => 'distance', 'form.fee_due_day' => 'due day', 'form.staff_starts_at' => 'duty start', 'form.staff_ends_at' => 'duty end', 'form.staff_late_minutes' => 'grace minutes', 'form.lates_per_absent' => 'lates per absent day'])['form'];

        $school = Auth::user()->school;
        foreach (['check_in_lat', 'check_in_lng'] as $key) {
            $data[$key] = ($data[$key] ?? '') === '' ? null : $data[$key];
        }
        $school->update($data);
        AuditLog::record('settings_changed', $school, null, null, implode(', ', array_keys($school->getChanges())));
        $this->dispatch('toast', message: 'Settings saved.');
    }

    public function render()
    {
        return view('livewire.settings');
    }
}
