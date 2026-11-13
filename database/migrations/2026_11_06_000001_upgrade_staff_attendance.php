<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff', function (Blueprint $t) {
            // teaching, office, support (attendants, peons, cleaners), transport, security
            $t->string('category', 20)->default('teaching')->after('designation');
            // Own duty hours, for guards on a night shift and the like. Empty means the school's hours.
            $t->string('duty_starts_at', 5)->nullable()->after('category');
            $t->string('duty_ends_at', 5)->nullable()->after('duty_starts_at');
        });
        DB::table('staff')->where('is_teacher', false)->update(['category' => 'support']);

        Schema::table('staff_attendances', function (Blueprint $t) {
            $t->string('check_in', 5)->nullable()->after('status');
            $t->string('check_out', 5)->nullable()->after('check_in');
            $t->string('note')->nullable()->after('check_out');
            $t->string('source', 10)->default('office')->after('note'); // office, or self when staff check in on their own
        });

        Schema::table('schools', function (Blueprint $t) {
            $t->string('staff_starts_at', 5)->default('08:00')->after('risk_attendance_threshold');
            $t->string('staff_ends_at', 5)->default('14:00')->after('staff_starts_at');
            $t->unsignedTinyInteger('staff_late_minutes')->default(10)->after('staff_ends_at');
            $t->unsignedTinyInteger('lates_per_absent')->default(0)->after('staff_late_minutes');
            $t->boolean('staff_self_check_in')->default(false)->after('lates_per_absent');
        });

        Schema::table('payrolls', function (Blueprint $t) {
            $t->unsignedTinyInteger('half_days')->default(0)->after('absent_days');
            $t->unsignedTinyInteger('late_days')->default(0)->after('half_days');
            // Absent days, plus half a day per half day, plus a day for every set number of lates.
            $t->decimal('deducted_days', 5, 1)->default(0)->after('late_days');
        });
        DB::table('payrolls')->update(['deducted_days' => DB::raw('absent_days')]);
    }

    public function down(): void
    {
        Schema::table('payrolls', fn (Blueprint $t) => $t->dropColumn(['half_days', 'late_days', 'deducted_days']));
        Schema::table('schools', fn (Blueprint $t) => $t->dropColumn(['staff_starts_at', 'staff_ends_at', 'staff_late_minutes', 'lates_per_absent', 'staff_self_check_in']));
        Schema::table('staff_attendances', fn (Blueprint $t) => $t->dropColumn(['check_in', 'check_out', 'note', 'source']));
        Schema::table('staff', fn (Blueprint $t) => $t->dropColumn(['category', 'duty_starts_at', 'duty_ends_at']));
    }
};
