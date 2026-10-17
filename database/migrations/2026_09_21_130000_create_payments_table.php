<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('invoice_no')->unique();
            $table->decimal('amount', 12, 2);
            $table->string('currency', 3)->default('USD');
            $table->text('qr');
            $table->string('md5', 32)->index();
            $table->string('transaction_hash')->nullable()->unique();
            $table->string('from_account_id')->nullable();
            $table->string('to_account_id')->nullable();
            $table->string('status')->default('pending')->index();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->text('description')->nullable();
            $table->json('gateway_response')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'course_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
