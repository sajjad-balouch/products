<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Vendors (suppliers) and employees (including salesmen) for businesses. Every books line can
 * name the vendor or employee it belongs to, the way it already names the customer, so each
 * one has their own running balance: what you owe a vendor, what an employee was paid.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('biz_vendors', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->string('code', 20);
            $t->string('name', 120);
            $t->string('company', 120)->nullable();
            $t->string('phone', 30)->nullable();
            $t->string('address')->nullable();
            $t->text('notes')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->unique(['school_id', 'code']);
            $t->index(['school_id', 'name']);
        });

        Schema::create('biz_employees', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->string('code', 20);
            $t->string('name', 120);
            $t->string('father_name', 120)->nullable();
            $t->string('cnic', 20)->nullable();
            $t->string('phone', 30)->nullable();
            $t->string('address')->nullable();
            $t->string('designation', 80)->nullable();
            $t->boolean('is_salesman')->default(false);
            $t->unsignedInteger('salary')->default(0);           // monthly, in rupees
            $t->decimal('commission_percent', 5, 2)->default(0);  // on the sales they make
            $t->date('joined_on')->nullable();
            $t->text('notes')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->unique(['school_id', 'code']);
        });

        Schema::table('biz_journal_lines', function (Blueprint $t) {
            $t->foreignId('vendor_id')->nullable()->after('customer_id')->constrained('biz_vendors')->nullOnDelete();
            $t->foreignId('employee_id')->nullable()->after('vendor_id')->constrained('biz_employees')->nullOnDelete();
        });
        Schema::table('biz_purchases', function (Blueprint $t) {
            $t->foreignId('vendor_id')->nullable()->after('supplier')->constrained('biz_vendors')->nullOnDelete();
        });
        foreach (['biz_invoices', 'biz_contracts'] as $table) {
            Schema::table($table, function (Blueprint $t) {
                $t->foreignId('salesman_id')->nullable()->after('customer_id')->constrained('biz_employees')->nullOnDelete();
            });
        }

        // The accounts staff payments use, for businesses that already exist.
        $now = now();
        foreach (DB::table('schools')->where('type', 'business')->pluck('id') as $school) {
            DB::table('biz_accounts')->where('school_id', $school)->where('code', '5200')->whereNull('system_key')->update(['system_key' => 'salaries']);
            foreach ([['1350', 'Staff advances', 'asset', 'staff_advance'], ['5250', 'Sales commission', 'expense', 'commission']] as [$code, $name, $type, $key]) {
                $exists = DB::table('biz_accounts')->where('school_id', $school)->where(fn ($q) => $q->where('code', $code)->orWhere('system_key', $key))->exists();
                if (! $exists) {
                    DB::table('biz_accounts')->insert(['school_id' => $school, 'code' => $code, 'name' => $name, 'type' => $type, 'system_key' => $key, 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
                }
            }
        }
    }

    public function down(): void
    {
        foreach (['biz_invoices', 'biz_contracts'] as $table) {
            Schema::table($table, fn (Blueprint $t) => $t->dropConstrainedForeignId('salesman_id'));
        }
        Schema::table('biz_purchases', fn (Blueprint $t) => $t->dropConstrainedForeignId('vendor_id'));
        Schema::table('biz_journal_lines', function (Blueprint $t) {
            $t->dropConstrainedForeignId('employee_id');
            $t->dropConstrainedForeignId('vendor_id');
        });
        Schema::dropIfExists('biz_employees');
        Schema::dropIfExists('biz_vendors');
    }
};
