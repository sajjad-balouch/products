<?php

use App\Models\Biz\ProductType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Businesses made before products existed get the same ready-made product types as new ones. */
    public function up(): void
    {
        foreach (DB::table('schools')->where('type', 'business')->pluck('id') as $id) {
            ProductType::seedStarters($id);
        }
    }

    public function down(): void
    {
        // Types may be in use by then, so they stay.
    }
};
