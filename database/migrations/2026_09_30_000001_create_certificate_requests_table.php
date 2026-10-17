<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // One request per student per course. A rejected request is reopened
        // (same row) when the student sends it again.
        Schema::create('certificate_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->string('real_name');
            $table->string('telegram_username', 64);
            $table->string('status', 16)->default('pending')->index(); // pending | approved | rejected
            $table->text('admin_note')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'course_id']);
        });

        // The name printed on the certificate: the real name the student
        // typed (checked by the admin), not their account name.
        Schema::table('certificates', function (Blueprint $table) {
            $table->string('holder_name')->nullable()->after('code');
        });
    }

    public function down(): void
    {
        Schema::table('certificates', function (Blueprint $table) {
            $table->dropColumn('holder_name');
        });

        Schema::dropIfExists('certificate_requests');
    }
};
