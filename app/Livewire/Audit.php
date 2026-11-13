<?php

namespace App\Livewire;

use App\Models\AuditLog;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Audit log')]
class Audit extends Component
{
    use WithPagination;

    public string $type = '';

    public string $person = '';

    public function updated(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        return view('livewire.audit', [
            'logs' => AuditLog::query()
                ->when($this->type !== '', fn ($q) => $q->where('subject_type', $this->type))
                ->when($this->person !== '', fn ($q) => $q->where('user_name', 'like', "%{$this->person}%"))
                ->latest('id')->paginate(30),
        ]);
    }
}
