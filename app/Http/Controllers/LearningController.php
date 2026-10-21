<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\User;
use App\Support\ActivityFeed;
use App\Support\LearningStats;
use App\Support\StudentWork;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LearningController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $enrollments = Enrollment::query()
            ->with(['course.modules.lessons', 'lastLesson'])
            ->where('user_id', $user->id)
            ->orderByDesc('updated_at')
            ->get();

        $continue = $enrollments->first(function (Enrollment $enrollment) use ($user) {
            return $enrollment->course->progressPercent($user) < 100;
        }) ?? $enrollments->first();

        $completedLessons = $user->lessonCompletions()->count();
        $learningMinutes = $enrollments
            ->flatMap(fn (Enrollment $enrollment) => $enrollment->course->modules->flatMap->lessons)
            ->unique('id')
            ->sum('duration_minutes');
        $certificates = $user->certificates()->with('course')->get();

        return view('learning.index', [
            'enrollments' => $enrollments,
            'continue' => $continue,
            'completedLessons' => $completedLessons,
            'learningHours' => round($learningMinutes / 60, 1),
            'certificates' => $certificates,
            'coursesCompleted' => $enrollments->filter(
                fn (Enrollment $enrollment) => $enrollment->course->progressPercent($user) >= 100
            )->count(),
        ]);
    }

    public function profile(Request $request): View
    {
        $user = $request->user()->load(['certificates.course', 'enrollments.course.modules.lessons']);

        // Their own payments only, and never one an admin has archived away.
        // Read-only: a student can see what they paid, and nothing more -- the
        // record is also what explains why they have access to a course.
        $payments = Payment::query()
            ->visible()
            ->where('user_id', $user->getKey())
            ->with('course:id,title_en,title_km,slug,category')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $stats = new LearningStats($user);
        $assignments = StudentWork::assignments($user);
        $quizzes = StudentWork::quizzes($user);

        // Lessons finished across every enrolled course, against all the lessons in them.
        $lessonTotal = $user->enrollments->sum(fn (Enrollment $enrollment) => $enrollment->course?->modules->flatMap->lessons->count() ?? 0);
        $lessonsDone = $user->lessonCompletions()->count();

        return view('learning.profile', [
            'user' => $user,
            'payments' => $payments,
            'stats' => $stats,
            'grid' => $stats->activityGrid(),
            'badges' => $stats->badges(),
            'lessonsDone' => $lessonsDone,
            'overallProgress' => $lessonTotal > 0 ? (int) min(100, round($lessonsDone / $lessonTotal * 100)) : 0,
            'posts' => ActivityFeed::ownedBy($user)->posts(20),
            'saved' => $user->savedResources()->published()->libraryOrder()->get(),
            'teacher' => User::query()->where('role', 'admin')->oldest('id')->first(),
            'assignments' => $assignments,
            'latestWork' => StudentWork::latestSubmissions($user, $assignments),
            'quizzes' => $quizzes,
            'bestQuiz' => StudentWork::bestAttempts($user, $quizzes),
        ]);
    }

    public function certificate(Request $request, Certificate $certificate): View
    {
        abort_unless($certificate->user_id === $request->user()->id || $request->user()->isAdmin(), 403);

        $certificate->load(['user', 'course']);

        return view('learning.certificate', compact('certificate'));
    }
}
