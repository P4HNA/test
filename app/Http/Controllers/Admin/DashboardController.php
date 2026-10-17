<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonCompletion;
use App\Models\Payment;
use App\Models\User;
use App\Support\Bytes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class DashboardController extends Controller
{
    public function index()
    {
        return view('admin.dashboard', ['initial' => $this->payload(14)]);
    }

    /**
     * Polled by the browser every few seconds. The result is cached for 4 seconds,
     * so ten open admin tabs still cost the database one set of queries per 4 seconds.
     */
    public function live(Request $request): JsonResponse
    {
        $days = (int) $request->query('days', 14);
        $days = in_array($days, [7, 14, 30], true) ? $days : 14;

        $data = Cache::remember("admin.live.{$days}", 4, fn () => $this->payload($days));

        return response()->json($data)->header('Cache-Control', 'no-store');
    }

    private function payload(int $days): array
    {
        $now = now();
        $weekAgo = $now->copy()->subDays(7);
        $twoWeeksAgo = $now->copy()->subDays(14);

        // ---- Students ---------------------------------------------------------
        $students = fn (): Builder => User::query()->where('role', 'student');

        $studentTotal = $students()->count();
        $joinedWeek = $students()->where('created_at', '>=', $weekAgo)->count();
        $joinedBefore = $students()->whereBetween('created_at', [$twoWeeksAgo, $weekAgo])->count();

        // ---- Enrollments ------------------------------------------------------
        $enrollTotal = Enrollment::count();
        $enrollWeek = Enrollment::where('enrolled_at', '>=', $weekAgo)->count();
        $enrollBefore = Enrollment::whereBetween('enrolled_at', [$twoWeeksAgo, $weekAgo])->count();
        $completions = LessonCompletion::count();

        // ---- Courses and lessons ----------------------------------------------
        $courses = Course::query()
            ->withCount(['lessons', 'enrollments'])
            ->orderBy('sort_order')
            ->get();

        $free = $courses->filter(fn ($c) => $c->isFree())->count();
        $lessonTotal = (int) $courses->sum('lessons_count');
        $uploads = Lesson::where('video_host', 'upload')->count();

        $signups = $this->dailyCounts($students(), 'created_at', $days);
        $enrolls = $this->dailyCounts(Enrollment::query(), 'enrolled_at', $days);

        $kpis = [
            'students' => [
                'label' => 'Students',
                'icon' => 'users',
                'value' => $studentTotal,
                'delta' => $this->delta($joinedWeek, $joinedBefore),
                'foot' => "{$joinedWeek} joined in the last 7 days",
                'spark' => $signups['values'],
            ],
            'enrollments' => [
                'label' => 'Enrollments',
                'icon' => 'book',
                'value' => $enrollTotal,
                'delta' => $this->delta($enrollWeek, $enrollBefore),
                'foot' => number_format($completions).' lessons completed',
                'spark' => $enrolls['values'],
            ],
            'courses' => [
                'label' => 'Courses',
                'icon' => 'video',
                'value' => $courses->count(),
                'delta' => null,
                'foot' => "{$lessonTotal} lessons, {$uploads} uploaded videos, {$free} free",
                'spark' => [],
            ],
        ];

        // ---- Fourth card: who is online (needs database sessions), else certificates
        $online = $this->online();

        if ($online) {
            $kpis['online'] = [
                'label' => 'Online now',
                'icon' => 'pulse',
                'value' => $online['users'],
                'delta' => null,
                'foot' => $online['guests'].' guests browsing',
                'spark' => [],
            ];
        } else {
            $kpis['certificates'] = [
                'label' => 'Certificates',
                'icon' => 'check',
                'value' => Certificate::count(),
                'delta' => null,
                'foot' => 'Issued when a student finishes a course',
                'spark' => [],
            ];
        }

        // ---- Lists ------------------------------------------------------------
        $categories = $courses
            ->groupBy(fn ($c) => trim((string) $c->category) !== '' ? (string) $c->category : 'Not set')
            ->map(fn ($group, $category) => ['label' => $category, 'count' => $group->count()])
            ->values()
            ->all();

        $recent = $students()->latest('created_at')->limit(6)->get()->map(fn ($u) => [
            'id' => $u->id,
            'url' => route('admin.users.show', $u),
            'name' => $u->name,
            'email' => $u->email,
            'when' => $u->created_at?->diffForHumans(),
            'ts' => $u->created_at?->timestamp ?? 0,
        ])->all();

        $activity = Enrollment::query()
            ->with(['user:id,uuid,name', 'course'])
            ->latest('enrolled_at')
            ->limit(6)
            ->get()
            ->filter(fn ($e) => $e->user && $e->course)
            ->map(fn ($e) => [
                'id' => $e->id,
                'url' => route('admin.users.show', $e->user),
                'name' => $e->user->name,
                'course' => $e->course->localized('title'),
                'mark' => $e->course->mark,
                'bg' => $e->course->swatch()['bg'],
                'fg' => $e->course->swatch()['fg'],
                'when' => $e->enrolled_at?->diffForHumans(),
            ])
            ->values()
            ->all();

        $top = $courses->sortByDesc('enrollments_count')->take(5)->values()->map(fn ($c) => [
            'id' => $c->id,
            'mark' => $c->mark,
            'title' => $c->localized('title'),
            'category' => $c->category,
            'enrollments' => (int) $c->enrollments_count,
            'lessons' => (int) $c->lessons_count,
            'free' => $c->isFree(),
            'price' => $c->displayPrice(),
            'price_label' => $c->priceLabel(),
            'bg' => $c->swatch()['bg'],
            'fg' => $c->swatch()['fg'],
            'url' => route('admin.courses.edit', $c),
        ])->all();

        // ---- Video storage (the file listing is cached for a minute) --------------
        $storage = Cache::remember('admin.video-storage', 60, function () {
            $disk = Storage::disk('local');
            $bytes = 0;
            $files = 0;

            if ($disk->exists('lesson-videos')) {
                foreach ($disk->files('lesson-videos') as $file) {
                    $bytes += $disk->size($file);
                    $files++;
                }
            }

            return ['bytes' => $bytes, 'files' => $files];
        });

        $freeSpace = @disk_free_space(storage_path());

        return [
            'server_time' => $now->toIso8601String(),
            'kpis' => $kpis,
            'attention' => $this->attention($courses),
            'online' => $online,
            'chart' => [
                'labels' => $signups['labels'],
                'series' => [
                    ['name' => 'New students', 'values' => $signups['values']],
                    ['name' => 'Enrollments', 'values' => $enrolls['values']],
                ],
                'days' => $days,
                'totals' => [array_sum($signups['values']), array_sum($enrolls['values'])],
            ],
            'categories' => $categories,
            'recent' => $recent,
            'activity' => $activity,
            'courses' => $top,
            'storage' => [
                'used' => Bytes::format($storage['bytes']),
                'files' => $storage['files'],
                'free' => $freeSpace !== false ? Bytes::format($freeSpace) : null,
            ],
        ];
    }

    /**
     * Things that need a person, each with the page that fixes it. Only real
     * counts, and only the ones above zero: an empty list means all clear.
     *
     * @return array<int, array{key: string, tone: string, count: int, text: string, action: string, url: string}>
     */
    private function attention($courses): array
    {
        $items = [];

        $review = Payment::query()->visible()->where('status', 'review')->count();
        if ($review > 0) {
            $items[] = [
                'key' => 'review',
                'tone' => 'warn',
                'count' => $review,
                'text' => $review === 1
                    ? 'payment is being checked: the amount did not match the course price'
                    : 'payments are being checked: the amount did not match the course price',
                'action' => 'Review',
                'url' => route('admin.payments', ['status' => 'review']),
            ];
        }

        $missing = Lesson::constrainToMissingVideo(Lesson::query())->count();
        if ($missing > 0) {
            $items[] = [
                'key' => 'videos',
                'tone' => 'warn',
                'count' => $missing,
                'text' => $missing === 1
                    ? 'lesson has no video, so students open it to an empty player'
                    : 'lessons have no video, so students open them to an empty player',
                'action' => 'Add videos',
                'url' => route('admin.videos'),
            ];
        }

        $empty = $courses->filter(fn ($c) => (int) $c->lessons_count === 0)->count();
        if ($empty > 0) {
            $items[] = [
                'key' => 'empty',
                'tone' => 'warn',
                'count' => $empty,
                'text' => $empty === 1 ? 'course has no lessons yet' : 'courses have no lessons yet',
                'action' => 'Open courses',
                'url' => route('admin.courses'),
            ];
        }

        $drafts = $courses->reject->isPublished()->count();
        if ($drafts > 0) {
            $items[] = [
                'key' => 'drafts',
                'tone' => 'info',
                'count' => $drafts,
                'text' => $drafts === 1
                    ? 'course is a draft, hidden from students until you publish it'
                    : 'courses are drafts, hidden from students until you publish them',
                'action' => 'See drafts',
                'url' => route('admin.courses', ['status' => 'draft']),
            ];
        }

        return $items;
    }

    /** @return array{labels: array<int,string>, values: array<int,int>} */
    private function dailyCounts(Builder $query, string $column, int $days): array
    {
        $start = now()->subDays($days - 1)->startOfDay();

        $rows = $query
            ->where($column, '>=', $start)
            ->selectRaw("DATE({$column}) as d, COUNT(*) as c")
            ->groupBy('d')
            ->pluck('c', 'd');

        $labels = [];
        $values = [];

        for ($i = 0; $i < $days; $i++) {
            $day = $start->copy()->addDays($i);
            $labels[] = $day->format('M j');
            $values[] = (int) ($rows[$day->toDateString()] ?? 0);
        }

        return ['labels' => $labels, 'values' => $values];
    }

    private function delta(int $current, int $previous): int
    {
        if ($previous > 0) {
            return (int) round((($current - $previous) / $previous) * 100);
        }

        return $current > 0 ? 100 : 0;
    }

    /**
     * People active in the last 5 minutes. Only possible when sessions are stored
     * in the database (SESSION_DRIVER=database); otherwise this card is left out.
     */
    private function online(): ?array
    {
        if (config('session.driver') !== 'database') {
            return null;
        }

        $table = config('session.table', 'sessions');

        if (! Schema::hasTable($table)) {
            return null;
        }

        $since = now()->subMinutes(5)->timestamp;

        // Admins who are signed in and active right now. Each open admin page asks
        // for fresh numbers every few seconds, so an admin reading the dashboard
        // counts as online for as long as the tab is open.
        $admins = DB::table($table)
            ->join('users', 'users.id', '=', $table.'.user_id')
            ->where('users.role', 'admin')
            ->where($table.'.last_activity', '>=', $since)
            ->groupBy('users.id', 'users.name', 'users.email')
            ->selectRaw('users.id, users.name, users.email, max('.$table.'.last_activity) as last_activity')
            ->orderByDesc('last_activity')
            ->get()
            ->map(fn ($a) => [
                'id' => (int) $a->id,
                'name' => $a->name,
                'email' => $a->email,
                'when' => (int) $a->last_activity >= now()->subMinute()->timestamp
                    ? 'Active now'
                    : Carbon::createFromTimestamp((int) $a->last_activity)->diffForHumans(),
            ])
            ->values()
            ->all();

        return [
            'users' => (int) DB::table($table)->where('last_activity', '>=', $since)->whereNotNull('user_id')->distinct()->count('user_id'),
            'guests' => (int) DB::table($table)->where('last_activity', '>=', $since)->whereNull('user_id')->count(),
            'admins' => $admins,
        ];
    }
}
