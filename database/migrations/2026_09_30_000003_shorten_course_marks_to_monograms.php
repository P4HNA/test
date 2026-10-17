<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/*
| A course mark is a monogram, not a word. The card sets it at 6xl over the
| artwork, so a long one overruns the tile and breaks the row: "Trading" sat
| where "Ps" and "Ai" sit. Admin now caps the field at three characters, which
| leaves any longer mark already in the table failing validation the next time
| someone opens that course and saves it.
|
| This shortens them the same way a person would: the first two letters, set
| as a monogram. Courses whose mark already fits are left alone.
|
| Irreversible by nature - "Tr" does not remember it was once "Trading" - so
| down() restores what it can and says so.
*/
return new class extends Migration
{
    /** The one course whose artwork was a screenshot rather than a monogram. */
    private const CANDLES_SLUG = 'form-of-candles-in-ict-basic';

    public function up(): void
    {
        DB::table('courses')
            ->select('id', 'slug', 'mark')
            ->orderBy('id')
            ->chunk(100, function ($courses) {
                foreach ($courses as $course) {
                    $mark = (string) $course->mark;

                    if (mb_strlen($mark) <= 3) {
                        continue;
                    }

                    DB::table('courses')
                        ->where('id', $course->id)
                        ->update(['mark' => $this->monogram($mark, (string) $course->slug)]);
                }
            });

        // The candlestick screenshot was the only photographic tile in a grid
        // of monograms. Clearing the reference puts it back on its own mark;
        // the file itself is left on disk.
        DB::table('courses')
            ->where('slug', self::CANDLES_SLUG)
            ->update(['thumbnail_url' => null]);
    }

    public function down(): void
    {
        // The original spelling is gone, but this one is known and is the only
        // mark the shortening actually touched on a stock install.
        DB::table('courses')
            ->where('slug', self::CANDLES_SLUG)
            ->where('mark', 'Tr')
            ->update(['mark' => 'Trading']);
    }

    /**
     * Two letters, capitalised like the Adobe marks: "Trading" -> "Tr".
     * Falls back to the slug when a mark carries no letters of its own.
     */
    private function monogram(string $mark, string $slug): string
    {
        $letters = preg_replace('/[^\p{L}]/u', '', $mark) ?? '';

        if ($letters === '') {
            $letters = preg_replace('/[^\p{L}]/u', '', $slug) ?? '';
        }

        if ($letters === '') {
            return 'Co';
        }

        return mb_convert_case(mb_substr($letters, 0, 1), MB_CASE_UPPER, 'UTF-8')
            .mb_convert_case(mb_substr($letters, 1, 1), MB_CASE_LOWER, 'UTF-8');
    }
};
