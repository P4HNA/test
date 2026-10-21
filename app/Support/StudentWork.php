<?php

namespace App\Support;

use App\Models\Assignment;
use App\Models\Quiz;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * A student's assignments and quizzes: the ones open to them, where they
 * stand on each, and the one that needs them next (the "#03" in the menu).
 * Teachers see every assignment and quiz.
 */
class StudentWork
{
    /** @return Collection<int, Assignment> soonest deadline first */
    public static function assignments(User $user): Collection
    {
        return Assignment::query()
            ->with('course')
            ->unless($user->isAdmin(), fn ($q) => $q->published()->whereIn('course_id', $user->enrollments()->select('course_id')))
            ->orderByRaw('due_at is null')->orderBy('due_at')->orderBy('number')
            ->get();
    }

    /** The newest version handed in for each assignment, keyed by assignment id. */
    public static function latestSubmissions(User $user, Collection $assignments): Collection
    {
        return $user->assignmentSubmissions()
            ->with('reviewer')
            ->whereIn('assignment_id', $assignments->pluck('id'))
            ->orderByDesc('version')
            ->get()
            ->unique('assignment_id')
            ->keyBy('assignment_id');
    }

    /** @return Collection<int, Quiz> */
    public static function quizzes(User $user): Collection
    {
        return Quiz::query()
            ->with('course')->withCount('questions')
            ->unless($user->isAdmin(), fn ($q) => $q->published()->whereIn('course_id', $user->enrollments()->select('course_id')))
            ->orderBy('sort_order')->orderBy('id')
            ->get();
    }

    /** The best attempt at each quiz, keyed by quiz id. */
    public static function bestAttempts(User $user, Collection $quizzes): Collection
    {
        return $user->quizAttempts()
            ->whereIn('quiz_id', $quizzes->pluck('id'))
            ->orderByDesc('score')->orderByDesc('id')
            ->get()
            ->unique('quiz_id')
            ->keyBy('quiz_id');
    }

    /**
     * The assignment that needs the student now: one sent back for changes,
     * else the next one not handed in yet, else null.
     */
    public static function focus(User $user): ?Assignment
    {
        // Asked by the menu and the footer on the same page: work it out once per request.
        $memo = request()->attributes;
        $key = 'student-work.focus.'.$user->getKey();
        if ($memo->has($key)) {
            return $memo->get($key);
        }

        $assignments = self::assignments($user);
        $latest = self::latestSubmissions($user, $assignments);

        $pick = $assignments->first(fn ($a) => $latest->get($a->id)?->status === 'revision')
            ?? $assignments->first(fn ($a) => ! $latest->has($a->id));

        $memo->set($key, $pick);

        return $pick;
    }
}
