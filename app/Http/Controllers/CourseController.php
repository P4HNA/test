<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Promotion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function home(): View
    {
        $courses = Course::query()
            ->published()
            ->withCount('lessons')
            ->orderBy('sort_order')
            ->get();

        // The one promotion for the bar at the top of the page, if any is live.
        $promotion = Promotion::forHomepage();

        return view('welcome', compact('courses', 'promotion'));
    }

    public function index(Request $request): View
    {
        $query = Course::query()
            ->published()
            ->with(['modules.lessons'])
            ->withCount('lessons')
            ->orderBy('sort_order');

        $search = trim((string) $request->query('q', ''));

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('title_en', 'like', '%'.$search.'%')
                    ->orWhere('title_km', 'like', '%'.$search.'%')
                    ->orWhere('category', 'like', '%'.$search.'%')
                    ->orWhere('description_en', 'like', '%'.$search.'%')
                    ->orWhere('description_km', 'like', '%'.$search.'%');
            });
        }

        $catalogue = Course::query()->published()->orderBy('sort_order')->get();
        $levels = collect(['All'])->merge($catalogue->pluck('level')->unique()->values());

        // Course level: only accept a value that actually exists in the data,
        // otherwise fall back to "All" (no filter) instead of erroring or matching nothing.
        $level = trim((string) $request->query('level', 'All'));
        if ($level === '' || ! $levels->contains($level)) {
            $level = 'All';
        }
        if ($level !== 'All') {
            $query->where('level', $level);
        }

        // Free courses only. Mirrors Course::isFree().
        $free = $request->boolean('free');
        if ($free) {
            $query->where(function ($builder) {
                $builder->where('access_type', 'free')->orWhere('price', 0);
            });
        }

        $priceMin = $this->nonNegativeInt($request->query('price_min'));
        $priceMax = $this->nonNegativeInt($request->query('price_max'));

        if ($priceMin !== null && $priceMax !== null && $priceMin > $priceMax) {
            [$priceMin, $priceMax] = [$priceMax, $priceMin];
        }

        if ($priceMin !== null) {
            $query->where('price', '>=', $priceMin);
        }

        if ($priceMax !== null) {
            $query->where('price', '<=', $priceMax);
        }

        $courses = $query->get();

        // Slim, unsearched dataset for the live-suggestions dropdown: it respects the
        // Level / Free / Price filters (so suggestions stay consistent with what's on
        // screen) but deliberately excludes the current "q" text, so typing can widen
        // or narrow the match set instantly in the browser without a round trip.
        $searchIndex = Course::query()
            ->published()
            ->when($level !== 'All', fn ($builder) => $builder->where('level', $level))
            ->when($free, function ($builder) {
                $builder->where(function ($b) {
                    $b->where('access_type', 'free')->orWhere('price', 0);
                });
            })
            ->when($priceMin !== null, fn ($builder) => $builder->where('price', '>=', $priceMin))
            ->when($priceMax !== null, fn ($builder) => $builder->where('price', '<=', $priceMax))
            ->orderBy('sort_order')
            ->get(['id', 'slug', 'title_en', 'title_km', 'category'])
            ->map(fn (Course $course) => [
                'slug' => $course->slug,
                'title' => $course->localized('title'),
                'category' => $course->category,
            ])
            ->values();

        $categories = collect(['All'])->merge($catalogue->pluck('category')->unique()->values());

        // ?category=Photoshop opens the catalogue on that category (a promotion's
        // button uses it). Only a category that exists is accepted.
        $activeCategory = $categories->first(fn ($c) => strcasecmp((string) $c, (string) $request->query('category', '')) === 0) ?? 'All';
        $user = auth()->user();
        $enrolledIds = $user?->enrollments()->pluck('course_id') ?? collect();

        $activeFilterCount = ($level !== 'All' ? 1 : 0)
            + ($free ? 1 : 0)
            + ($priceMin !== null ? 1 : 0)
            + ($priceMax !== null ? 1 : 0);

        // The promotion Admin chose to feature, shown as the banner above the courses.
        $promotion = Promotion::forHomepage();

        return view('courses.index', compact(
            'courses', 'categories', 'levels', 'enrolledIds', 'search', 'user',
            'level', 'free', 'priceMin', 'priceMax', 'activeFilterCount', 'searchIndex', 'activeCategory', 'promotion'
        ));
    }

    /**
     * Parse a query-string value into a non-negative integer, or null if it isn't one.
     * Keeps unexpected/garbage input (e.g. "abc") from ever reaching the query builder.
     */
    private function nonNegativeInt(mixed $value): ?int
    {
        if ($value === null || $value === '' || ! is_numeric($value)) {
            return null;
        }

        return max(0, (int) $value);
    }

    public function show(Request $request, Course $course): View
    {
        $user = $request->user();
        abort_unless($course->isVisibleTo($user), 404);

        $course->load(['modules.lessons']);
        $enrollment = $user?->enrollmentFor($course);
        $progress = $course->progressPercent($user);

        // Which lessons this learner has already finished. Fetched once so the
        // syllabus can tick rows off without a query per lesson.
        $completedLessonIds = $user
            ? $user->lessonCompletions()->whereIn('lesson_id', $course->modules->flatMap->lessons->pluck('id'))->pluck('lesson_id')
            : collect();

        return view('courses.show', compact('course', 'enrollment', 'progress', 'completedLessonIds'));
    }

    public function enroll(Request $request, Course $course): RedirectResponse
    {
        $user = $request->user();
        abort_unless($course->isVisibleTo($user), 404);

        if (! $course->isFree()) {
            return redirect()->route('payments.checkout', $course);
        }

        $enrollment = Enrollment::query()->firstOrCreate(
            [
                'user_id' => $user->id,
                'course_id' => $course->id,
            ],
            [
                'enrolled_at' => now(),
            ]
        );

        $lesson = $enrollment->continueLesson() ?? $course->load('modules.lessons')->firstLesson();

        if (! $lesson) {
            return redirect()
                ->route('courses.show', $course)
                ->with('status', 'This course has no lessons yet.');
        }

        return redirect()->route('courses.lessons.show', [$course, $lesson]);
    }
}
