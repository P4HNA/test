<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * One row per user holds their currently-pending email change: the
     * requested new address, a hash of the one-time code sent to it, and
     * how many incorrect attempts have been made. The row is deleted as
     * soon as the change is confirmed, cancelled, or invalidated.
     */
    public function up(): void
    {
        Schema::create('email_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('new_email');
            $table->string('code_hash');
            $table->unsignedTinyInteger('attempts')->default(0);
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('email_change_requests');
    }
};
