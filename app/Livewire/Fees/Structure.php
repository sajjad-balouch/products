<?php

namespace App\Livewire\Fees;

use App\Models\FeeHead;
use App\Models\FeeStructure;
use App\Models\SchoolClass;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Title('Fee structure')]
class Structure extends Component
{
    /** amounts[classId][headId] */
    public array $amounts = [];

    public function mount(): void
    {
        foreach (FeeStructure::get() as $row) {
            $this->amounts[$row->school_class_id][$row->fee_head_id] = $row->amount;
        }
    }

    public function save(): void
    {
        $this->validate(['amounts.*.*' => 'nullable|integer|min:0|max:10000000'], [], ['amounts.*.*' => 'amount']);

        $classIds = SchoolClass::pluck('id')->all();
        $headIds = FeeHead::pluck('id')->all();

        DB::transaction(function () use ($classIds, $headIds) {
            foreach ($classIds as $classId) {
                foreach ($headIds as $headId) {
                    $amount = $this->amounts[$classId][$headId] ?? null;
                    $keys = ['school_class_id' => $classId, 'fee_head_id' => $headId];

                    if ($amount === null || $amount === '') {
                        FeeStructure::where($keys)->delete();
                    } else {
                        FeeStructure::updateOrCreate($keys, ['amount' => (int) $amount]);
                    }
                }
            }
        });

        $this->dispatch('toast', message: 'Fee structure saved. It applies to vouchers generated from now on.');
    }

    public function render()
    {
        return view('livewire.fees.structure', [
            'classes' => SchoolClass::orderBy('sort')->get(),
            'heads' => FeeHead::orderByDesc('is_monthly')->orderBy('name')->get(),
        ]);
    }
}
