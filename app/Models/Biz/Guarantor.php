<?php

namespace App\Models\Biz;

use App\Models\Concerns\BelongsToSchool;
use Illuminate\Database\Eloquent\Model;

class Guarantor extends Model
{
    use BelongsToSchool;

    protected $table = 'biz_guarantors';

    protected $guarded = ['id'];

    public function contract()
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }
}
