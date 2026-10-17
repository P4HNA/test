<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Lets an admin build a course out of sight. A draft is left out of the
| catalogue and cannot be enrolled in or bought; students who already have
| it keep their access. Every course that exists today stays published.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->boolean('is_published')->default(true)->after('is_featured');
        });
    }

    public function down(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->dropColumn('is_published');
        });
    }
};
