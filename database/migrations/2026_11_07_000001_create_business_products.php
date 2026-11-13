<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Products and stock for a business. Every piece bought is its own stock unit with its own cost,
 * so the profit of each sale uses what that very piece cost (5,000 or 6,000), not an average.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Kinds of product, each with the details its pieces carry: engine no., IMEI, colour...
        Schema::create('biz_product_types', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->string('name', 80);
            $t->json('fields')->nullable(); // [{key, label}]
            $t->timestamps();
            $t->unique(['school_id', 'name']);
        });

        Schema::create('biz_products', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('type_id')->nullable()->constrained('biz_product_types')->nullOnDelete();
            $t->string('code', 30)->nullable();
            $t->string('name', 150);
            $t->string('brand', 80)->nullable();
            $t->unsignedBigInteger('sale_price')->default(0); // the usual cash price
            $t->text('notes')->nullable();
            $t->boolean('is_active')->default(true);
            $t->timestamps();
            $t->unique(['school_id', 'code']);
            $t->index(['school_id', 'name']);
        });

        // One delivery of stock: how many pieces, at what cost each, from whom.
        Schema::create('biz_purchases', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_id')->constrained('biz_products')->restrictOnDelete();
            $t->string('number', 20);
            $t->date('date');
            $t->string('supplier', 120)->nullable();
            $t->unsignedInteger('quantity');
            $t->unsignedBigInteger('unit_cost');
            $t->string('paid_by', 12)->default('cash'); // cash | bank | credit (owed to the supplier)
            $t->string('reference', 60)->nullable();
            $t->foreignId('journal_entry_id')->nullable()->constrained('biz_journal_entries')->nullOnDelete();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->unique(['school_id', 'number']);
        });

        // Every single piece in stock, with its own cost and details (serial, engine no., IMEI...).
        Schema::create('biz_stock_units', function (Blueprint $t) {
            $t->id();
            $t->foreignId('school_id')->constrained()->cascadeOnDelete();
            $t->foreignId('product_id')->constrained('biz_products')->restrictOnDelete();
            $t->foreignId('purchase_id')->constrained('biz_purchases')->cascadeOnDelete();
            $t->unsignedBigInteger('unit_cost');
            $t->json('details')->nullable();
            $t->string('status', 10)->default('in_stock'); // in_stock | sold
            $t->foreignId('contract_id')->nullable()->constrained('biz_contracts')->nullOnDelete();
            $t->date('sold_on')->nullable();
            $t->timestamps();
            $t->index(['school_id', 'product_id', 'status']);
        });

        Schema::table('biz_contracts', function (Blueprint $t) {
            $t->foreignId('product_id')->nullable()->after('customer_id')->constrained('biz_products')->nullOnDelete();
            $t->foreignId('stock_unit_id')->nullable()->after('product_id')->constrained('biz_stock_units')->nullOnDelete();
            $t->json('details')->nullable()->after('item_details');
        });
    }

    public function down(): void
    {
        Schema::table('biz_contracts', function (Blueprint $t) {
            $t->dropConstrainedForeignId('stock_unit_id');
            $t->dropConstrainedForeignId('product_id');
            $t->dropColumn('details');
        });
        foreach (['biz_stock_units', 'biz_purchases', 'biz_products', 'biz_product_types'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
