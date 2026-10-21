<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The Resources library: design files the teacher shares (PSD mockups, vector
 * kits, fonts, Figma kits...) and the students' bookmarks on them.
 *
 * A resource is either an uploaded file (kept on the private disk and only
 * handed out through the download route, so it can be counted and needs a
 * signed-in student) or a link to where it lives (Google Drive, Figma
 * Community...).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('design_resources', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('title_en', 160);
            $table->string('title_km', 160)->nullable();
            $table->text('description_en')->nullable();
            $table->text('description_km')->nullable();
            $table->string('type', 80);
            $table->string('software', 40);
            $table->string('category', 40);
            $table->json('tags')->nullable();
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('external_url', 2048)->nullable();
            $table->string('thumbnail_url', 2048)->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('downloads_count')->default(0);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['is_published', 'sort_order']);
        });

        Schema::create('design_resource_bookmarks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('design_resource_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'design_resource_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('design_resource_bookmarks');
        Schema::dropIfExists('design_resources');
    }
};
