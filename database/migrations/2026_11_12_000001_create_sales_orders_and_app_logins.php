<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Orders that salesmen take in the field on the phone app, even offline, and that reach the
 * owner, who turns them into sales. App logins use their own long-lived tokens, so a salesman
 * who was offline for days is still signed in when the phone sends the orders.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('biz_employees', function (Blueprint $t) {
            $t->foreignId('user_id')->nullable()->after('school_id')->constrained('users')->nullOnDelete();
        });

        Schema::create('biz_app_tokens', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->char('token_hash', 64)->unique();
            $t->string('device', 120)->nullable();
            $t->timestamp('last_used_at')->nullable();
            $t->timestamps();
        });

        Schema::create('biz_orders', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->string('number', 20);
            // The phone makes this id, so an order sent twice (weak signal) is saved once.
            $t->uuid('client_uuid');
            $t->foreignId('customer_id')->nullable()->constrained('biz_customers')->nullOnDelete();
            $t->foreignId('salesman_id')->nullable()->constrained('biz_employees')->nullOnDelete();
            $t->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $t->string('price_list', 10)->default('wholesale'); // retail | wholesale
            $t->unsignedBigInteger('total')->default(0);
            $t->string('status', 12)->default('new');           // new | accepted | delivered | cancelled
            $t->string('notes', 500)->nullable();
            $t->timestamp('ordered_at');                         // on the phone, which may be before it reached us
            $t->decimal('lat', 10, 7)->nullable();
            $t->decimal('lng', 10, 7)->nullable();
            $t->foreignId('invoice_id')->nullable()->constrained('biz_invoices')->nullOnDelete();
            $t->string('cancel_reason')->nullable();
            $t->timestamp('seen_at')->nullable();
            $t->timestamps();
            $t->unique(['school_id', 'number']);
            $t->unique(['school_id', 'client_uuid']);
            $t->index(['school_id', 'status']);
        });

        Schema::create('biz_order_lines', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('order_id')->constrained('biz_orders')->cascadeOnDelete();
            $t->foreignId('product_id')->nullable()->constrained('biz_products')->nullOnDelete();
            $t->string('label', 200);
            $t->unsignedInteger('quantity');
            $t->unsignedBigInteger('unit_price');
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('biz_order_lines');
        Schema::dropIfExists('biz_orders');
        Schema::dropIfExists('biz_app_tokens');
        Schema::table('biz_employees', fn (Blueprint $t) => $t->dropConstrainedForeignId('user_id'));
    }
};
