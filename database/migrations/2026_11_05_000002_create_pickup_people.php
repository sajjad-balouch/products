<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // How the family's main contact is related to the children: father, mother, uncle, guardian...
        Schema::table('guardians', fn (Blueprint $t) => $t->string('relation', 30)->nullable()->after('name'));

        // Other people a family allows to pick up its children, each with their own card.
        Schema::create('pickup_people', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('guardian_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->string('relation', 30);
            $t->string('phone', 30)->nullable();
            $t->string('cnic', 20)->nullable();
            $t->string('photo_path')->nullable();
            $t->timestamps();
            $t->index(['school_id', 'guardian_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pickup_people');
        Schema::table('guardians', fn (Blueprint $t) => $t->dropColumn('relation'));
    }
};
