<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            if (! Schema::hasColumn('lessons', 'video_path')) {
                $table->string('video_path')->nullable();   // file uploaded from the admin panel
            }

            if (! Schema::hasColumn('lessons', 'video_size')) {
                $table->unsignedBigInteger('video_size')->nullable();   // bytes
            }
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            if (Schema::hasColumn('lessons', 'video_path')) {
                $table->dropColumn('video_path');
            }

            if (Schema::hasColumn('lessons', 'video_size')) {
                $table->dropColumn('video_size');
            }
        });
    }
};
