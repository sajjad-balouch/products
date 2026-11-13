<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Automatic SMS reminders before fee and installment due dates. Messages get a channel
 * (WhatsApp outbox or SMS), the reason a send failed, and a key so the same reminder is
 * never sent twice. Safe to run again: it skips what is already there.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $t) {
            if (! Schema::hasColumn('messages', 'channel')) {
                $t->string('channel', 10)->default('whatsapp')->after('category'); // whatsapp | sms
            }
            if (! Schema::hasColumn('messages', 'customer_id')) {
                $t->unsignedBigInteger('customer_id')->nullable()->after('guardian_id'); // a business customer
            }
            if (! Schema::hasColumn('messages', 'error')) {
                $t->string('error', 255)->nullable()->after('status');
            }
            if (! Schema::hasColumn('messages', 'dedupe_key')) {
                $t->string('dedupe_key', 120)->nullable()->after('error');
            }
        });
        if (! $this->hasIndex('messages', 'messages_school_id_dedupe_key_unique')) {
            Schema::table('messages', fn (Blueprint $t) => $t->unique(['school_id', 'dedupe_key']));
        }
        if (! Schema::hasColumn('schools', 'reminder_settings')) {
            Schema::table('schools', fn (Blueprint $t) => $t->json('reminder_settings')->nullable());
        }
    }

    private function hasIndex(string $table, string $name): bool
    {
        return collect(Schema::getIndexes($table))->contains(fn ($i) => $i['name'] === $name);
    }

    public function down(): void
    {
        if ($this->hasIndex('messages', 'messages_school_id_dedupe_key_unique')) {
            Schema::table('messages', fn (Blueprint $t) => $t->dropUnique(['school_id', 'dedupe_key']));
        }
        Schema::table('messages', function (Blueprint $t) {
            foreach (['channel', 'customer_id', 'error', 'dedupe_key'] as $c) {
                if (Schema::hasColumn('messages', $c)) {
                    $t->dropColumn($c);
                }
            }
        });
        if (Schema::hasColumn('schools', 'reminder_settings')) {
            Schema::table('schools', fn (Blueprint $t) => $t->dropColumn('reminder_settings'));
        }
    }
};
