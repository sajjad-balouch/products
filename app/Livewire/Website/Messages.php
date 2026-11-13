<?php

namespace App\Livewire\Website;

use App\Livewire\Website\Concerns\ManagesSite;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Website messages')]
class Messages extends Component
{
    use ManagesSite;
    use WithPagination;

    public function mount(): void
    {
        $this->need('messages');
    }

    public function toggleRead(int $id): void
    {
        $this->need('messages');
        $message = $this->site()->messages()->findOrFail($id);
        $message->update(['read_at' => $message->read_at ? null : now()]);
    }

    public function delete(int $id): void
    {
        $this->need('messages');
        $this->site()->messages()->findOrFail($id)->delete();
    }

    public function render()
    {
        return view('livewire.website.messages', ['messages' => $this->site()->messages()->paginate(20)]);
    }
}
