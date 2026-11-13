<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // machine: sends punches itself. kiosk: a computer with a USB fingerprint reader showing the kiosk page.
        Schema::table('biometric_devices', function (Blueprint $t) {
            $t->string('type', 10)->default('machine')->after('name');
        });

        // What the matcher made from a staff member's finger. Not a picture: a picture is never stored.
        Schema::create('fingerprint_templates', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('staff_id')->constrained('staff')->cascadeOnDelete();
            $t->string('finger', 20);
            $t->text('template');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fingerprint_templates');
        Schema::table('biometric_devices', fn (Blueprint $t) => $t->dropColumn('type'));
    }
};
