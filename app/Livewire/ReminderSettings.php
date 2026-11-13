<?php

namespace App\Livewire;

use App\Models\Message;
use App\Services\Reminders;
use App\Services\Sms;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

/** Automatic SMS reminders for fees (schools) or installments (businesses): on the settings page of each. */
class ReminderSettings extends Component
{
    public array $s = [];

    public string $testPhone = '';

    public ?array $result = null;

    public function mount(): void
    {
        abort_unless(Auth::user()?->isRole('admin'), 403);
        $s = Reminders::settings($this->school());
        $s['days_before'] = array_map('strval', $s['days_before']);
        $s['late_every'] = (string) $s['late_every'];
        $s['late_times'] = (string) $s['late_times'];
        $this->s = $s;
        $this->testPhone = (string) Auth::user()->phone;
    }

    private function school()
    {
        return Auth::user()->school;
    }

    /** The two messages this kind of account uses. */
    private function keys(): array
    {
        return $this->school()->isBusiness() ? ['installment', 'installment_late'] : ['fee', 'fee_late'];
    }

    public function save(): void
    {
        [$due, $late] = $this->keys();
        $this->validate([
            's.enabled' => 'boolean',
            's.days_before' => 'array',
            's.days_before.*' => 'in:1,2,3,5,7',
            's.on_due' => 'boolean',
            's.late_every' => 'required|integer|in:0,1,2,3,5,7',
            's.late_times' => 'required|integer|min:1|max:10',
            "s.$due" => 'required|string|max:480',
            "s.$late" => 'required|string|max:480',
        ], [], ["s.$due" => 'message', "s.$late" => 'late message']);

        $keep = array_intersect_key($this->s, array_flip(['enabled', 'days_before', 'on_due', 'late_every', 'late_times', $due, $late]));
        $keep['days_before'] = array_values(array_map('intval', $keep['days_before'] ?? []));
        $keep['late_every'] = (int) $keep['late_every'];
        $keep['late_times'] = (int) $keep['late_times'];
        $keep['enabled'] = (bool) $keep['enabled'];
        $keep['on_due'] = (bool) $keep['on_due'];
        $this->school()->update(['reminder_settings' => $keep]);
        $this->dispatch('toast', message: $keep['enabled'] ? 'Reminders are on. They go out every morning.' : 'Reminders saved (switched off).');
    }

    public function sendTest(Sms $sms): void
    {
        $this->validate(['testPhone' => 'required|string|min:10|max:20'], [], ['testPhone' => 'phone']);
        [$due] = $this->keys();
        $text = 'Test: '.mb_substr(strtr($this->s[$due], ['{name}' => 'Test', '{students}' => 'Ali', '{amount}' => 'Rs 5,000', '{due_date}' => today()->format('j M'), '{days_late}' => '3', '{plan}' => 'IS-0001', '{balance}' => 'Rs 20,000', '{school}' => $this->school()->name, '{business}' => $this->school()->name]), 0, 300);
        $message = Message::create(['phone' => $this->testPhone, 'category' => 'test', 'channel' => 'whatsapp', 'body' => $text, 'status' => 'queued', 'created_by' => Auth::id()]);
        $r = $sms->deliver($message);
        $this->dispatch('toast', message: $r['ok'] ? ($r['queued'] ? 'Test message is in line. It reaches '.$this->testPhone.' within a minute.' : 'Test message sent to '.$this->testPhone.'.') : $r['error'], error: ! $r['ok']);
    }

    /** Sends today's reminders now, instead of waiting for the morning. */
    public function runNow(Reminders $reminders): void
    {
        $this->save();
        $this->result = $reminders->run($this->school()->fresh());
    }

    public function render(Reminders $reminders)
    {
        $school = $this->school();
        $preview = $reminders->due($school->forceFill(['reminder_settings' => array_merge($this->s, ['enabled' => true])]), today());

        return view('livewire.reminder-settings', [
            'business' => $school->isBusiness(),
            'keys' => $this->keys(),
            'placeholders' => Reminders::PLACEHOLDERS[$school->isBusiness() ? 'business' : 'school'],
            'smsOn' => Sms::enabledFor($school),
            'inPlan' => $school->hasFeature('sms'),
            'channelLabel' => Sms::label(),
            'smsDriver' => Sms::driver(),
            'preview' => $preview,
            'sentToday' => Message::whereNotNull('dedupe_key')->whereDate('created_at', today())->selectRaw('status, COUNT(*) as n')->groupBy('status')->pluck('n', 'status'),
        ]);
    }
}
