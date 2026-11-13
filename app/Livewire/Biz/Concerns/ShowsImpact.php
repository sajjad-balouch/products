<?php

namespace App\Livewire\Biz\Concerns;

use App\Services\Biz\Impact;
use Closure;
use Illuminate\Support\Facades\Validator;

/**
 * The live "what saving will change" box on business forms. It checks the form quietly
 * (no red errors while the user is still typing) and then runs the real save and undoes it.
 */
trait ShowsImpact
{
    /**
     * @param  array<string, mixed>  $rules  the same rules the save uses
     * @param  Closure  $action  the same work the save does; anything it throws is shown as the reason it cannot be saved
     */
    protected function impactOf(array $rules, Closure $action, array $messages = [], array $attributes = []): array
    {
        $check = Validator::make($this->all(), $rules, $messages, $attributes);
        if ($check->fails()) {
            return ['ok' => false, 'pending' => $check->errors()->first(), 'error' => null, 'effects' => [], 'lines' => [], 'profit' => null, 'stock' => []];
        }

        return Impact::preview($this->business(), $action);
    }
}
