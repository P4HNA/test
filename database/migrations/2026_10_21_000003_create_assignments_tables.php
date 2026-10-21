<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Course assignments and what students hand in for them. Every upload is its
 * own row (v1, v2 ...), so the version history and the teacher's notes on
 * each version are kept.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('number')->default(1);
            $table->string('title_en', 160);
            $table->string('title_km', 160)->nullable();
            $table->text('brief_en')->nullable();
            $table->text('brief_km')->nullable();
            $table->json('requirements')->nullable();
            $table->string('format', 120)->nullable();
            $table->string('cover_url', 2048)->nullable();
            $table->string('brief_path')->nullable();
            $table->string('brief_name')->nullable();
            $table->timestamp('due_at')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->index(['course_id', 'number']);
        });

        Schema::create('assignment_submissions', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('assignment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('version')->default(1);
            $table->string('file_path')->nullable();
            $table->string('file_name')->nullable();
            $table->unsignedBigInteger('file_size')->default(0);
            $table->string('file_mime', 120)->nullable();
            $table->string('link_url', 2048)->nullable();
            $table->text('note')->nullable();
            $table->string('status', 20)->default('submitted');
            $table->decimal('grade', 4, 1)->nullable();
            $table->text('feedback_en')->nullable();
            $table->text('feedback_km')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['assignment_id', 'user_id', 'version']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_submissions');
        Schema::dropIfExists('assignments');
    }
};
