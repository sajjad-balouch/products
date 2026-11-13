<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The business product: double-entry accounts and installment sales.
 * A business is a row in schools with type "business". All money is whole PKR.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', function (Blueprint $t) {
            $t->json('business_settings')->nullable()->after('card_settings');
        });

        // Chart of accounts. system_key marks the accounts that sales and collections post to.
        Schema::create('biz_accounts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->string('code', 10);
            $t->string('name', 120);
            $t->string('type', 10); // asset | liability | equity | income | expense
            $t->string('system_key', 30)->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->unique(['school_id', 'code']);
            $t->unique(['school_id', 'system_key']);
        });

        Schema::create('biz_customers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->string('code', 20);
            $t->string('name', 120);
            $t->string('father_name', 120)->nullable();
            $t->string('cnic', 20)->nullable();
            $t->string('phone', 30);
            $t->string('phone2', 30)->nullable();
            $t->string('address')->nullable();
            $t->string('occupation', 100)->nullable();
            $t->string('photo_path')->nullable();
            $t->text('notes')->nullable();
            $t->boolean('is_blacklisted')->default(false);
            $t->timestamps();
            $t->unique(['school_id', 'code']);
            $t->index(['school_id', 'phone']);
            $t->index(['school_id', 'cnic']);
        });

        Schema::create('biz_journal_entries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->string('number', 20);
            $t->date('date');
            $t->string('kind', 12); // journal | receipt | payment | sale | collection | reversal
            $t->string('memo')->nullable();
            $t->nullableMorphs('source');
            $t->foreignId('reversal_of')->nullable()->constrained('biz_journal_entries')->nullOnDelete();
            $t->timestamp('reversed_at')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->unique(['school_id', 'number']);
            $t->index(['school_id', 'date']);
        });

        Schema::create('biz_journal_lines', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('entry_id')->constrained('biz_journal_entries')->cascadeOnDelete();
            $t->foreignId('account_id')->constrained('biz_accounts')->restrictOnDelete();
            $t->unsignedBigInteger('debit')->default(0);
            $t->unsignedBigInteger('credit')->default(0);
            $t->foreignId('customer_id')->nullable()->constrained('biz_customers')->nullOnDelete();
            $t->string('memo')->nullable();
            $t->index(['school_id', 'account_id']);
        });

        // One installment sale: the item, the price, the down payment and the plan.
        Schema::create('biz_contracts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->string('number', 20);
            $t->foreignId('customer_id')->constrained('biz_customers')->restrictOnDelete();
            $t->string('item', 150);
            $t->text('item_details')->nullable(); // model, serial, IMEI, engine/chassis no.
            $t->date('sale_date');
            $t->unsignedBigInteger('cash_price');
            $t->unsignedBigInteger('cost_price')->nullable();
            $t->decimal('markup_percent', 6, 2)->default(0);
            $t->unsignedBigInteger('markup');
            $t->unsignedBigInteger('total_price');
            $t->unsignedBigInteger('down_payment')->default(0);
            $t->unsignedBigInteger('financed');
            $t->unsignedSmallInteger('installments');
            $t->string('frequency', 12)->default('monthly'); // monthly | fortnightly | weekly
            $t->unsignedBigInteger('installment_amount');
            $t->date('first_due_on');
            $t->unsignedInteger('late_fee')->default(0); // per late installment
            $t->unsignedTinyInteger('grace_days')->default(0);
            $t->string('status', 12)->default('active'); // active | completed | written_off
            $t->unsignedBigInteger('markup_recognised')->default(0);
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->text('notes')->nullable();
            $t->timestamp('closed_at')->nullable();
            $t->timestamps();
            $t->unique(['school_id', 'number']);
            $t->index(['school_id', 'status']);
        });

        Schema::create('biz_guarantors', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('contract_id')->constrained('biz_contracts')->cascadeOnDelete();
            $t->string('name', 120);
            $t->string('father_name', 120)->nullable();
            $t->string('cnic', 20)->nullable();
            $t->string('phone', 30)->nullable();
            $t->string('address')->nullable();
            $t->string('relation', 60)->nullable();
            $t->timestamps();
        });

        Schema::create('biz_schedule', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('contract_id')->constrained('biz_contracts')->cascadeOnDelete();
            $t->unsignedSmallInteger('number');
            $t->date('due_on');
            $t->unsignedBigInteger('amount');
            $t->unsignedBigInteger('paid')->default(0);
            $t->date('paid_on')->nullable();
            $t->index(['school_id', 'due_on']);
            $t->index('contract_id');
        });

        Schema::create('biz_receipts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->string('number', 20);
            $t->foreignId('contract_id')->constrained('biz_contracts')->restrictOnDelete();
            $t->date('date');
            $t->boolean('is_down_payment')->default(false);
            $t->unsignedBigInteger('amount'); // towards the installments
            $t->unsignedInteger('late_fee')->default(0);
            $t->unsignedInteger('discount')->default(0);
            $t->string('method', 12)->default('cash'); // cash | bank | jazzcash | easypaisa | cheque
            $t->string('reference', 60)->nullable();
            $t->string('note')->nullable();
            $t->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $t->foreignId('journal_entry_id')->nullable()->constrained('biz_journal_entries')->nullOnDelete();
            $t->timestamp('voided_at')->nullable();
            $t->string('void_reason')->nullable();
            $t->timestamps();
            $t->unique(['school_id', 'number']);
            $t->index(['school_id', 'date']);
        });
    }

    public function down(): void
    {
        foreach (['biz_receipts', 'biz_schedule', 'biz_guarantors', 'biz_contracts', 'biz_journal_lines', 'biz_journal_entries', 'biz_customers', 'biz_accounts'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('schools', fn (Blueprint $t) => $t->dropColumn('business_settings'));
    }
};
