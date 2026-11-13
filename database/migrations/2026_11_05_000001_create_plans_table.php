<?php

use App\Models\School;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A package the owner sells. schools.plan holds its code, or "trial".
        Schema::create('plans', function (Blueprint $t) {
            $t->id();
            $t->string('code', 40)->unique();
            $t->string('name');
            $t->unsignedInteger('monthly_price')->default(0);
            $t->json('features');
            $t->string('description')->nullable();
            $t->unsignedInteger('sort')->default(0);
            $t->timestamps();
        });

        // The two labels schools already used. Prices start at zero for the owner to set.
        $all = array_keys(School::FEATURES);
        $basic = ['admissions', 'attendance', 'diary', 'timetable', 'exams', 'fees', 'certificates', 'messages', 'portal'];
        DB::table('plans')->insert([
            ['code' => 'basic', 'name' => 'Basic', 'monthly_price' => 0, 'features' => json_encode($basic), 'description' => 'Everyday school office', 'sort' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'pro', 'name' => 'Pro', 'monthly_price' => 0, 'features' => json_encode($all), 'description' => 'Every feature, including the website', 'sort' => 2, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
