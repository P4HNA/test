<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| When PayWay reports an amount that does not match the order, the payment is
| held at status "review" and the course stays locked. That is the right call --
| but until now nothing told a human it had happened, so a customer whose money
| really did move sat locked out until somebody happened to look at the admin
| list.
|
| review_notified_at is how the alert is kept to exactly one email, the same way
| receipt_sent_at keeps the receipt to one: claimed with a single conditional
| UPDATE that two racing processes cannot both win. PayWay retries its callback
| and the page polls alongside it, so "have we already warned someone?" cannot be
| answered from memory.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->timestamp('review_notified_at')->nullable()->after('receipt_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('review_notified_at');
        });
    }
};
