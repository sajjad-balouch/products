<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Business plans that already had installments now include cash and wholesale sales too. */
    public function up(): void
    {
        foreach (DB::table('plans')->where('product', 'business')->get() as $plan) {
            $features = json_decode($plan->features, true) ?: [];
            if (in_array('installments', $features, true) && ! in_array('sales', $features, true)) {
                $features[] = 'sales';
                DB::table('plans')->where('id', $plan->id)->update(['features' => json_encode(array_values($features))]);
            }
        }
    }

    public function down(): void
    {
        foreach (DB::table('plans')->where('product', 'business')->get() as $plan) {
            $features = array_values(array_diff(json_decode($plan->features, true) ?: [], ['sales']));
            DB::table('plans')->where('id', $plan->id)->update(['features' => json_encode($features)]);
        }
    }
};
