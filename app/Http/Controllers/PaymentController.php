<?php

namespace App\Http\Controllers;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Services\PayWayService;
use App\Services\PayWayVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

/**
 * Checkout on ABA PayWay KHQR.
 *
 * PayWay tells us about a payment by calling PayWayCallbackController, so the
 * status endpoint here normally answers straight out of the database and costs
 * nothing. It keeps a rate-limited fallback check for the case where the
 * callback cannot reach us at all (a local machine with no tunnel running).
 */
class PaymentController extends Controller
{
    /**
     * How often one order may fall back to asking PayWay directly.
     *
     * The page polls every 3 seconds; without this the fallback would turn a
     * callback-driven integration back into a polling one.
     */
    private const FALLBACK_EVERY_SECONDS = 20;

    public function checkout(Request $request, Course $course, PayWayService $payway, PayWayVerifier $verifier): View|RedirectResponse
    {
        $user = $request->user();

        // A draft cannot be bought. The one exception is a checkout the student
        // had already started before the course was hidden: they can still come
        // back to it, so money that was sent is never left without its course.
        abort_unless(
            $course->isVisibleTo($user)
                || Payment::query()->where('user_id', $user->id)->where('course_id', $course->id)->where('status', 'pending')->exists(),
            404
        );

        if ($course->isFree() || $user->enrollmentFor($course)) {
            return redirect()->route('courses.show', $course);
        }

        // Settings must be complete. The problem list names .env keys, so it is
        // written to the log for you and never shown to the customer. Read it
        // back with "php artisan payway:test".
        if ($problems = $payway->configProblems()) {
            Log::error('ABA PayWay is not configured', [
                'course' => $course->id,
                'problems' => $problems,
            ]);

            return $this->paymentUnavailable($course);
        }

        // Paid an earlier QR but closed the page before the callback landed?
        // Unlock now rather than charging again.
        if ($this->firstSettledPayment($user->id, $course->id, $verifier)) {
            return redirect()
                ->route('courses.show', $course)
                ->with('success', __('Payment confirmed. Your course is unlocked — enjoy learning!'));
        }

        try {
            // Reuses their current QR when it is still payable.
            $payment = $verifier->qrFor($user, $course);
        } catch (Throwable $e) {
            // PayWay's wording names .env keys, hashes and the API host, so it
            // goes to the log only.
            report($e);

            return $this->paymentUnavailable($course);
        }

        return view('payments.checkout', compact('course', 'payment'));
    }

    /**
     * Polled by the payment page every few seconds.
     *
     * Reads the database. The callback is what moves a payment to paid, so in
     * the normal case this makes no outgoing request at all.
     *
     * POST, not GET: it can flip the payment to paid and create the enrollment,
     * so it must not be reachable by a prefetch, a crawler, a browser cache or
     * a shared link.
     */
    public function check(Request $request, Payment $payment, PayWayVerifier $verifier): JsonResponse
    {
        abort_unless((int) $payment->user_id === (int) $request->user()->id, 403);

        // Fallback, strictly rate-limited: normally the callback has already
        // done this. It covers the local-development case where PayWay cannot
        // reach the callback URL, and the rare dropped callback in production.
        if (! $payment->isPaid() && $verifier->isCheckable($payment) && $this->mayAskPayWay($payment)) {
            try {
                $verifier->settle($payment);
            } catch (Throwable $e) {
                $this->logThrottled('payway_check_error', 'PayWay status check failed', [
                    'invoice' => $payment->invoice_no,
                    'error' => $e->getMessage(),
                ]);

                return response()->json([
                    'state' => 'error',
                    'paid' => false,
                    'message' => __('Still checking your payment. If you already paid, keep this page open — it will be confirmed automatically.'),
                ]);
            }
        } else {
            // No outgoing call, but a QR whose time is long past should still
            // stop claiming to be waiting.
            $verifier->expireIfOverdue($payment);
        }

        $payment->refresh();

        // This page polls only the QR it was handed, but the student may have
        // paid a DIFFERENT one -- an earlier QR whose callback has just landed,
        // or a screenshot of one from a previous visit. Once they are enrolled
        // the course is theirs however it happened, and leaving the page saying
        // "waiting for payment" forever would be simply wrong.
        if (! $payment->isPaid() && $this->alreadyEnrolled($payment)) {
            return $this->paidResponse($payment);
        }

        return match ($payment->status) {
            'paid' => $this->paidResponse($payment),
            'review' => response()->json([
                'state' => 'review',
                'paid' => false,
                'message' => __('We received your payment but it needs a quick manual check. Please contact us with invoice :invoice.', ['invoice' => $payment->invoice_no]),
            ]),
            'declined' => response()->json(array_filter([
                'state' => 'expired',
                'paid' => false,
                'expired' => true,
                'message' => __('That payment did not go through. Tap "Get a new QR" to try again.'),
                'retry_url' => $payment->course ? route('payments.checkout', $payment->course) : null,
            ], fn ($value) => $value !== null)),
            'expired' => response()->json(array_filter([
                'state' => 'expired',
                'paid' => false,
                'expired' => true,
                'message' => $payment->course
                    ? __('This QR code has expired. Tap "Get a new QR" to pay again.')
                    : __('This QR code has expired, and the course is no longer available. Please contact us with invoice :invoice.', ['invoice' => $payment->invoice_no]),
                // No course left to send them back to, so offer no retry link.
                'retry_url' => $payment->course ? route('payments.checkout', $payment->course) : null,
            ], fn ($value) => $value !== null)),
            default => response()->json([
                'state' => 'pending',
                'paid' => false,
                'message' => __('Waiting for your payment… this page updates by itself.'),
            ]),
        };
    }

    private function paidResponse(Payment $payment): JsonResponse
    {
        // The course can have been deleted between showing the QR and the
        // payment landing. The money is still recorded; there is just nothing
        // to open.
        if (! $payment->course) {
            session()->flash('status', __('Payment received for invoice :invoice, but that course is no longer available. Please contact us.', ['invoice' => $payment->invoice_no]));

            return response()->json([
                'state' => 'paid',
                'paid' => true,
                'redirect' => route('courses'),
                'message' => __('Payment received. That course is no longer available — please contact us with invoice :invoice.', ['invoice' => $payment->invoice_no]),
            ]);
        }

        session()->flash('success', __('Payment confirmed. Your course is unlocked — enjoy learning!'));

        return response()->json([
            'state' => 'paid',
            'paid' => true,
            'redirect' => route('courses.show', $payment->course),
            'message' => __('Payment confirmed. Opening your course…'),
        ]);
    }

    /**
     * Any of this student's recent QRs for this course that turns out to be paid.
     *
     * Covers the student who paid and then closed the page: their payment may
     * already be settled (callback), or settle on the first check here.
     */
    private function firstSettledPayment(int $userId, int $courseId, PayWayVerifier $verifier): ?Payment
    {
        $payments = Payment::query()
            ->where('user_id', $userId)
            ->where('course_id', $courseId)
            ->whereIn('status', ['paid', 'pending', 'expired'])
            ->where('created_at', '>=', now()->subDay())
            ->latest('id')
            ->limit(5)
            ->get();

        foreach ($payments as $payment) {
            if ($payment->isPaid()) {
                $verifier->ensureEnrollment($payment);

                return $payment;
            }

            if (! $verifier->isCheckable($payment)) {
                continue;
            }

            try {
                if ($verifier->settle($payment) === 'paid') {
                    return $payment;
                }
            } catch (Throwable $e) {
                // PayWay unreachable right now. Showing a fresh QR is the right
                // fallback; a real payment still lands via the callback.
                $this->logThrottled('payway_checkout_sweep', 'Could not re-check a recent PayWay QR at checkout', [
                    'invoice' => $payment->invoice_no,
                    'error' => $e->getMessage(),
                ]);

                return null;
            }
        }

        return null;
    }

    /**
     * Does this student already have the course, by any route?
     *
     * One indexed lookup on the enrollments unique key, so it is cheap enough to
     * run on every poll.
     */
    private function alreadyEnrolled(Payment $payment): bool
    {
        if (! $payment->user_id || ! $payment->course_id) {
            return false;
        }

        return Enrollment::query()
            ->where('user_id', $payment->user_id)
            ->where('course_id', $payment->course_id)
            ->exists();
    }

    /**
     * True at most once every 20 seconds per order.
     *
     * Cache::add is atomic, so two polls arriving together cannot both win. On a
     * cache failure this returns false: the fallback is a safety net, and the
     * callback is the real mechanism.
     */
    private function mayAskPayWay(Payment $payment): bool
    {
        try {
            return Cache::add(
                'payway_check_'.$payment->getKey(),
                true,
                now()->addSeconds(self::FALLBACK_EVERY_SECONDS)
            );
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Send the customer back to the course with a plain apology.
     *
     * Takes no detail argument on purpose: PayWay's technical wording names
     * .env keys, the API host and hash failures. There is no argument to
     * accidentally pass through, so it cannot leak. Callers log the real reason
     * before calling this.
     */
    private function paymentUnavailable(Course $course): RedirectResponse
    {
        return redirect()
            ->route('courses.show', $course)
            ->with('status', __('Online payment is temporarily unavailable. Please try again in a few minutes.'));
    }

    /** Avoid filling the log with the same error every few seconds. */
    private function logThrottled(string $key, string $message, array $context): void
    {
        try {
            if (! Cache::add('log_throttle_'.$key, true, now()->addMinute())) {
                return;
            }
        } catch (Throwable) {
            //
        }

        Log::warning($message, $context);
    }
}
