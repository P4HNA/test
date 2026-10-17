<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Two things the payment row now has to remember.
|
| receipt_sent_at: the receipt must go out exactly once. PayWay retries its
| callback, and the page polls alongside it, so "have we already emailed this
| person?" cannot be answered from memory -- it is claimed with a single
| conditional UPDATE on this column, which two racing processes cannot both win.
|
| refunded_at: a refund is not a deletion. The payment stays in the books with
| status "refunded" and this timestamp, while the enrollment is removed so the
| course closes again.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->timestamp('receipt_sent_at')->nullable()->after('paid_at');
            $table->timestamp('refunded_at')->nullable()->after('receipt_sent_at');
        });

        // The sweeper looks for unpaid rows by status and age on a timer.
        Schema::table('payments', function (Blueprint $table) {
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['status', 'created_at']);
            $table->dropColumn(['receipt_sent_at', 'refunded_at']);
        });
    }
};
