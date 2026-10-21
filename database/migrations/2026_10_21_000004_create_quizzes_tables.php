<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Course quizzes: multiple-choice questions, and each student's scored attempts. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quizzes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('title_en', 160);
            $table->string('title_km', 160)->nullable();
            $table->string('topic', 160)->nullable();
            $table->string('cover_url', 2048)->nullable();
            $table->unsignedTinyInteger('pass_percent')->default(70);
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->text('prompt_en');
            $table->text('prompt_km')->nullable();
            $table->json('options');
            $table->unsignedTinyInteger('correct_index');
            $table->text('explanation_en')->nullable();
            $table->text('explanation_km')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('quiz_attempts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('quiz_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->json('answers');
            $table->unsignedSmallInteger('correct')->default(0);
            $table->unsignedSmallInteger('total')->default(0);
            $table->unsignedTinyInteger('score')->default(0);
            $table->boolean('passed')->default(false);
            $table->timestamps();

            $table->index(['quiz_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_attempts');
        Schema::dropIfExists('quiz_questions');
        Schema::dropIfExists('quizzes');
    }
};
