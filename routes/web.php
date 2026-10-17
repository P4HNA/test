<?php

use App\Http\Controllers\CertificateRequestController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\LearningController;
use App\Http\Controllers\LessonController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PayWayCallbackController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProfileEmailChangeController;
use Illuminate\Support\Facades\Route;

Route::get('/', [CourseController::class, 'home'])->name('home');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::post('/locale', [LocaleController::class, 'update'])->name('locale');

Route::get('/dashboard', function () {
    return redirect()->route('learning.index');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::get('/courses', [CourseController::class, 'index'])->name('courses');
Route::get('/courses/{course}', [CourseController::class, 'show'])->name('courses.show');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // The "about you" half of the page -- photo, headline, bio, location, link.
    // Separate from profile.update because that one owns the email-change
    // handshake and shouldn't be re-run just to save a bio.
    Route::patch('/profile/public', [ProfileController::class, 'updatePublicProfile'])->name('profile.public.update');

    Route::post('/profile/email/resend-code', [ProfileEmailChangeController::class, 'resend'])
        ->middleware('throttle:6,1')
        ->name('profile.email.resend');
    Route::post('/profile/email/confirm-code', [ProfileEmailChangeController::class, 'confirm'])
        ->middleware('throttle:6,1')
        ->name('profile.email.confirm');
    Route::delete('/profile/email/cancel', [ProfileEmailChangeController::class, 'cancel'])
        ->name('profile.email.cancel');
});

// Learning and paying need a verified email address. The profile routes above
// deliberately don't, so someone who typed the wrong address at sign-up can
// still correct it (or delete the account) without being stuck.
Route::middleware(['auth', 'verified'])->group(function () {
    Route::post('/courses/{course}/enroll', [CourseController::class, 'enroll'])->name('courses.enroll');
    Route::get('/courses/{course}/payment', [PaymentController::class, 'checkout'])->name('payments.checkout');
    Route::get('/courses/{course}/lessons/{lesson}', [LessonController::class, 'show'])->name('courses.lessons.show');
    Route::get('/courses/{course}/lessons/{lesson}/video', [LessonController::class, 'video'])->name('courses.lessons.video');
    Route::post('/courses/{course}/lessons/{lesson}/complete', [LessonController::class, 'complete'])->name('courses.lessons.complete');

    Route::get('/my-learning', [LearningController::class, 'index'])->name('learning.index');
    Route::get('/my-profile', [LearningController::class, 'profile'])->name('learning.profile');
    Route::get('/certificates/{certificate}', [LearningController::class, 'certificate'])->name('certificates.show');

    // Finishing a course does not print a certificate by itself: the student
    // sends their real name and Telegram, and an admin checks and approves it.
    Route::get('/courses/{course}/certificate', [CertificateRequestController::class, 'show'])->name('certificates.request');
    Route::post('/courses/{course}/certificate', [CertificateRequestController::class, 'store'])->name('certificates.request.store');
});

// Reading a payment's status is bookkeeping, not learning, so it needs "auth"
// but NOT "verified". Getting a QR does require a verified address
// (payments.checkout above), but once money has moved it must be possible to
// record it and unlock the course -- otherwise a customer who changes their
// email address mid-payment (which resets email_verified_at) pays and is never
// unlocked, and the payment page polls a 403 forever while showing "Waiting for
// payment...".
//
// This is not a way in: check() still refuses any payment that is not the
// caller's own, and the lesson routes above stay behind "verified", so an
// unverified student is enrolled but still cannot open a lesson.
//
// POST, not GET: checking a payment is not a read. It can flip the payment to
// paid and create the enrollment, so it must not be reachable by a prefetch,
// a crawler, a browser cache or a shared link.
//
// The throttle allows the page's 3-second poll (20/min) with room to spare;
// what protects PayWay is the 20-second fallback gate inside check(), not this.
Route::post('/payments/{payment}/check', [PaymentController::class, 'check'])
    ->middleware(['auth', 'throttle:60,1'])
    ->name('payments.check');

// ABA PayWay tells us a payment happened here. No auth and no CSRF token: the
// caller is PayWay's server, which has neither (the CSRF exclusion is in
// bootstrap/app.php). Nothing in the request body is trusted -- the controller
// only reads the transaction id from it and then asks PayWay directly.
//
// Throttled so a flood cannot turn into a flood of outgoing check-transaction
// calls, but generously: PayWay retries, and every retry is one we want.
Route::post('/payway/callback', PayWayCallbackController::class)
    ->middleware('throttle:120,1')
    ->name('payway.callback');

require __DIR__.'/admin.php';
require __DIR__.'/auth.php';
