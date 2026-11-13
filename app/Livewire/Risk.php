<?php

namespace App\Livewire;

use App\Services\RiskService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Early warning')]
class Risk extends Component
{
    public function render(RiskService $risk)
    {
        return view('livewire.risk', ['rows' => $risk->assess(Auth::user()->school)]);
    }
}
