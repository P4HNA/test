<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('courses', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title_en');
            $table->string('title_km');
            $table->string('mark', 16);
            $table->string('category');
            $table->string('level');
            $table->unsignedInteger('price')->default(0);
            $table->string('access_type')->default('free');
            $table->string('thumbnail_url')->nullable();
            $table->text('description_en');
            $table->text('description_km')->nullable();
            $table->string('instructor_name')->default('Piseth Yoeurn');
            $table->string('instructor_title')->nullable();
            $table->json('outcomes')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courses');
    }
};
