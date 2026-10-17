<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * A lesson marked as a free preview opens for any signed-in student
     * without enrolment or payment. It is the shop window: someone can watch
     * one real lesson before sending money over KHQR. Off for every existing
     * lesson, so nothing becomes free by running this.
     */
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            if (! Schema::hasColumn('lessons', 'is_preview')) {
                $table->boolean('is_preview')->default(false)->after('duration_minutes');
            }
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            if (Schema::hasColumn('lessons', 'is_preview')) {
                $table->dropColumn('is_preview');
            }
        });
    }
};
