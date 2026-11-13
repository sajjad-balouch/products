<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * How messages go out, set by the software owner (owner panel → Messaging): a WhatsApp/SMS
 * company's API, the WhatsApp Business (Meta Cloud) API, or your own SMS gateway (an Android
 * phone with a SIM that picks up messages). Safe to run again: it skips what is already there.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('messaging_settings')) {
            Schema::create('messaging_settings', function (Blueprint $t) {
                $t->id();
                $t->string('driver', 20)->default('off'); // off | http | whatsapp_cloud | gateway | log
                $t->text('config')->nullable(); // encrypted: url, token, payload...
                $t->unsignedInteger('school_daily_limit')->default(500); // per school, 0 = no limit
                $t->timestamps();
            });
        }

        if (! Schema::hasTable('gateway_devices')) {
            Schema::create('gateway_devices', function (Blueprint $t) {
                $t->id();
                $t->string('name', 80);
                $t->string('token_hash', 64)->unique();
                $t->string('sim_number', 20)->nullable();
                $t->boolean('is_active')->default(true);
                $t->unsignedInteger('daily_limit')->default(300); // so the network does not block the SIM
                $t->timestamp('last_seen_at')->nullable();
                $t->json('last_status')->nullable(); // battery, signal, app version
                $t->timestamps();
            });
        }

        Schema::table('messages', function (Blueprint $t) {
            if (! Schema::hasColumn('messages', 'device_id')) {
                $t->unsignedBigInteger('device_id')->nullable()->after('channel');
            }
            if (! Schema::hasColumn('messages', 'attempts')) {
                $t->unsignedTinyInteger('attempts')->default(0)->after('dedupe_key');
            }
            if (! Schema::hasColumn('messages', 'claimed_at')) {
                $t->timestamp('claimed_at')->nullable()->after('attempts');
            }
        });
        if (! collect(Schema::getIndexes('messages'))->contains(fn ($i) => $i['name'] === 'messages_channel_status_id_index')) {
            Schema::table('messages', fn (Blueprint $t) => $t->index(['channel', 'status', 'id']));
        }
        // The owner's own test messages belong to no school.
        Schema::table('messages', fn (Blueprint $t) => $t->unsignedBigInteger('school_id')->nullable()->change());

        // Automatic SMS / WhatsApp is a new plan feature: off for existing schools until the owner gives it.
        if (! DB::table('migrations')->where('migration', '2026_11_13_000002_add_messaging_settings_and_sms_gateway')->exists()) {
            foreach (DB::table('schools')->select('id', 'features_off')->get() as $school) {
                $off = json_decode((string) $school->features_off, true) ?: [];
                if (! in_array('sms', $off, true)) {
                    DB::table('schools')->where('id', $school->id)->update(['features_off' => json_encode([...$off, 'sms'])]);
                }
            }
        }
    }

    public function down(): void
    {
        if (collect(Schema::getIndexes('messages'))->contains(fn ($i) => $i['name'] === 'messages_channel_status_id_index')) {
            Schema::table('messages', fn (Blueprint $t) => $t->dropIndex(['channel', 'status', 'id']));
        }
        Schema::table('messages', function (Blueprint $t) {
            foreach (['device_id', 'attempts', 'claimed_at'] as $c) {
                if (Schema::hasColumn('messages', $c)) {
                    $t->dropColumn($c);
                }
            }
        });
        Schema::dropIfExists('gateway_devices');
        Schema::dropIfExists('messaging_settings');
    }
};
