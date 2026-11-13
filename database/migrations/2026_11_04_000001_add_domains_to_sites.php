<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Web addresses for school websites. Only the software owner sets these, since the owner buys and sets up the domains.
        Schema::table('sites', function (Blueprint $t) {
            $t->string('subdomain', 63)->nullable()->unique()->after('school_id');
            $t->string('domain', 190)->nullable()->unique()->after('subdomain');
            // none: no domain. waiting: entered, DNS or SSL not ready yet. live: links and old addresses move to it.
            $t->string('domain_status', 10)->default('none')->after('domain');
            $t->json('domain_check')->nullable()->after('domain_status');
            $t->string('domain_registrar', 100)->nullable()->after('domain_check');
            $t->date('domain_expires_on')->nullable()->after('domain_registrar');
            $t->text('domain_notes')->nullable()->after('domain_expires_on');
        });
    }

    public function down(): void
    {
        Schema::table('sites', function (Blueprint $t) {
            $t->dropUnique(['subdomain']);
            $t->dropUnique(['domain']);
            $t->dropColumn(['subdomain', 'domain', 'domain_status', 'domain_check', 'domain_registrar', 'domain_expires_on', 'domain_notes']);
        });
    }
};
