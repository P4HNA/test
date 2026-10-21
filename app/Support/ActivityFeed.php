<?php

namespace App\Support;

use App\Models\ActivityLike;
use App\Models\Certificate;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Lesson;
use App\Models\LessonCompletion;
use App\Models\Promotion;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

/**
 * The posts on the Activity page, built from what really happened on the
 * site rather than from a posts table:
 *
 *   lesson  - the teacher added a lesson to a published course
 *   course  - a course was published
 *   news    - the promotion running now (an announcement or a discount)
 *   cert    - a student earned a certificate
 *   done    - a student finished lessons in a course (one post per student, course and day)
 *   join    - a student enrolled in a course
 *
 * Student posts are only built for signed-in visitors, and show students by
 * first name and last initial. Each post has a stable key ("lesson:12") that
 * likes are stored against.
 */
class ActivityFeed
{
    /** How far back each kind of post is looked for. */
    private const PER_KIND = 20;

    public function __construct(private readonly ?User $viewer)
    {
    }

    /** @return Collection<int, array<string, mixed>> newest first */
    public function posts(int $limit = 30): Collection
    {
        $teacher = User::query()->where('role', 'admin')->oldest('id')->first();

        $posts = collect()
            ->merge($this->newLessons($teacher))
            ->merge($this->newCourses($teacher))
            ->merge($this->news());

        if ($this->viewer) {
            $posts = $posts
                ->merge($this->certificates())
                ->merge($this->finishedLessons())
                ->merge($this->enrollments());
        }

        $posts = $posts->sortByDesc(fn (array $p) => $p['at']->getTimestamp())->take($limit)->values();

        return $this->withLikes($posts);
    }

    /** Whether a key looks like one of ours (checked before a like is saved). */
    public static function isValidKey(string $key): bool
    {
        return (bool) preg_match('/^(lesson|course|news|cert|join):\d{1,12}$|^done:\d{1,12}:\d{1,12}:\d{8}$/', $key);
    }

    /** "Sothea Chan" -> "Sothea C."; one-word names stay as they are. */
    public static function shortName(string $name): string
    {
        $words = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if (count($words) < 2) {
            return $words[0] ?? 'Student';
        }

        return $words[0].' '.mb_substr(end($words), 0, 1).'.';
    }

    // ------------------------------------------------------------------ teacher

    private function newLessons(?User $teacher): array
    {
        $lessons = Lesson::query()
            ->with('module.course')
            ->whereHas('module.course', fn ($q) => $q->published())
            ->latest('created_at')
            ->latest('id')
            ->limit(self::PER_KIND)
            ->get();

        return $lessons->map(function (Lesson $lesson) use ($teacher) {
            $course = $lesson->module->course;
            $minutes = (int) $lesson->duration_minutes;

            return $this->post('lesson:'.$lesson->id, 'lesson', $lesson->created_at, [
                'actor' => $this->teacherActor($course, $teacher),
                'action' => ['released a new course lesson', 'បានបញ្ចេញមេរៀនថ្មី'],
                'title' => $course->localized('title').' — '.$this->localizedOr($lesson, 'title'),
                'subtitle' => $minutes > 0
                    ? $this->say(':count min lesson', 'មេរៀន :count នាទី', ['count' => $minutes])
                    : $course->localized('subtitle'),
                'thumb' => CoursePicture::for($course, 300),
                'badge' => ['New Lesson', 'មេរៀនថ្មី'],
                'url' => route('courses.show', $course),
            ]);
        })->all();
    }

    private function newCourses(?User $teacher): array
    {
        return Course::query()->published()->withCount('lessons')
            ->latest('created_at')->latest('id')->limit(self::PER_KIND)->get()
            ->map(fn (Course $course) => $this->post('course:'.$course->id, 'course', $course->created_at, [
                'actor' => $this->teacherActor($course, $teacher),
                'action' => ['published a new course', 'បានបើកវគ្គសិក្សាថ្មី'],
                'title' => $course->localized('title'),
                'subtitle' => $course->localized('subtitle') ?: $this->say(':count lessons', ':count មេរៀន', ['count' => $course->lessons_count]),
                'thumb' => CoursePicture::for($course, 300),
                'badge' => ['New Course', 'វគ្គថ្មី'],
                'url' => route('courses.show', $course),
            ]))->all();
    }

    private function news(): array
    {
        $promotion = Promotion::forHomepage();

        if (! $promotion) {
            return [];
        }

        $discount = $promotion->isDiscount();

        return [$this->post('news:'.$promotion->id, 'news', $promotion->starts_at ?? $promotion->created_at, [
            'actor' => ['name' => 'Academic Desk', 'role' => 'Academy', 'user' => null, 'logo' => true],
            'action' => $discount ? ['announced a discount', 'បានប្រកាសការបញ្ចុះតម្លៃ'] : ['posted an announcement', 'បានប្រកាសព័ត៌មានថ្មី'],
            'title' => $promotion->localized('title'),
            'subtitle' => $promotion->localized('message'),
            'thumb' => $promotion->course ? CoursePicture::for($promotion->course, 300) : null,
            'badge' => $discount ? [$promotion->discount_percent.'% off', 'បញ្ចុះ '.$promotion->discount_percent.'%'] : ['News', 'ព័ត៌មាន'],
            'url' => $promotion->ctaUrl(),
        ])];
    }

    // ------------------------------------------------------------------ students

    private function certificates(): array
    {
        return Certificate::query()->with(['user', 'course'])
            ->whereHas('user')->whereHas('course')
            ->latest('issued_at')->latest('id')->limit(self::PER_KIND)->get()
            ->map(fn (Certificate $cert) => $this->post('cert:'.$cert->id, 'cert', $cert->issued_at ?? $cert->created_at, [
                'actor' => $this->studentActor($cert->user, $cert->course),
                'action' => ['earned the certificate for', 'បានទទួលវិញ្ញាបនបត្រ'],
                'title' => $cert->course->localized('title'),
                'subtitle' => $this->say('Verified certificate of completion', 'វិញ្ញាបនបត្របញ្ចប់ការសិក្សាដែលបានផ្ទៀងផ្ទាត់'),
                'thumb' => CoursePicture::for($cert->course, 300),
                'badge' => ['Certified', 'ទទួលវិញ្ញាបនបត្រ'],
                'url' => route('courses.show', $cert->course),
            ]))->all();
    }

    /** Recent lesson completions, one post per student, course and day. */
    private function finishedLessons(): array
    {
        $completions = LessonCompletion::query()
            ->with(['user', 'lesson.module.course'])
            ->whereHas('user')
            ->whereHas('lesson.module.course', fn ($q) => $q->published())
            ->latest('completed_at')->latest('id')
            ->limit(self::PER_KIND * 10)
            ->get();

        return $completions
            ->groupBy(fn (LessonCompletion $c) => $c->user_id.':'.$c->lesson->module->course_id.':'.($c->completed_at ?? $c->created_at)->format('Ymd'))
            ->take(self::PER_KIND)
            ->map(function (Collection $group, string $key) {
                $latest = $group->first();
                $course = $latest->lesson->module->course;
                $count = $group->count();

                return $this->post('done:'.$key, 'done', $latest->completed_at ?? $latest->created_at, [
                    'actor' => $this->studentActor($latest->user, $course),
                    'action' => ['completed lessons in', 'បានបញ្ចប់មេរៀនក្នុង'],
                    'title' => $course->localized('title'),
                    'subtitle' => $count === 1
                        ? $this->localizedOr($latest->lesson, 'title')
                        : $this->say(':count lessons finished', 'បានបញ្ចប់ :count មេរៀន', ['count' => $count]),
                    'thumb' => CoursePicture::for($course, 300),
                    'badge' => ['Progress', 'វឌ្ឍនភាព'],
                    'url' => route('courses.show', $course),
                ]);
            })->values()->all();
    }

    private function enrollments(): array
    {
        return Enrollment::query()->with(['user', 'course'])
            ->whereHas('user')->whereHas('course', fn ($q) => $q->published())
            ->latest('enrolled_at')->latest('id')->limit(self::PER_KIND)->get()
            ->map(fn (Enrollment $e) => $this->post('join:'.$e->id, 'join', $e->enrolled_at ?? $e->created_at, [
                'actor' => $this->studentActor($e->user, $e->course),
                'action' => ['joined the course', 'បានចូលរៀនវគ្គសិក្សា'],
                'title' => $e->course->localized('title'),
                'subtitle' => $e->course->localized('subtitle'),
                'thumb' => CoursePicture::for($e->course, 300),
                'badge' => ['New Student', 'សិស្សថ្មី'],
                'url' => route('courses.show', $e->course),
            ]))->all();
    }

    // ------------------------------------------------------------------ helpers

    private function teacherActor(Course $course, ?User $teacher): array
    {
        return [
            'name' => $course->instructor_name ?: ($teacher?->name ?: 'Piseth Yoeurn'),
            'role' => 'Lead Instructor',
            'user' => $teacher,
            'logo' => false,
        ];
    }

    private function studentActor(User $user, Course $course): array
    {
        $isYou = $this->viewer && $this->viewer->is($user);

        return [
            'name' => $isYou ? $user->name : self::shortName($user->name),
            'role' => $user->isAdmin() ? 'Lead Instructor' : trim(($course->category ?: 'Design').' Student'),
            'user' => $user,
            'logo' => false,
            'you' => $isYou,
        ];
    }

    private function post(string $key, string $type, ?CarbonInterface $at, array $fields): array
    {
        $km = app()->getLocale() === 'km';
        $at ??= now();

        return [
            'key' => $key,
            'type' => $type,
            'at' => $at,
            'ago' => $at->copy()->locale($km ? 'km' : 'en')->diffForHumans(),
            'actor' => $fields['actor'],
            'action' => $km ? $fields['action'][1] : $fields['action'][0],
            'title' => (string) $fields['title'],
            'subtitle' => (string) ($fields['subtitle'] ?? ''),
            'thumb' => $fields['thumb'] ?? null,
            'badge' => $km ? $fields['badge'][1] : $fields['badge'][0],
            'url' => $fields['url'] ?? null,
        ];
    }

    private function withLikes(Collection $posts): Collection
    {
        $keys = $posts->pluck('key')->all();

        $counts = ActivityLike::query()->whereIn('activity_key', $keys)
            ->selectRaw('activity_key, count(*) as total')->groupBy('activity_key')
            ->pluck('total', 'activity_key');

        $mine = $this->viewer
            ? ActivityLike::query()->where('user_id', $this->viewer->getKey())->whereIn('activity_key', $keys)->pluck('activity_key')->flip()
            : collect();

        return $posts->map(fn (array $p) => $p + [
            'likes' => (int) ($counts[$p['key']] ?? 0),
            'liked' => $mine->has($p['key']),
        ]);
    }

    private function localizedOr(object $model, string $field): string
    {
        $km = $model->{$field.'_km'} ?? null;

        return app()->getLocale() === 'km' && filled($km) ? (string) $km : (string) ($model->{$field.'_en'} ?? '');
    }

    private function say(string $en, string $km, array $replace = []): string
    {
        $text = app()->getLocale() === 'km' ? $km : $en;

        foreach ($replace as $k => $v) {
            $text = str_replace(':'.$k, (string) $v, $text);
        }

        return $text;
    }
}
