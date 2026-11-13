<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Categories, brands with their models, and cash / wholesale sale invoices for a business.
 * An invoice can sell many products at once, each piece still at its own cost.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('biz_categories', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->string('name', 80);
            $t->timestamps();
            $t->unique(['school_id', 'name']);
        });

        // Brands belong to a product type (Samsung for mobiles, Honda for motorcycles), or to none.
        Schema::create('biz_brands', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('type_id')->nullable()->constrained('biz_product_types')->nullOnDelete();
            $t->string('name', 80);
            $t->timestamps();
            $t->unique(['school_id', 'type_id', 'name']);
        });

        Schema::create('biz_brand_models', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('brand_id')->constrained('biz_brands')->cascadeOnDelete();
            $t->string('name', 100);
            $t->timestamps();
            $t->unique(['brand_id', 'name']);
        });

        Schema::table('biz_products', function (Blueprint $t) {
            $t->foreignId('category_id')->nullable()->after('type_id')->constrained('biz_categories')->nullOnDelete();
            $t->foreignId('brand_id')->nullable()->after('category_id')->constrained('biz_brands')->nullOnDelete();
            $t->foreignId('model_id')->nullable()->after('brand_id')->constrained('biz_brand_models')->nullOnDelete();
            $t->string('variant', 80)->nullable()->after('name'); // 8/256, Black, 2025
            $t->unsignedBigInteger('wholesale_price')->default(0)->after('sale_price');
        });

        // A cash, bank or credit (udhaar) sale of one or more products.
        Schema::create('biz_invoices', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->string('number', 20);
            $t->date('date');
            $t->foreignId('customer_id')->nullable()->constrained('biz_customers')->nullOnDelete();
            $t->string('customer_name', 120)->nullable(); // a walk-in buyer
            $t->unsignedBigInteger('subtotal');
            $t->unsignedBigInteger('discount')->default(0);
            $t->unsignedBigInteger('total');
            $t->unsignedBigInteger('cost')->default(0);
            $t->unsignedBigInteger('paid')->default(0);
            $t->string('notes')->nullable();
            $t->foreignId('journal_entry_id')->nullable()->constrained('biz_journal_entries')->nullOnDelete();
            $t->timestamp('voided_at')->nullable();
            $t->string('void_reason')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->unique(['school_id', 'number']);
            $t->index(['school_id', 'date']);
        });

        Schema::create('biz_invoice_lines', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('invoice_id')->constrained('biz_invoices')->cascadeOnDelete();
            $t->foreignId('product_id')->constrained('biz_products')->restrictOnDelete();
            $t->unsignedInteger('quantity');
            $t->unsignedBigInteger('unit_price');
            $t->unsignedBigInteger('total');
            $t->unsignedBigInteger('cost')->default(0); // what the pieces sold cost
            $t->text('details')->nullable(); // IMEIs / serials of the pieces
        });

        Schema::create('biz_invoice_payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('invoice_id')->constrained('biz_invoices')->cascadeOnDelete();
            $t->date('date');
            $t->unsignedBigInteger('amount');
            $t->string('method', 12)->default('cash');
            $t->string('reference', 60)->nullable();
            $t->foreignId('journal_entry_id')->nullable()->constrained('biz_journal_entries')->nullOnDelete();
            $t->foreignId('received_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index(['school_id', 'date']);
        });

        Schema::table('biz_stock_units', function (Blueprint $t) {
            $t->foreignId('invoice_line_id')->nullable()->after('contract_id')->constrained('biz_invoice_lines')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('biz_stock_units', fn (Blueprint $t) => $t->dropConstrainedForeignId('invoice_line_id'));
        foreach (['biz_invoice_payments', 'biz_invoice_lines', 'biz_invoices'] as $table) {
            Schema::dropIfExists($table);
        }
        Schema::table('biz_products', function (Blueprint $t) {
            $t->dropConstrainedForeignId('model_id');
            $t->dropConstrainedForeignId('brand_id');
            $t->dropConstrainedForeignId('category_id');
            $t->dropColumn(['variant', 'wholesale_price']);
        });
        foreach (['biz_brand_models', 'biz_brands', 'biz_categories'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
