<?php

use App\Models\School;
use App\Services\Biz\ChartOfAccounts;
use App\Services\Biz\StarterCatalog;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /** Existing businesses get the categories, brands, models and the customer credit account too. */
    public function up(): void
    {
        foreach (School::where('type', 'business')->get() as $business) {
            ChartOfAccounts::seed($business);
            StarterCatalog::seed($business->id);
        }
    }

    public function down(): void
    {
        // Kept: they may already be in use.
    }
};
