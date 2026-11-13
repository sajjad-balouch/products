<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Plans belong to one product: school management or the business (installments and accounts) product.
        Schema::table('plans', fn (Blueprint $t) => $t->string('product', 12)->default('school')->after('code'));

        if (! DB::table('plans')->where('code', 'business')->exists()) {
            DB::table('plans')->insert([
                'code' => 'business', 'product' => 'business', 'name' => 'Business', 'monthly_price' => 0,
                'features' => json_encode(['installments', 'accounts', 'messages', 'website']),
                'description' => 'Installment sales, recovery and full accounts',
                'sort' => (int) DB::table('plans')->max('sort') + 1, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('plans')->where('code', 'business')->delete();
        Schema::table('plans', fn (Blueprint $t) => $t->dropColumn('product'));
    }
};
