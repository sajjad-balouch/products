<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $t) {
            // Whole PKR, like all money in this system.
            $t->unsignedInteger('monthly_fee')->default(0)->after('trial_ends_at');
            // The last day the school may use the system, whether free or paid.
            $t->date('access_until')->nullable()->after('monthly_fee');
            $t->unsignedTinyInteger('grace_days')->default(7)->after('access_until');
            $t->boolean('lock_when_due')->default(true)->after('grace_days');
            $t->json('features_off')->nullable()->after('lock_when_due');
            $t->text('owner_note')->nullable()->after('features_off');
        });

        DB::table('schools')->update(['access_until' => DB::raw('trial_ends_at')]);

        Schema::table('users', function (Blueprint $t) {
            $t->timestamp('last_login_at')->nullable()->after('remember_token');
        });

        // Every change to a school's access: a payment, free months, or a date set by hand.
        Schema::create('subscription_entries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->string('type', 10); // payment | free | adjust
            $t->unsignedSmallInteger('months')->default(0);
            $t->unsignedInteger('amount')->default(0);
            $t->date('from_date')->nullable();
            $t->date('to_date');
            $t->date('paid_on')->nullable();
            $t->string('method', 20)->nullable();
            $t->string('reference')->nullable();
            $t->string('note')->nullable();
            $t->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_entries');
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('last_login_at'));
        Schema::table('schools', fn (Blueprint $t) => $t->dropColumn(['monthly_fee', 'access_until', 'grace_days', 'lock_when_due', 'features_off', 'owner_note']));
    }
};
