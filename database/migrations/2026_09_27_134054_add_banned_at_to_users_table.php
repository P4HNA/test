<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Banning is soft: the account stays (so its enrollments, payments and
| certificates keep their history), but the person can no longer sign in,
| by password or by Google. See User::isBanned() and the login checks in
| LoginRequest and SocialAuthController.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('banned_at')->nullable()->after('role');
            $table->string('ban_reason')->nullable()->after('banned_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['banned_at', 'ban_reason']);
        });
    }
};
