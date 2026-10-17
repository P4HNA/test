<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * Adds a public-facing uuid to every table whose integer id currently shows up
 * in a URL. Primary keys and foreign keys are untouched -- this is additive.
 *
 * Only the route-bound tables are listed. Join and bookkeeping tables
 * (enrollments, lesson_completions, social_accounts, email_change_requests)
 * never appear in a URL, so they keep their integer ids and stay out of this.
 */
return new class extends Migration
{
    /**
     * Ordered as they are routed, not alphabetically, so the list reads like
     * routes/admin.php.
     */
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
            if (! Schema::hasTable($table) || Schema::hasColumn($table, 'uuid')) {
                continue;
            }

            // Nullable on the way in: existing rows have no value yet, and a
            // NOT NULL column with no default cannot be added to a populated
            // table. The backfill below closes the gap and the HasUuid trait
            // fills every row created from here on.
            Schema::table($table, function (Blueprint $t) {
                $t->uuid('uuid')->nullable()->after('id');
            });

            $this->backfill($table);

            // Indexed after the backfill so the index is built once, against
            // final data, rather than being maintained through every update.
            Schema::table($table, function (Blueprint $t) {
                $t->unique('uuid');
            });
        }
    }

    /**
     * Give every existing row a uuid.
     *
     * Done in PHP because no uuid generator is portable across sqlite (used
     * here and in the test suite) and mysql. Chunked by id and updated one row
     * at a time inside a single transaction: each row needs its own distinct
     * value, so there is no set-based version of this.
     */
    private function backfill(string $table): void
    {
        DB::transaction(function () use ($table) {
            DB::table($table)->select('id')->orderBy('id')->chunk(500, function ($rows) use ($table) {
                foreach ($rows as $row) {
                    DB::table($table)
                        ->where('id', $row->id)
                        ->update(['uuid' => (string) Str::uuid()]);
                }
            });
        });
    }

    public function down(): void
    {
        foreach (array_reverse(self::TABLES) as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'uuid')) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) {
                $t->dropUnique(['uuid']);
                $t->dropColumn('uuid');
            });
        }
    }
};
