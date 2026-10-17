<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Lesson;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class LessonController extends Controller
{
    public function show(Request $request, Course $course, Lesson $lesson): View|RedirectResponse
    {
        $course->load(['modules.lessons']);
        $user = $request->user();

        $belongs = $course->modules->contains(
            fn ($module) => $module->id === $lesson->module_id
        );

        if (! $belongs) {
            abort(404);
        }

        $enrollment = $user->enrollmentFor($course);
        $owns = $enrollment !== null || $user->isAdmin();

        if (! $owns && ! $lesson->isFreePreviewIn($course)) {
            return redirect()
                ->route('courses.show', $course)
                ->with('status', 'Enrol to open the classroom.');
        }

        // Only a real enrolment remembers where you were. A preview watcher has
        // no enrollment row to write to, and must not get one: that would hand
        // them the whole course.
        $enrollment?->forceFill(['last_lesson_id' => $lesson->id])->save();

        $ordered = $course->modules->flatMap->lessons->values();
        $index = $ordered->search(fn ($item) => $item->id === $lesson->id);
        $previous = $index > 0 ? $ordered[$index - 1] : null;
        $next = $index !== false && $index < $ordered->count() - 1 ? $ordered[$index + 1] : null;
        $currentModule = $course->modules->firstWhere('id', $lesson->module_id);
        $completedIds = $user->lessonCompletions()
            ->whereIn('lesson_id', $ordered->pluck('id'))
            ->pluck('lesson_id');

        return view('courses.player', [
            'course' => $course,
            'lesson' => $lesson,
            'previous' => $previous,
            'next' => $next,
            'position' => $index === false ? 1 : $index + 1,
            'total' => $ordered->count(),
            'currentModule' => $currentModule,
            'completedIds' => $completedIds,
            'isComplete' => $completedIds->contains($lesson->id),
            // Watching a free sample without owning the course: the player
            // hides progress tracking and sells the course instead.
            'previewOnly' => ! $owns,
        ]);
    }

    public function complete(Request $request, Course $course, Lesson $lesson): RedirectResponse
    {
        // Same ownership rule as show() and video(): the lesson must belong to
        // this course, otherwise enrolment in one course could be used to
        // record completions for another course's lessons. Checked before
        // anything (including an admin's auto-enrolment) is written.
        $belongs = $course->modules()->whereKey($lesson->module_id)->exists();
        abort_unless($belongs, 404);

        $user = $request->user();
        $enrollment = $user->enrollmentFor($course);

        if (! $enrollment && ! $user->isAdmin()) {
            abort(403);
        }

        if ($user->isAdmin() && ! $enrollment) {
            $enrollment = $user->enrollments()->firstOrCreate(
                ['course_id' => $course->id],
                ['enrolled_at' => now()]
            );
        }

        $enrollment->markLessonComplete($lesson);

        $course->load(['modules.lessons']);
        $ordered = $course->modules->flatMap->lessons->values();
        $index = $ordered->search(fn ($item) => $item->id === $lesson->id);
        $next = $index !== false && $index < $ordered->count() - 1 ? $ordered[$index + 1] : null;

        if ($next) {
            return redirect()
                ->route('courses.lessons.show', [$course, $next])
                ->with('status', 'Lesson marked complete.');
        }

        return redirect()
            ->route('certificates.request', $course)
            ->with('status', __('Course complete. Ask for your certificate below.'));
    }

    /**
     * Streams a video that was uploaded from the admin panel.
     * Same rule as the lesson page: enrolled students and admins only.
     * The file lives outside /public, so its address cannot be shared around.
     */
    public function video(Request $request, Course $course, Lesson $lesson)
    {
        $user = $request->user();

        $belongs = $course->modules()->whereKey($lesson->module_id)->exists();
        abort_unless($belongs, 404);
        abort_unless(
            $user->isAdmin() || $user->isEnrolledIn($course) || $lesson->isFreePreviewIn($course),
            403
        );
        abort_unless($lesson->isUpload(), 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($lesson->video_path), 404);

        // A file response supports HTTP Range requests, so the player can skip around.
        return response()->file($disk->path($lesson->video_path), [
            'Cache-Control' => 'private, max-age=3600',
        ]);
    }
}
