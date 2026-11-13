<?php

namespace App\Livewire\Students;

use App\Services\StudentImporter;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

#[Title('Import students')]
class Import extends Component
{
    use WithFileUploads;

    public $file = null;

    public array $rows = [];

    public array $problems = [];

    public ?array $result = null;

    public function updatedFile(StudentImporter $importer): void
    {
        $this->validate(['file' => 'required|file|mimes:csv,txt|max:5120']);

        $parsed = $importer->parse($this->file->getRealPath());
        $this->rows = $parsed['rows'];
        $this->problems = $parsed['errors'];
        $this->result = null;
    }

    public function import(StudentImporter $importer): void
    {
        abort_if($this->problems || ! $this->rows, 422);

        $this->result = $importer->import(Auth::user()->school_id, $this->rows);
        $this->reset('file', 'rows', 'problems');
    }

    public function render()
    {
        return view('livewire.students.import');
    }
}
