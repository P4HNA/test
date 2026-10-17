<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
| Lets an admin hide a payment from the lists without destroying it.
|
| Payments are deliberately never deleted: the 2026_09_26_000001 migration keeps
| them when a student or course is removed, and the account-deletion page tells
| people in writing that their payment records are kept for accounting and tax
| purposes. So "delete" in the admin panel sets this timestamp instead -- the row
| leaves the normal view and the money still counts in the totals.
*/
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->timestamp('archived_at')->nullable()->after('paid_at');

            // The lists filter on it on every page load.
            $table->index('archived_at');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['archived_at']);
            $table->dropColumn('archived_at');
        });
    }
};
