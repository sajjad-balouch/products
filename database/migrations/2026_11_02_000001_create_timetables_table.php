<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A school can keep several timetables (summer, winter, Ramzan) with their
     * own period timings. Exactly one is active. Entries belong to a timetable
     * through their period. Existing periods move into a "Main timetable".
     */
    public function up(): void
    {
        Schema::create('timetables', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->boolean('is_active')->default(false);
            $t->timestamps();
            $t->unique(['school_id', 'name']);
        });

        Schema::table('timetable_periods', function (Blueprint $t) {
            $t->foreignId('timetable_id')->nullable()->after('school_id')->constrained()->cascadeOnDelete();
            $t->boolean('is_break')->default(false)->after('ends_at');
        });

        foreach (DB::table('schools')->pluck('id') as $schoolId) {
            $timetableId = DB::table('timetables')->insertGetId([
                'school_id' => $schoolId, 'name' => 'Main timetable', 'is_active' => true,
                'created_at' => now(), 'updated_at' => now(),
            ]);
            DB::table('timetable_periods')->where('school_id', $schoolId)->update(['timetable_id' => $timetableId]);
        }

        Schema::table('timetable_periods', function (Blueprint $t) {
            $t->foreignId('timetable_id')->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('timetable_periods', function (Blueprint $t) {
            $t->dropConstrainedForeignId('timetable_id');
            $t->dropColumn('is_break');
        });
        Schema::dropIfExists('timetables');
    }
};
