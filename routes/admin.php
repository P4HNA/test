<?php

use App\Http\Controllers\Admin\CertificateController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LessonController;
use App\Http\Controllers\Admin\ModuleController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\PromotionController;
use App\Http\Controllers\Admin\ThumbnailController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VideoController;
use App\Http\Controllers\Admin\VideoUploadController;
use Illuminate\Support\Facades\Route;

/*
| Every admin page lives in this one group:
|   admin.network  -> only your allowed IPs get past (else 404)
|   auth           -> must be logged in
|   admin          -> must be an admin user (else 403)
|
| Admin URLs bind a course on its uuid, not its slug, so renaming a course
| never breaks an admin link or a bookmark -- the public side is the half that
| wants the readable slug. Everything else here (users, payments, lessons,
| modules, promotions) binds on its uuid too: an admin URL should not leak how
| many customers or payments exist by counting upwards.
*/
Route::middleware(['admin.network', 'auth', 'admin'])
    ->domain(config('admin.domain'))   // empty = your normal domain
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('live', [DashboardController::class, 'live'])->name('live');

        // Students and admins
        Route::get('users', [UserController::class, 'index'])->name('users');
        Route::get('users/{user}', [UserController::class, 'show'])->name('users.show');

        // Course access: give a student a course by hand (a cash payment, a
        // scholarship) or take it back. Neither records or refunds any money.
        Route::post('users/{user}/enrollments', [UserController::class, 'grant'])->name('users.enrollments.grant');
        Route::delete('users/{user}/enrollments/{course:uuid}', [UserController::class, 'revoke'])->name('users.enrollments.revoke')
            ->withoutScopedBindings();   // the course is not looked up "through" the user
        Route::put('users/{user}/password', [UserController::class, 'updatePassword'])->name('users.password');
        Route::patch('users/{user}/ban', [UserController::class, 'ban'])->name('users.ban');
        Route::patch('users/{user}/unban', [UserController::class, 'unban'])->name('users.unban');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');

        // Payments: the money book. Read-only apart from archiving -- nothing
        // here unlocks a course, only PayWayVerifier does that.
        Route::get('payments', [PaymentController::class, 'index'])->name('payments');

        // The same rows the list is showing, as a CSV for the accountant.
        // Read-only, and throttled because it can walk the whole book.
        Route::get('payments/export', [PaymentController::class, 'export'])
            ->middleware('throttle:10,1')
            ->name('payments.export');
        Route::patch('payments/{payment}/archive', [PaymentController::class, 'archive'])->name('payments.archive');
        Route::patch('payments/{payment}/restore', [PaymentController::class, 'restore'])->name('payments.restore');

        // Refunding CLOSES a course. It is the one thing in this panel that takes
        // access away, so PayWay has the final word: "refund" does nothing unless
        // PayWay reports the transaction as refunded. "refund-anyway" is the
        // deliberate override for when PayWay cannot answer, and needs the invoice
        // number typed to reach it.
        Route::patch('payments/{payment}/refund', [PaymentController::class, 'refund'])->name('payments.refund');
        Route::patch('payments/{payment}/refund-anyway', [PaymentController::class, 'refundAnyway'])->name('payments.refund-anyway');

        // Certificate requests: a student who finished a course sends their real
        // name and Telegram; approving one issues the certificate in that name.
        Route::get('certificates', [CertificateController::class, 'index'])->name('certificates');
        Route::patch('certificates/{certificateRequest}/approve', [CertificateController::class, 'approve'])->name('certificates.approve');
        Route::patch('certificates/{certificateRequest}/reject', [CertificateController::class, 'reject'])->name('certificates.reject');
        Route::patch('certificates/{certificateRequest}/revoke', [CertificateController::class, 'revoke'])->name('certificates.revoke');

        // Promotions: discounts that lower the price at checkout, and announcements.
        // One of them can show in the bar at the top of the homepage.
        Route::get('promotions', [PromotionController::class, 'index'])->name('promotions');
        Route::get('promotions/create', [PromotionController::class, 'create'])->name('promotions.create');
        Route::post('promotions', [PromotionController::class, 'store'])->name('promotions.store');
        Route::get('promotions/{promotion}/edit', [PromotionController::class, 'edit'])->name('promotions.edit');
        Route::put('promotions/{promotion}', [PromotionController::class, 'update'])->name('promotions.update');
        Route::patch('promotions/{promotion}/toggle', [PromotionController::class, 'toggle'])->name('promotions.toggle');
        Route::delete('promotions/{promotion}', [PromotionController::class, 'destroy'])->name('promotions.destroy');

        // Courses
        Route::get('courses', [CourseController::class, 'index'])->name('courses');
        Route::get('courses/create', [CourseController::class, 'create'])->name('courses.create');
        Route::post('courses', [CourseController::class, 'store'])->name('courses.store');
        Route::get('courses/{course:uuid}/edit', [CourseController::class, 'edit'])->name('courses.edit');
        Route::put('courses/{course:uuid}', [CourseController::class, 'update'])->name('courses.update');
        Route::patch('courses/{course:uuid}/visibility', [CourseController::class, 'visibility'])->name('courses.visibility');
        Route::delete('courses/{course:uuid}', [CourseController::class, 'destroy'])->name('courses.destroy');

        // Modules (a course is made of modules, a module of lessons)
        Route::post('courses/{course:uuid}/modules', [ModuleController::class, 'store'])->name('modules.store');
        Route::put('courses/{course:uuid}/modules/{module}', [ModuleController::class, 'update'])->name('modules.update');
        Route::delete('courses/{course:uuid}/modules/{module}', [ModuleController::class, 'destroy'])->name('modules.destroy');
        Route::post('courses/{course:uuid}/modules/{module}/move', [ModuleController::class, 'move'])->name('modules.move');

        // Lessons
        Route::post('courses/{course:uuid}/lessons', [LessonController::class, 'store'])->name('lessons.store');
        Route::put('courses/{course:uuid}/lessons/{lesson}', [LessonController::class, 'update'])->name('lessons.update');
        Route::delete('courses/{course:uuid}/lessons/{lesson}', [LessonController::class, 'destroy'])->name('lessons.destroy');
        Route::post('courses/{course:uuid}/lessons/{lesson}/move', [LessonController::class, 'move'])->name('lessons.move');

        // Video file upload (sent in small chunks)
        Route::post('uploads/chunk', [VideoUploadController::class, 'chunk'])->name('uploads.chunk');

        // Videos: every lesson's video, across every course, in one place
        Route::get('videos', [VideoController::class, 'index'])->name('videos');
        Route::get('videos/{course:uuid}', [VideoController::class, 'show'])->name('videos.show');
        Route::delete('videos/{lesson}', [VideoController::class, 'destroy'])->name('videos.destroy');

        // Course pictures: a picture-first gallery to swap covers without opening the full editor
        Route::get('thumbnails', [ThumbnailController::class, 'index'])->name('thumbnails');
        Route::post('thumbnails/{course:uuid}', [ThumbnailController::class, 'update'])->name('thumbnails.update');
    });
