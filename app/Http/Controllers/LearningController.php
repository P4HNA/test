<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Support\LearningStats;
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

        return view('learning.profile', [
            'user' => $user,
            'payments' => $payments,
            'stats' => $stats,
            'grid' => $stats->activityGrid(),
            'badges' => $stats->badges(),
            'recent' => $stats->recentActivity(),
        ]);
    }

    public function certificate(Request $request, Certificate $certificate): View
    {
        abort_unless($certificate->user_id === $request->user()->id || $request->user()->isAdmin(), 403);

        $certificate->load(['user', 'course']);

        return view('learning.certificate', compact('certificate'));
    }
}
