<?php

namespace App\Livewire;

use App\Models\Guardian;
use App\Models\Message;
use App\Models\Section;
use App\Services\MessageService;
use App\Services\Sms;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Messages')]
class Messages extends Component
{
    use WithPagination;

    public string $audience = 'all';

    public string $body = '';

    public string $tab = 'queued';

    public function compose(MessageService $messages): void
    {
        $sectionIds = Section::pluck('id')->map(fn ($id) => "section:$id")->all();
        $this->validate(['body' => 'required|string|max:1000', 'audience' => ['required', function ($attr, $value, $fail) use ($sectionIds) {
            if ($value !== 'all' && ! in_array($value, $sectionIds, true)) {
                $fail('Choose who should receive this.');
            }
        }]]);

        $guardians = Guardian::whereHas('students', function ($q) {
            $q->where('status', 'active');
            if ($this->audience !== 'all') {
                $q->where('section_id', (int) substr($this->audience, 8));
            }
        })->get();

        foreach ($guardians as $guardian) {
            $messages->queue($guardian, 'general', $this->body);
        }

        $this->reset('body');
        $this->dispatch('toast', message: $guardians->count().' messages added to the outbox.');
    }

    public function markSent(int $id): void
    {
        Message::where('status', 'queued')->findOrFail($id)->update(['status' => 'sent', 'sent_at' => now()]);
    }

    public function discard(int $id): void
    {
        Message::whereIn('status', ['queued', 'failed'])->findOrFail($id)->delete();
    }

    /** Tries a message that did not go through once more. */
    public function retry(int $id, Sms $sms): void
    {
        $r = $sms->deliver(Message::where('status', 'failed')->findOrFail($id));
        $this->dispatch('toast', message: $r['ok'] ? ($r['queued'] ? 'In line again. It goes within a minute.' : 'Sent.') : $r['error'], error: ! $r['ok']);
    }

    /** Everything waiting in the WhatsApp outbox goes out automatically instead. */
    public function sendAllAutomatically(Sms $sms): void
    {
        abort_unless(Sms::enabledFor(auth()->user()->school), 403);
        $sent = $failed = 0;
        foreach (Message::where('status', 'queued')->where('channel', 'whatsapp')->orderBy('id')->limit(200)->get() as $message) {
            $sms->deliver($message)['ok'] ? $sent++ : $failed++;
        }
        $this->dispatch('toast', message: $sent.' messages sent'.($failed ? ", {$failed} could not go (see \"Not sent\")." : '.'), error: $failed > 0);
    }

    /** A failed SMS sent by WhatsApp instead. */
    public function sentByWhatsapp(int $id): void
    {
        Message::where('status', 'failed')->findOrFail($id)->update(['status' => 'sent', 'sent_at' => now(), 'channel' => 'whatsapp']);
    }

    public function render()
    {
        return view('livewire.messages', [
            'sections' => Section::with('schoolClass')->get()->sortBy(fn ($s) => [$s->schoolClass->sort, $s->name]),
            'rows' => Message::with(['guardian', 'customer'])->where('category', '!=', 'test')
                ->when($this->tab === 'sent', fn ($q) => $q->where(fn ($q) => $q->whereIn('status', ['sent', 'sending'])->orWhere(fn ($q) => $q->where('status', 'queued')->where('channel', '!=', 'whatsapp'))))
                ->when($this->tab === 'failed', fn ($q) => $q->where('status', 'failed'))
                ->when(! in_array($this->tab, ['sent', 'failed'], true), fn ($q) => $q->where('status', 'queued')->where('channel', 'whatsapp'))
                ->latest('id')->paginate(25),
            'queuedCount' => Message::where('status', 'queued')->where('channel', 'whatsapp')->count(),
            'inLine' => Message::whereIn('status', ['queued', 'sending'])->where('channel', '!=', 'whatsapp')->count(),
            'failedCount' => Message::where('status', 'failed')->where('category', '!=', 'test')->count(),
            'smsOn' => Sms::enabledFor(auth()->user()->school),
            'channelLabel' => Sms::label(),
        ]);
    }
}
