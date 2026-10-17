<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Everything /my-profile shows about how a student has been working: hours put
 * in, the day-by-day activity grid, streaks, and the badges those add up to.
 *
 * All of it is derived from lesson_completions -- nothing here is stored, so a
 * student can never end up with a streak or a badge the record doesn't back up.
 * One query on construction feeds every method.
 */
final class LearningStats
{
    /** Weeks of history the activity grid shows. */
    public const WEEKS = 26;

    /** @var Collection<string, int> 'Y-m-d' => lessons finished that day */
    private readonly Collection $byDay;

    private readonly int $minutes;

    public function __construct(private readonly User $user)
    {
        $completions = $this->user->lessonCompletions()
            ->with('lesson:id,duration_minutes')
            ->get();

        $this->byDay = $completions
            // completed_at is nullable on old rows; fall back to the insert time.
            ->groupBy(fn ($completion) => ($completion->completed_at ?? $completion->created_at)?->toDateString() ?? '')
            ->forget('')
            ->map(fn (Collection $group) => $group->count());

        $this->minutes = (int) $completions->sum(fn ($completion) => $completion->lesson?->duration_minutes ?? 0);
    }

    /** Minutes of lesson material the student has actually finished. */
    public function minutesLearned(): int
    {
        return $this->minutes;
    }

    /** The same figure as "17.1h", ready to print. */
    public function hoursLearned(): string
    {
        return number_format($this->minutes / 60, 1);
    }

    /**
     * Consecutive days finishing at least one lesson, counting back from today.
     * Yesterday still counts as live, so a streak doesn't die at midnight
     * before the student has had a chance to open a lesson.
     */
    public function currentStreak(): int
    {
        $cursor = Carbon::today();

        if (! $this->byDay->has($cursor->toDateString())) {
            $cursor = $cursor->subDay();

            if (! $this->byDay->has($cursor->toDateString())) {
                return 0;
            }
        }

        $streak = 0;

        while ($this->byDay->has($cursor->toDateString())) {
            $streak++;
            $cursor = $cursor->subDay();
        }

        return $streak;
    }

    /** The best run the student has ever had, so a broken streak still shows something. */
    public function longestStreak(): int
    {
        $dates = $this->byDay->keys()->sort()->values();

        $best = 0;
        $run = 0;
        $previous = null;

        foreach ($dates as $date) {
            $day = Carbon::parse($date);
            $run = ($previous && $previous->copy()->addDay()->isSameDay($day)) ? $run + 1 : 1;
            $best = max($best, $run);
            $previous = $day;
        }

        return $best;
    }

    public function activeDays(): int
    {
        return $this->byDay->count();
    }

    /**
     * The activity grid, as full weeks running Monday-Sunday and ending with the
     * week containing today. Each cell carries the date, how many lessons were
     * finished, and a 0-4 intensity so the template doesn't do arithmetic.
     *
     * @return array<int, array<int, array{date: string, label: string, count: int, level: int, future: bool}>>
     */
    public function activityGrid(): array
    {
        $today = Carbon::today();
        $end = $today->copy()->endOfWeek(Carbon::SUNDAY);
        $start = $end->copy()->subWeeks(self::WEEKS - 1)->startOfWeek(Carbon::MONDAY);

        // A day is "busy" relative to this student's own best day, so someone
        // doing two lessons a day doesn't see a flat grid.
        $busiest = max(1, (int) $this->byDay->max());

        $weeks = [];
        $cursor = $start->copy();

        while ($cursor <= $end) {
            $week = [];

            for ($i = 0; $i < 7; $i++) {
                $date = $cursor->toDateString();
                $count = (int) ($this->byDay[$date] ?? 0);

                $week[] = [
                    'date' => $date,
                    'label' => $cursor->isoFormat('D MMM YYYY'),
                    'count' => $count,
                    'level' => $count === 0 ? 0 : (int) max(1, ceil($count / $busiest * 4)),
                    'future' => $cursor->greaterThan($today),
                ];

                $cursor = $cursor->addDay();
            }

            $weeks[] = $week;
        }

        return $weeks;
    }

    /**
     * Milestones the student has passed, plus the next one still to go so the
     * row never reads as finished. Labels stay untranslated here; the template
     * runs them through __().
     *
     * @return array<int, array{icon: string, label: string, note: string, earned: bool}>
     */
    public function badges(): array
    {
        $lessons = (int) $this->byDay->sum();
        $courses = $this->user->enrollments->count();
        $certificates = $this->user->certificates->count();
        $streak = max($this->currentStreak(), $this->longestStreak());
        $hours = $this->minutes / 60;

        $all = [
            ['icon' => 'book-open', 'label' => 'First steps', 'note' => 'Enrolled in a course', 'earned' => $courses >= 1],
            ['icon' => 'check-circle', 'label' => 'Off the mark', 'note' => 'Finished your first lesson', 'earned' => $lessons >= 1],
            ['icon' => 'layers', 'label' => 'Ten down', 'note' => 'Finished 10 lessons', 'earned' => $lessons >= 10],
            ['icon' => 'pulse', 'label' => 'On a roll', 'note' => 'Worked 7 days in a row', 'earned' => $streak >= 7],
            ['icon' => 'clock', 'label' => 'Ten hours in', 'note' => 'Ten hours of lessons finished', 'earned' => $hours >= 10],
            ['icon' => 'graduation', 'label' => 'Graduate', 'note' => 'Earned your first certificate', 'earned' => $certificates >= 1],
            ['icon' => 'star', 'label' => 'Regular', 'note' => 'Worked 30 days in a row', 'earned' => $streak >= 30],
            ['icon' => 'award', 'label' => 'Collector', 'note' => 'Earned 3 certificates', 'earned' => $certificates >= 3],
        ];

        $earned = array_values(array_filter($all, fn (array $badge) => $badge['earned']));
        $locked = array_values(array_filter($all, fn (array $badge) => ! $badge['earned']));

        // Everything won, then the one to aim at next -- never the whole locked list.
        return array_merge($earned, array_slice($locked, 0, 1));
    }

    /**
     * The last few things the student finished, newest first, for the activity
     * feed. Returns the completion rows with lesson and course already loaded.
     */
    public function recentActivity(int $limit = 6): Collection
    {
        return $this->user->lessonCompletions()
            ->with('lesson.module.course')
            ->orderByDesc('completed_at')
            ->limit($limit)
            ->get()
            ->filter(fn ($completion) => $completion->lesson?->module?->course !== null)
            ->values();
    }
}
