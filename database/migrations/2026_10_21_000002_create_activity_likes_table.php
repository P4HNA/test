<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Likes on the Activity page. A post there is built from real records (a new
 * lesson, a certificate, finished lessons...), so a like points at the post's
 * key, like "lesson:12" or "cert:4", rather than at a posts table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('activity_key', 80);
            $table->timestamps();

            $table->unique(['user_id', 'activity_key']);
            $table->index('activity_key');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_likes');
    }
};
