<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Course prices used to be whole dollars (unsigned integer), so the cheapest
| paid course was $1. Now the price keeps cents, so $0.01 is possible.
| Existing prices are kept as they are (35 becomes 35.00).
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('courses', function (Blueprint $table) {
            $table->decimal('price', 8, 2)->default(0)->change();
        });
    }

    public function down(): void
    {
        // Going back drops the cents (0.01 becomes 0, which means free).
        Schema::table('courses', function (Blueprint $table) {
            $table->unsignedInteger('price')->default(0)->change();
        });
    }
};
