<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $role = in_array($request->query('role'), ['student', 'admin', 'suspended'], true) ? $request->query('role') : 'all';
        $like = '%'.addcslashes($q, '%_\\').'%';

        $users = User::query()
            ->withCount(['enrollments', 'lessonCompletions', 'certificates'])
            ->when(in_array($role, ['student', 'admin'], true), fn ($query) => $query->where('role', $role))
            ->when($role === 'suspended', fn ($query) => $query->whereNotNull('banned_at'))
            ->when($q !== '', fn ($query) => $query->where(function ($w) use ($like) {
                $w->where('name', 'like', $like)->orWhere('email', 'like', $like);
            }))
            ->orderByDesc('created_at')
            ->paginate(15)
            ->withQueryString();

        return view('admin.users.index', [
            'users' => $users,
            'q' => $q,
            'role' => $role,
            'counts' => [
                'all' => User::count(),
                'student' => User::where('role', 'student')->count(),
                'admin' => User::where('role', 'admin')->count(),
                'suspended' => User::whereNotNull('banned_at')->count(),
            ],
            'lastSeen' => $this->lastSeen($users->pluck('id')->all()),
        ]);
    }

    /**
     * One person on one page: their account, every course they can open and
     * how far they got, and every payment they made. This is where "why can't
     * I open my course?" gets answered, and fixed.
     */
    public function show(User $user)
    {
        $enrollments = $user->enrollments()
            ->with(['course.modules.lessons', 'lastLesson'])
            ->latest('enrolled_at')
            ->get()
            ->filter(fn (Enrollment $e) => $e->course !== null)
            ->values();

        $payments = Payment::query()
            ->where('user_id', $user->id)
            ->with('course:id,uuid,title_en,title_km,slug')
            ->latest()
            ->get();

        $paidInvoices = $payments->where('status', 'paid')->keyBy('course_id');
        $certificates = $user->certificates()->get()->keyBy('course_id');
        $done = $user->lessonCompletions()->pluck('lesson_id')->flip();

        $courses = $enrollments->map(function (Enrollment $e) use ($paidInvoices, $certificates, $done) {
            $course = $e->course;
            $lessonIds = $course->modules->flatMap->lessons->pluck('id');
            $completed = $lessonIds->filter(fn ($id) => $done->has($id))->count();
            $total = $lessonIds->count();

            return [
                'enrollment' => $e,
                'course' => $course,
                'completed' => $completed,
                'total' => $total,
                'percent' => $total > 0 ? (int) round($completed / $total * 100) : 0,
                'certificate' => $certificates->get($course->id),
                // How they got in. Only a paid invoice proves a purchase; a paid
                // course without one was given by hand (or predates the records).
                'source' => $course->isFree() ? 'free' : ($paidInvoices->has($course->id) ? 'paid' : 'granted'),
                'invoice' => $paidInvoices->get($course->id),
            ];
        });

        $grantable = Course::query()
            ->whereNotIn('id', $enrollments->pluck('course_id'))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return view('admin.users.show', [
            'user' => $user,
            'courses' => $courses,
            'payments' => $payments,
            'grantable' => $grantable,
            'lastSeen' => $this->lastSeen([$user->id])[$user->id] ?? null,
        ]);
    }

    /**
     * Give a student a course by hand: they paid in cash, won a scholarship,
     * or a payment needs sorting out. No payment is recorded, and none is
     * invented; the payment book stays exactly what PayWay reported.
     */
    public function grant(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'course_id' => ['required', 'integer', 'exists:courses,id'],
        ], [
            'course_id.required' => 'Choose the course to give.',
            'course_id.exists' => 'That course no longer exists.',
        ]);

        $course = Course::query()->findOrFail($data['course_id']);

        $enrollment = Enrollment::query()->firstOrCreate(
            ['user_id' => $user->id, 'course_id' => $course->id],
            ['enrolled_at' => now()],
        );

        if (! $enrollment->wasRecentlyCreated) {
            return back()->with('status', "{$user->name} already has {$course->localized('title')}.");
        }

        Log::info('Admin gave a student a course', [
            'admin_id' => $request->user()->getKey(),
            'user_id' => $user->getKey(),
            'course_id' => $course->getKey(),
        ]);

        return back()->with('status', "{$user->name} can now open {$course->localized('title')}. No payment was recorded.");
    }

    /**
     * Take a course away. Lesson progress and any certificate are kept, so
     * giving the course back later picks up where they left off. This does
     * not refund anyone: refunds go through Payments.
     */
    public function revoke(Request $request, User $user, Course $course): RedirectResponse
    {
        $removed = Enrollment::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->delete();

        if (! $removed) {
            return back()->with('status', "{$user->name} did not have {$course->localized('title')}.");
        }

        Log::info('Admin removed a student from a course', [
            'admin_id' => $request->user()->getKey(),
            'user_id' => $user->getKey(),
            'course_id' => $course->getKey(),
        ]);

        return back()->with('status', "{$user->name} can no longer open {$course->localized('title')}. Their progress is kept.");
    }

    /**
     * Change someone's password.
     *
     * The admin must type their OWN password too, so a walk-up on an unlocked
     * laptop cannot reset accounts. The person is signed out everywhere.
     */
    public function updatePassword(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'current_password' => ['required', 'current_password'],
        ]);

        $user->forceFill([
            'password' => Hash::make($data['password']),
            'remember_token' => Str::random(60),   // kills "remember me" cookies
        ])->save();

        $signedOut = false;
        $sessions = config('session.table', 'sessions');

        if ((int) $user->getKey() !== (int) $request->user()->getKey()
            && config('session.driver') === 'database'
            && Schema::hasTable($sessions)) {
            DB::table($sessions)->where('user_id', $user->getKey())->delete();
            $signedOut = true;
        }

        Log::info('Admin changed a user password', [
            'admin_id' => $request->user()->getKey(),
            'user_id' => $user->getKey(),
            'ip' => $request->ip(),
        ]);

        $message = "Password updated for {$user->name}.";
        if ($signedOut) {
            $message .= ' They have been signed out of all devices.';
        }

        return back()->with('status', $message);
    }

    /**
     * Suspend an account: they can no longer log in, by password or Google,
     * but their enrollments, payments and certificates all stay intact.
     * Any sessions they're currently signed into are killed immediately.
     */
    public function ban(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', "You can't suspend your own account.");
        }

        if ($user->isAdmin() && $this->isOnlyAdmin($user)) {
            return back()->with('error', 'This is the only admin account — suspending it would lock everyone out.');
        }

        $reason = trim((string) $request->input('reason', ''));

        $user->forceFill([
            'banned_at' => now(),
            'ban_reason' => $reason !== '' ? $reason : null,
        ])->save();

        $this->killSessionsFor($user);

        Log::info('Admin suspended a user', [
            'admin_id' => $request->user()->getKey(),
            'user_id' => $user->getKey(),
        ]);

        return back()->with('status', "{$user->name} has been suspended and signed out.");
    }

    /** Lift a suspension. The account can sign in again right away. */
    public function unban(Request $request, User $user): RedirectResponse
    {
        $user->forceFill([
            'banned_at' => null,
            'ban_reason' => null,
        ])->save();

        Log::info('Admin lifted a suspension', [
            'admin_id' => $request->user()->getKey(),
            'user_id' => $user->getKey(),
        ]);

        return back()->with('status', "{$user->name} can sign in again.");
    }

    /**
     * Permanently remove the account.
     *
     * Their payments are kept on the books (the user_id just nulls out — see
     * the 2026_09_26 migration); enrollments, lesson progress and
     * certificates go with them.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->with('error', "You can't remove your own account.");
        }

        if ($user->isAdmin() && $this->isOnlyAdmin($user)) {
            return back()->with('error', 'This is the only admin account — it can\'t be removed.');
        }

        $name = $user->name;
        $this->killSessionsFor($user);
        $user->delete();

        Log::info('Admin removed a user', [
            'admin_id' => $request->user()->getKey(),
            'user_email' => $user->email,
        ]);

        // Removed from their own record page: that page is gone, so go to the list.
        $fromRecord = url()->previous() === route('admin.users.show', $user);

        return ($fromRecord ? redirect()->route('admin.users') : back())
            ->with('status', "{$name} has been removed.");
    }

    private function isOnlyAdmin(User $user): bool
    {
        return $user->isAdmin() && User::where('role', 'admin')->count() <= 1;
    }

    private function killSessionsFor(User $user): void
    {
        $table = config('session.table', 'sessions');

        if (config('session.driver') === 'database' && Schema::hasTable($table)) {
            DB::table($table)->where('user_id', $user->getKey())->delete();
        }
    }

    /** @return array<int, int> user id => last activity (unix time) */
    private function lastSeen(array $ids): array
    {
        $table = config('session.table', 'sessions');

        if ($ids === [] || config('session.driver') !== 'database' || ! Schema::hasTable($table)) {
            return [];
        }

        return DB::table($table)
            ->whereIn('user_id', $ids)
            ->select('user_id', DB::raw('MAX(last_activity) as seen'))
            ->groupBy('user_id')
            ->pluck('seen', 'user_id')
            ->map(fn ($v) => (int) $v)
            ->all();
    }
}
