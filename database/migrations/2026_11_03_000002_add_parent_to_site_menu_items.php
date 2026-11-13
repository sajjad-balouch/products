<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A link with a parent shows in that link's dropdown. Removing the parent keeps the link.
        Schema::table('site_menu_items', function (Blueprint $t) {
            $t->foreignId('parent_id')->nullable()->after('site_id')->constrained('site_menu_items')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('site_menu_items', function (Blueprint $t) {
            $t->dropConstrainedForeignId('parent_id');
        });
    }
};
