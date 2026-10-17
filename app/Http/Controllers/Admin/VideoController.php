<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class VideoController extends Controller
{
    /** One course-per-card dashboard: pick a course to see its lessons. */
    public function index()
    {
        $withVideo = Lesson::query()
            ->join('modules', 'modules.id', '=', 'lessons.module_id')
            ->where(fn ($q) => Lesson::constrainToHasVideo($q, 'lessons.'))
            ->select('modules.course_id', DB::raw('COUNT(*) as c'))
            ->groupBy('modules.course_id')
            ->pluck('c', 'course_id');

        $courses = Course::query()
            ->withCount('lessons')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('admin.videos.index', [
            'courses' => $courses,
            'withVideo' => $withVideo,
        ]);
    }

    /** Every lesson inside one course, grouped by module, in order. */
    public function show(Course $course)
    {
        $course->load('modules.lessons');

        return view('admin.videos.show', [
            'course' => $course,
        ]);
    }

    /**
     * Unassign this lesson's video (and delete the uploaded file, if any).
     * The lesson itself is kept — only its video is cleared.
     */
    public function destroy(Lesson $lesson): RedirectResponse
    {
        $course = $lesson->module?->course;
        $path = $lesson->video_path;

        // video_host has no database default of NULL (it defaults to 'youtube'),
        // so it is reset to that rather than set to null.
        $lesson->forceFill([
            'video_host' => 'youtube',
            'video_url' => null,
            'video_path' => null,
            'video_size' => null,
        ])->save();

        if ($path) {
            Storage::disk('local')->delete($path);
        }

        // The old fallback here passed a raw course_id, which no longer routes
        // now that a course binds on its uuid. There is nothing to show without
        // the course anyway, so an orphaned lesson lands on the index instead.
        $back = $course
            ? redirect()->route('admin.videos.show', $course)
            : redirect()->route('admin.videos');

        return $back->with('status', 'Video removed from "'.$lesson->localized('title').'".');
    }
}
