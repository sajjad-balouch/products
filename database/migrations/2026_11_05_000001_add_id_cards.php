<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Photos for ID cards, kept on the private disk.
        Schema::table('students', fn (Blueprint $t) => $t->string('photo_path')->nullable()->after('b_form'));
        Schema::table('guardians', fn (Blueprint $t) => $t->string('photo_path')->nullable()->after('address'));
        // Template, colours, logo, signature and back text for the school's cards.
        Schema::table('schools', fn (Blueprint $t) => $t->json('card_settings')->nullable()->after('risk_attendance_threshold'));
    }

    public function down(): void
    {
        Schema::table('students', fn (Blueprint $t) => $t->dropColumn('photo_path'));
        Schema::table('guardians', fn (Blueprint $t) => $t->dropColumn('photo_path'));
        Schema::table('schools', fn (Blueprint $t) => $t->dropColumn('card_settings'));
    }
};
