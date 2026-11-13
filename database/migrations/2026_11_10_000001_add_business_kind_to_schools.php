<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** What a business sells (mobile shop, motorcycle dealer...), so it sees its own products, catalog and dashboard. */
    public function up(): void
    {
        Schema::table('schools', fn (Blueprint $t) => $t->string('business_kind', 20)->nullable()->after('type'));
    }

    public function down(): void
    {
        Schema::table('schools', fn (Blueprint $t) => $t->dropColumn('business_kind'));
    }
};
