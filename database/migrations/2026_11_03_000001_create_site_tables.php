<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One public website per school, plus the software owner's own site where school_id is null.
        Schema::create('sites', function (Blueprint $t) {
            $t->id();
            $t->string('owner_key')->unique();
            $t->foreignId('school_id')->nullable()->constrained()->cascadeOnDelete();
            $t->boolean('is_published')->default(false);
            $t->json('settings')->nullable();
            $t->timestamps();
        });

        Schema::create('site_pages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('site_id')->constrained()->cascadeOnDelete();
            $t->string('title');
            $t->string('slug');
            $t->boolean('is_home')->default(false);
            $t->boolean('is_published')->default(true);
            $t->text('seo_description')->nullable();
            $t->unsignedInteger('sort')->default(0);
            $t->timestamps();
            $t->unique(['site_id', 'slug']);
        });

        Schema::create('site_sections', function (Blueprint $t) {
            $t->id();
            $t->foreignId('site_page_id')->constrained()->cascadeOnDelete();
            $t->string('type', 30);
            $t->json('data')->nullable();
            $t->boolean('is_visible')->default(true);
            $t->unsignedInteger('sort')->default(0);
            $t->timestamps();
        });

        Schema::create('site_menu_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('site_id')->constrained()->cascadeOnDelete();
            $t->string('location', 10);
            $t->string('label');
            $t->foreignId('site_page_id')->nullable()->constrained()->cascadeOnDelete();
            $t->string('url', 500)->nullable();
            $t->boolean('new_tab')->default(false);
            $t->boolean('is_visible')->default(true);
            $t->unsignedInteger('sort')->default(0);
            $t->timestamps();
        });

        Schema::create('site_media', function (Blueprint $t) {
            $t->id();
            $t->foreignId('site_id')->constrained()->cascadeOnDelete();
            $t->string('path');
            $t->string('original_name')->nullable();
            $t->timestamps();
        });

        Schema::create('site_messages', function (Blueprint $t) {
            $t->id();
            $t->foreignId('site_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->string('phone', 30)->nullable();
            $t->string('email')->nullable();
            $t->text('message');
            $t->timestamp('read_at')->nullable();
            $t->timestamps();
        });

        // What a user other than the owner may manage: an area name, or page:{id} for one page.
        Schema::create('site_permissions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('site_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete();
            $t->string('permission', 40);
            $t->timestamps();
            $t->unique(['site_id', 'user_id', 'permission']);
        });
    }

    public function down(): void
    {
        foreach (['site_permissions', 'site_messages', 'site_media', 'site_menu_items', 'site_sections', 'site_pages', 'sites'] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
