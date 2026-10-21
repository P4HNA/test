<?php

namespace App\Support;

use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * What a course card shows on the live-design pages (Home and the Course
 * Library): the software chips, the picture, the filter group, and the
 * viewer's own progress and resume link.
 */
class CourseCard
{
    /** Course mark => [software chips, software line, stock picture, filter group]. */
    public const SOFTWARE = [
        'ps' => [['Ps'], 'Photoshop', 'ps', 'Graphic Design'],
        'ai' => [['Ai'], 'Illustrator', 'ai', 'Graphic Design'],
        'id' => [['Id'], 'InDesign', 'id', 'Graphic Design'],
        'gd' => [['Ps', 'Ai', 'Id'], 'Photoshop + Illustrator + InDesign', 'gd', 'Graphic Design'],
        'fg' => [['Fg'], 'Figma', 'ux', 'UX/UI Design'],
        'xd' => [['Xd'], 'Adobe XD', 'ux', 'UX/UI Design'],
        'ae' => [['Ae'], 'After Effects', 'mo', 'Motion Graphics'],
        'pr' => [['Pr'], 'Premiere Pro', 'mo', 'Motion Graphics'],
    ];

    /** The order the filter groups are offered in. */
    public const GROUPS = ['Graphic Design', 'UX/UI Design', 'Motion Graphics'];

    /** @return array<string, mixed> */
    public static function make(Course $course, ?User $user): array
    {
        $mark = trim((string) $course->mark);
        $sw = self::SOFTWARE[strtolower($mark)] ?? null;
        $category = (string) $course->category;
        $minutes = $course->durationMinutes();
        $enrollment = $user?->enrollmentFor($course);
        $lesson = $enrollment?->continueLesson();
        $description = trim(strip_tags((string) $course->localized('description'))) ?: trim((string) $course->localized('subtitle'));

        return [
            'course' => $course,
            'title' => $course->localized('title'),
            'chips' => $sw[0] ?? array_values(array_filter([$mark])),
            'tools' => $sw[1] ?? ($category ?: 'Design'),
            'photo' => $course->thumbnailSrc() ?: CoursePicture::STOCK[$sw[2] ?? 'gd'],
            'group' => $sw[3] ?? self::groupOf($category),
            'category' => $category,
            'description' => Str::limit($description, 240),
            'level' => $course->level ? __($course->level) : '',
            'duration' => $minutes > 0 ? $course->formattedDuration() : '',
            'lessons' => (int) $course->lessons_count,
            'enrolled' => $enrollment !== null,
            'progress' => $enrollment ? $course->progressPercent($user) : 0,
            // Seeded lessons can lack a route key; fall back to the course page then.
            'continue' => ($lesson && filled($lesson->getRouteKey())) ? route('courses.lessons.show', [$course, $lesson]) : route('courses.show', $course),
            'url' => route('courses.show', $course),
        ];
    }

    public static function groupOf(string $category): string
    {
        $c = strtolower($category);

        if (preg_match('/ux|ui|figma|xd|web|app/', $c)) {
            return 'UX/UI Design';
        }

        if (preg_match('/motion|after effects|animation|video|premiere/', $c)) {
            return 'Motion Graphics';
        }

        return 'Graphic Design';
    }
}
