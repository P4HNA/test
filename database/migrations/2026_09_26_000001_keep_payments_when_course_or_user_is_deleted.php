<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
| Payments used to cascade away with their user and course, so deleting a
| student or a course silently destroyed the record of money that really
| changed hands -- the one thing a shop must never lose.
|
| Now the row stays. The two links become nullable and null out on delete
| instead of taking the payment with them, and a snapshot of who paid for what
| is copied onto the payment itself, so an orphaned row still reads correctly
| in the books long after the student or course is gone.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('payer_name')->nullable();
            $table->string('payer_email')->nullable();
            $table->string('course_title')->nullable();
        });

        $this->backfillSnapshots();

        // SQLite rebuilds the whole table for each of these, so they are kept in
        // separate statements: dropping the old constraints, widening the
        // columns, and adding the new constraints must not collide in one
        // alteration. Foreign keys are dropped by column, which is the only
        // form SQLite supports.
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['course_id']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->unsignedBigInteger('course_id')->nullable()->change();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
            $table->foreign('course_id')->references('id')->on('courses')->nullOnDelete();
        });
    }

    public function down(): void
    {
        // The old shape cannot hold a payment whose user or course is gone, so
        // going back drops exactly the rows this migration was written to save.
        DB::table('payments')->whereNull('user_id')->orWhereNull('course_id')->delete();

        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['course_id']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->unsignedBigInteger('course_id')->nullable(false)->change();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('course_id')->references('id')->on('courses')->cascadeOnDelete();
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['payer_name', 'payer_email', 'course_title']);
        });
    }

    /**
     * Copy the payer and course names onto payments that predate the snapshot
     * columns. Unquoted identifiers keep the correlated subqueries valid on
     * both SQLite and MySQL.
     */
    private function backfillSnapshots(): void
    {
        DB::table('payments')->update([
            'payer_name' => DB::raw('(select name from users where users.id = payments.user_id)'),
            'payer_email' => DB::raw('(select email from users where users.id = payments.user_id)'),
            'course_title' => DB::raw('(select title_en from courses where courses.id = payments.course_id)'),
        ]);
    }
};
