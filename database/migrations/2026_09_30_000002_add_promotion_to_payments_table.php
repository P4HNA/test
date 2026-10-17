<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| When a payment was made at a promotional price, keep the regular price and
| the promotion next to it, so the books explain why $28 was charged for a $35
| course. Both stay null for full-price payments.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->decimal('list_price', 10, 2)->nullable()->after('amount');
            $table->foreignId('promotion_id')->nullable()->after('list_price')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropConstrainedForeignId('promotion_id');
            $table->dropColumn('list_price');
        });
    }
};
