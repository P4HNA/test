<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('module_id')->constrained()->cascadeOnDelete();
            $table->string('title_en');
            $table->string('title_km');
            $table->string('video_host')->default('youtube');
            $table->string('video_url')->nullable();
            $table->unsignedInteger('duration_minutes')->default(0);
            $table->text('notes_en')->nullable();
            $table->text('notes_km')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
