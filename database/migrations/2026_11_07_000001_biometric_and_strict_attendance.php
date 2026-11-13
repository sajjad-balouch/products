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
            // The user number this person has on the attendance machine.
            $t->string('biometric_id', 20)->nullable()->after('duty_ends_at');
            $t->index(['school_id', 'biometric_id']);
        });

        Schema::create('biometric_devices', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            // Machines identify themselves by serial number when they push.
            $t->string('serial_number', 60)->unique();
            // Only a hash is kept. The token itself is shown once, for software that sends punches by API.
            $t->string('token_hash', 64)->nullable()->unique();
            $t->boolean('is_active')->default(true);
            $t->timestamp('last_seen_at')->nullable();
            $t->string('last_ip', 45)->nullable();
            $t->timestamps();
        });

        // Every punch exactly as the machine sent it, also for ids not yet linked to a staff member.
        Schema::create('biometric_punches', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('biometric_device_id')->nullable()->constrained()->nullOnDelete();
            $t->foreignId('staff_id')->nullable()->constrained('staff')->nullOnDelete();
            $t->string('device_user_id', 20);
            $t->dateTime('punched_at');
            $t->string('source', 10); // push, api or import
            $t->timestamps();
            $t->unique(['school_id', 'device_user_id', 'punched_at']);
        });

        Schema::table('schools', function (Blueprint $t) {
            // Strict: staff mark themselves only from the school's network or grounds, and machine times cannot be edited.
            $t->boolean('strict_attendance')->default(false)->after('staff_self_check_in');
            $t->text('check_in_networks')->nullable()->after('strict_attendance');
            $t->decimal('check_in_lat', 10, 7)->nullable()->after('check_in_networks');
            $t->decimal('check_in_lng', 10, 7)->nullable()->after('check_in_lat');
            $t->unsignedSmallInteger('check_in_radius')->default(150)->after('check_in_lng');
        });

        // Payroll and biometric devices become features of their own. A school that had staff switched off
        // keeps payroll off too, and the Basic plan now includes staff attendance.
        foreach (DB::table('schools')->get(['id', 'plan', 'features_off']) as $school) {
            $off = json_decode($school->features_off ?? '[]', true) ?: [];
            if (in_array('staff', $off, true)) {
                $off = array_merge($off, ['payroll', 'biometric']);
            }
            if ($school->plan === 'basic') {
                $off = array_merge(array_diff($off, ['staff']), ['payroll', 'biometric']);
            }
            DB::table('schools')->where('id', $school->id)->update(['features_off' => json_encode(array_values(array_unique($off)))]);
        }

        foreach (DB::table('plans')->get(['id', 'code', 'features']) as $plan) {
            $features = json_decode($plan->features, true) ?: [];
            if (in_array('staff', $features, true)) {
                $features = array_merge($features, ['payroll', 'biometric']);
            }
            if ($plan->code === 'basic') {
                $features[] = 'staff';
            }
            DB::table('plans')->where('id', $plan->id)->update(['features' => json_encode(array_values(array_unique($features)))]);
        }
    }

    public function down(): void
    {
        Schema::table('schools', fn (Blueprint $t) => $t->dropColumn(['strict_attendance', 'check_in_networks', 'check_in_lat', 'check_in_lng', 'check_in_radius']));
        Schema::dropIfExists('biometric_punches');
        Schema::dropIfExists('biometric_devices');
        Schema::table('staff', function (Blueprint $t) {
            $t->dropIndex(['school_id', 'biometric_id']);
            $t->dropColumn('biometric_id');
        });
    }
};
