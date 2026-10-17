<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Fills in any public-facing uuid that is still empty.
 *
 * 2026_09_30_000004 added the column and backfilled every row that existed
 * then, and the HasUuid trait fills every row created after it. For a while the
 * models had lost that trait, so rows written in between were saved with no
 * uuid at all -- and a record with no uuid cannot be routed: its admin link
 * renders as /admin/payments//archive and answers 404.
 *
 * Safe to run against a database that has none of them: it writes only where
 * the column is null, so a healthy database sees no writes.
 */
return new class extends Migration
{
    /** Same list, and the same order, as 2026_09_30_000004. */
    private const TABLES = [
        'users',
        'courses',
        'modules',
        'lessons',
        'payments',
        'certificates',
        'promotions',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'uuid')) {
                continue;
            }

            // In PHP, one row at a time, for the same reason as the original
            // backfill: each row needs its own distinct value, and no uuid
            // generator is portable across sqlite and mysql.
            DB::transaction(function () use ($table) {
                DB::table($table)
                    ->whereNull('uuid')
                    ->select('id')
                    ->orderBy('id')
                    ->chunk(500, function ($rows) use ($table) {
                        foreach ($rows as $row) {
                            DB::table($table)
                                ->where('id', $row->id)
                                ->whereNull('uuid')
                                ->update(['uuid' => (string) Str::uuid()]);
                        }
                    });
            });
        }
    }

    /**
     * Nothing to undo. Clearing these again would only put back rows that
     * cannot be opened, and there is no record of which ones were empty.
     */
    public function down(): void
    {
        //
    }
};
