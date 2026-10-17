<?php

namespace App\Services;

use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Models\User;
use App\Notifications\PaymentNeedsReview;
use App\Notifications\PaymentReceipt;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

/**
 * The one place that decides whether a PayWay payment is real, marks it paid and
 * unlocks the course.
 *
 * Every route in reaches this class: the callback PayWay POSTs, the status
 * endpoint the payment page polls, and "php artisan payments:confirm". They all
 * end up in settle(), which is written to be safe to call twice at once.
 */
class PayWayVerifier
{
    /**
     * Written into gateway_response so a row's gateway is never in doubt.
     *
     * The payments table still holds Bakong rows from before the switch. They
     * have an md5 and no PayWay transaction, so they must never be handed to
     * PayWay's check-transaction endpoint (and vice versa). This tag is what
     * keeps the two apart.
     */
    public const GATEWAY = 'payway-khqr';

    /**
     * Keep accepting a payment slightly past the QR's own expiry.
     *
     * Someone who taps "confirm" in ABA Mobile at the last second has their
     * payment land after our clock says the QR is dead. The money moved, so the
     * course has to open.
     */
    public const EXPIRY_GRACE_MINUTES = 3;

    /** Cents of slack when comparing what was paid to what was owed. */
    private const AMOUNT_TOLERANCE = 0.005;

    public function __construct(private readonly PayWayService $payway) {}

    /**
     * The QR to show this student for this course.
     *
     * Reuses the pending QR they already have if it is still payable, so
     * reloading the payment page does not litter PayWay with transactions or
     * invalidate the code they are in the middle of scanning.
     */
    public function qrFor(User $user, Course $course): Payment
    {
        // The price comes from the database, always. Nothing the browser sends
        // is allowed anywhere near it. A live promotion can lower it (see
        // Course::salePrice()); the amount is then fixed on this payment row,
        // and that row is what PayWay's answer is checked against, so a sale
        // ending mid-payment changes nothing for a QR already shown.
        $discount = $course->activeDiscount();
        $amount = $course->salePrice();
        $listPrice = round((float) $course->price, 2);

        if ($existing = $this->reusableQr($user, $course, $amount)) {
            return $existing;
        }

        // tran_id: <= 20 chars, letters and digits only, unique. 'PD' + 12-digit
        // timestamp + 4 random = 18, leaving room while staying human-readable
        // on PayWay's side.
        $invoiceNo = 'PD'.now()->format('ymdHis').Str::upper(Str::random(4));
        $expiresAt = now()->addMinutes($this->payway->lifetimeMinutes());

        $result = $this->payway->generateQr(
            amount: $amount,
            tranId: $invoiceNo,
            payer: [
                'name' => (string) $user->name,
                'email' => (string) $user->email,
                'phone' => (string) ($user->phone ?? ''),
            ],
            items: [[
                'name' => Str::limit((string) $course->title_en, 60, ''),
                'quantity' => 1,
                'price' => $this->payway->formatAmount($amount),
            ]],
        );

        return Payment::create([
            'user_id' => $user->id,
            'course_id' => $course->id,
            // Snapshot: keeps the row readable if the student or course is
            // deleted later (see the 2026_09_26_000001 migration).
            'payer_name' => $user->name,
            'payer_email' => $user->email,
            'course_title' => $course->title_en,
            'invoice_no' => $invoiceNo,
            'amount' => $amount,
            // Regular price and promotion, kept only when a discount applied.
            'list_price' => $amount < $listPrice ? $listPrice : null,
            'promotion_id' => $amount < $listPrice ? $discount?->id : null,
            'currency' => $this->payway->currency(),
            'qr' => $result['qr'],
            'qr_image' => $result['qr_image'],
            'deeplink' => $result['deeplink'],
            'status' => 'pending',
            'expires_at' => $expiresAt,
            'description' => 'Course: '.$course->title_en
                .($amount < $listPrice && $discount ? ' (promotion: '.$discount->title_en.', '.$discount->discount_percent.'% off)' : ''),
            'gateway_response' => [
                'gateway' => self::GATEWAY,
                'sandbox' => $this->payway->isSandbox(),
                'generate_qr' => $result['raw'],
            ],
        ]);
    }

    /**
     * Ask PayWay about this payment and act on the answer.
     *
     * @return 'paid'|'pending'|'expired'|'declined'|'review'
     *
     * @throws RuntimeException when PayWay could not be reached or refused us
     */
    public function settle(Payment $payment): string
    {
        if ($payment->isPaid()) {
            // Self-heal: a row can be paid while its enrollment is missing if
            // the process died between the two. Make sure it exists.
            $this->ensureEnrollment($payment);

            return 'paid';
        }

        if ($payment->status === 'review') {
            return 'review';
        }

        $result = $this->payway->checkTransaction((string) $payment->invoice_no);

        if ($result['approved']) {
            return $this->markPaid($payment, $result);
        }

        if ($result['final']) {
            Payment::query()
                ->whereKey($payment->getKey())
                ->where('status', 'pending')
                ->update([
                    'status' => 'declined',
                    'gateway_response' => json_encode($this->mergeGateway($payment, $result, 'check')),
                ]);

            Log::info('PayWay payment did not go through', [
                'invoice' => $payment->invoice_no,
                'status' => $result['status'],
            ]);

            $payment->refresh();

            return 'declined';
        }

        return $this->expireIfOverdue($payment);
    }

    /**
     * Mark the payment paid and enrol the student -- once, whatever happens.
     *
     * The callback and the page's poll can arrive in the same millisecond, and
     * PayWay retries its callback, so this runs inside a transaction that takes
     * a row lock on the payment first. Whoever gets the lock second sees the
     * status already flipped and does nothing but confirm the enrollment.
     *
     * @param  array{approved: bool, status: string, amount: ?float, currency: ?string, data: ?array, raw: array}  $result
     * @return 'paid'|'review'
     */
    public function markPaid(Payment $payment, array $result, string $source = 'check'): string
    {
        $data = $result['data'] ?? [];

        /** @var 'paid'|'review' $outcome */
        $outcome = DB::transaction(function () use ($payment, $result, $data, $source) {
            /** @var Payment|null $locked */
            $locked = Payment::query()->whereKey($payment->getKey())->lockForUpdate()->first();

            if (! $locked) {
                return 'paid';
            }

            // Someone else already settled this one. Nothing left to do -- and
            // in particular, do not enrol a second time.
            if ($locked->status === 'paid') {
                return 'paid';
            }

            if ($locked->status === 'review') {
                return 'review';
            }

            $expected = (float) $locked->amount;
            $paid = $result['amount'];
            $paidCurrency = strtoupper((string) ($result['currency'] ?? ''));
            $sameCurrency = $paidCurrency === '' || $paidCurrency === strtoupper((string) $locked->currency);

            // The amount has to match what we charged. PayWay reporting a
            // different figure means either the wrong transaction or a tampered
            // one, and either way a human should look before the course opens.
            $mismatch = $paid !== null && (
                ! $sameCurrency || abs($paid - $expected) > self::AMOUNT_TOLERANCE
            );

            if ($mismatch) {
                $locked->forceFill([
                    'status' => 'review',
                    'gateway_response' => $this->mergeGateway($locked, $result, $source, [
                        'amount_mismatch' => [
                            'expected' => $this->payway->formatAmount($expected),
                            'expected_currency' => strtoupper((string) $locked->currency),
                            'paid' => $paid,
                            'paid_currency' => $paidCurrency ?: null,
                        ],
                    ]),
                ])->save();

                Log::warning('PayWay payment amount does not match the order — held for review, course NOT unlocked', [
                    'invoice' => $locked->invoice_no,
                    'expected' => $expected,
                    'expected_currency' => $locked->currency,
                    'paid' => $paid,
                    'paid_currency' => $paidCurrency,
                ]);

                return 'review';
            }

            $locked->forceFill([
                'status' => 'paid',
                'paid_at' => now(),
                'transaction_hash' => $this->uniqueHash($data['apv'] ?? $data['bank_ref'] ?? null, $locked),
                'from_account_id' => $this->nullableString($data['payer_account'] ?? $data['bank_name'] ?? null),
                'gateway_response' => $this->mergeGateway($locked, $result, $source),
            ])->save();

            // Inside the same transaction: if creating the enrollment fails,
            // the payment does not silently end up paid-but-locked.
            $this->ensureEnrollment($locked);

            Log::info('PayWay course payment confirmed', [
                'invoice' => $locked->invoice_no,
                'user_id' => $locked->user_id,
                'course_id' => $locked->course_id,
                'amount' => (string) $locked->amount,
                'source' => $source,
            ]);

            return 'paid';
        });

        $payment->refresh();

        // Outside the transaction on purpose: the money and the enrollment are
        // committed by now, so a mail failure cannot roll back a real payment.
        if ($outcome === 'paid') {
            $this->sendReceipt($payment);
        }

        // A held payment is the one state nothing else will resolve: the course
        // stays locked and the customer has been told to contact us, so someone
        // has to actually be told.
        if ($outcome === 'review') {
            $this->alertReview($payment);
        }

        return $outcome;
    }

    /**
     * Email the customer their receipt -- exactly once, ever.
     *
     * The callback, the page's poll and "payments:confirm" can all arrive at a
     * payment at the same moment, and PayWay retries its callback, so "have we
     * already sent this?" is claimed with one conditional UPDATE rather than
     * decided in PHP. Two racing processes cannot both win that claim, and
     * whoever loses simply does not send.
     *
     * Every failure here is swallowed and logged. A student who paid gets their
     * course whether or not the mail server is answering; the receipt is the
     * nice-to-have, the unlock is not.
     */
    public function sendReceipt(Payment $payment): bool
    {
        if (! $payment->isPaid()) {
            return false;
        }

        $email = $payment->payerEmail();

        if ($email === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // An orphaned row whose snapshot has no usable address. Nothing to
            // do, and nothing wrong.
            return false;
        }

        // Atomic claim: only the caller that flips NULL -> now() may send.
        $claimed = Payment::query()
            ->whereKey($payment->getKey())
            ->whereNull('receipt_sent_at')
            ->update(['receipt_sent_at' => now()]);

        if ($claimed !== 1) {
            return false;
        }

        try {
            // Routed by address rather than through the User model, so a payment
            // whose account was deleted can still be receipted from its snapshot.
            Notification::route('mail', $email)->notify(new PaymentReceipt($payment));

            Log::info('Payment receipt sent', [
                'invoice' => $payment->invoice_no,
                'to' => $email,
            ]);

            return true;
        } catch (Throwable $e) {
            report($e);

            // Hand the claim back so a later attempt (the sweeper, or
            // "payments:confirm") can try again instead of the receipt being
            // lost for good.
            Payment::query()->whereKey($payment->getKey())->update(['receipt_sent_at' => null]);

            Log::warning('Could not email a payment receipt — the course is still unlocked', [
                'invoice' => $payment->invoice_no,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Warn the admins that a payment is held for review -- exactly once, ever.
     *
     * Same shape as sendReceipt(): the "once" is claimed with one conditional
     * UPDATE rather than decided in PHP, because the callback, the page's poll
     * and "payments:confirm" can all reach the same payment at the same moment.
     *
     * Every failure is swallowed and logged. The payment is already recorded
     * correctly and the course is already (correctly) locked; the email is how a
     * human finds out sooner, not part of the decision.
     */
    public function alertReview(Payment $payment): bool
    {
        if ($payment->status !== 'review') {
            return false;
        }

        $admins = User::query()->where('role', 'admin')->get();

        if ($admins->isEmpty()) {
            Log::warning('A payment is held for review but there is no admin to tell', [
                'invoice' => $payment->invoice_no,
            ]);

            return false;
        }

        // Atomic claim: only the caller that flips NULL -> now() may send.
        $claimed = Payment::query()
            ->whereKey($payment->getKey())
            ->whereNull('review_notified_at')
            ->update(['review_notified_at' => now()]);

        if ($claimed !== 1) {
            return false;
        }

        try {
            Notification::send($admins, new PaymentNeedsReview($payment));

            Log::info('Admins told about a payment held for review', [
                'invoice' => $payment->invoice_no,
                'admins' => $admins->count(),
            ]);

            return true;
        } catch (Throwable $e) {
            report($e);

            // Hand the claim back so the sweeper can try again rather than the
            // warning being lost for good.
            Payment::query()->whereKey($payment->getKey())->update(['review_notified_at' => null]);

            Log::warning('Could not email the review alert - the payment is still held', [
                'invoice' => $payment->invoice_no,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Money given back: close the course again, keep the record.
     *
     * The payment row is never destroyed -- it becomes "refunded" with the date,
     * because it is still a real movement of money and still the explanation for
     * why this student once had access.
     *
     * The enrollment goes, which is what actually closes the course. Their lesson
     * progress does NOT: lesson_completions is keyed on the user and the lesson,
     * not on the enrollment, so if they buy again they pick up where they left
     * off. Any certificate already issued is left alone as a historical fact.
     */
    public function markRefunded(Payment $payment, string $source = 'manual'): bool
    {
        if ($payment->isRefunded()) {
            return false;
        }

        DB::transaction(function () use ($payment, $source) {
            /** @var Payment|null $locked */
            $locked = Payment::query()->whereKey($payment->getKey())->lockForUpdate()->first();

            if (! $locked || $locked->status === 'refunded') {
                return;
            }

            $locked->forceFill([
                'status' => 'refunded',
                'refunded_at' => now(),
                'gateway_response' => array_merge($locked->gateway_response ?? [], [
                    'refunded_by' => $source,
                    'refunded_at' => now()->toIso8601String(),
                ]),
            ])->save();

            if ($locked->user_id && $locked->course_id) {
                $removed = Enrollment::query()
                    ->where('user_id', $locked->user_id)
                    ->where('course_id', $locked->course_id)
                    ->delete();

                Log::warning('Payment refunded — course access removed', [
                    'invoice' => $locked->invoice_no,
                    'user_id' => $locked->user_id,
                    'course_id' => $locked->course_id,
                    'amount' => (string) $locked->amount,
                    'enrollment_removed' => (bool) $removed,
                    'source' => $source,
                ]);
            } else {
                Log::warning('Refunded payment has no student or course left to close', [
                    'invoice' => $locked->invoice_no,
                    'source' => $source,
                ]);
            }
        });

        $payment->refresh();

        return true;
    }

    /**
     * Ask PayWay about a payment we already think is paid.
     *
     * settle() deliberately short-circuits on a paid row so the page's 3-second
     * poll costs nothing. That means a refund made at ABA would never be noticed,
     * so this is the deliberate, occasional question: called from the callback
     * (PayWay only posts when something changed) and from "payments:refund-check".
     *
     * @return 'paid'|'refunded'
     *
     * @throws RuntimeException when PayWay could not be reached
     */
    public function recheckPaid(Payment $payment): string
    {
        if (! $payment->isPaid()) {
            return $payment->status;
        }

        $result = $this->payway->checkTransaction((string) $payment->invoice_no);
        $status = strtoupper((string) ($result['status'] ?? ''));

        if (in_array($status, ['REFUNDED', 'REVERSED'], true)) {
            $this->markRefunded($payment, 'payway');

            return 'refunded';
        }

        return 'paid';
    }

    /**
     * Create the enrollment if it is not there yet.
     *
     * enrollments has a unique index on (user_id, course_id), so this is
     * idempotent even against a concurrent writer that beat us to it.
     */
    public function ensureEnrollment(Payment $payment): void
    {
        // A payment outlives its student and its course: either link can be
        // null by now. There is nobody to enrol; the money record is what still
        // matters, and it is already saved.
        if ($payment->isOrphaned()) {
            Log::warning('Paid course payment has no student or course left to enrol', [
                'invoice' => $payment->invoice_no,
                'payer' => $payment->payer_email,
                'course' => $payment->course_title,
            ]);

            return;
        }

        try {
            Enrollment::query()->firstOrCreate(
                [
                    'user_id' => $payment->user_id,
                    'course_id' => $payment->course_id,
                ],
                [
                    'enrolled_at' => now(),
                ]
            );
        } catch (UniqueConstraintViolationException) {
            // Created a moment ago by the other caller. That is the desired end
            // state, so there is nothing to report.
        }
    }

    /** Flip a long-dead pending QR to expired. Returns the resulting state. */
    public function expireIfOverdue(Payment $payment): string
    {
        $deadline = $payment->expires_at?->copy()->addMinutes(self::EXPIRY_GRACE_MINUTES);

        if ($payment->status === 'pending' && $deadline && $deadline->isPast()) {
            $payment->forceFill(['status' => 'expired'])->save();
        }

        return $payment->status === 'expired' ? 'expired' : $payment->status;
    }

    /** True while this QR is still worth asking PayWay about. */
    public function isCheckable(Payment $payment): bool
    {
        return $this->isPayWay($payment) && filled($payment->invoice_no);
    }

    /** Was this row created by the PayWay integration? */
    public function isPayWay(Payment $payment): bool
    {
        return ($payment->gateway_response['gateway'] ?? null) === self::GATEWAY;
    }

    /**
     * The student's pending QR for this course, if it is still usable.
     *
     * Deliberately strict: a QR that is about to expire, or was made for a
     * different price or currency, is not reused -- the student would scan it
     * and have it die, or pay the wrong amount.
     */
    private function reusableQr(User $user, Course $course, float $amount): ?Payment
    {
        $payment = Payment::query()
            ->where('user_id', $user->id)
            ->where('course_id', $course->id)
            ->where('status', 'pending')
            ->latest('id')
            ->first();

        if (! $payment || ! $this->isPayWay($payment)) {
            return null;
        }

        $usable = filled($payment->qr)
            && strtoupper((string) $payment->currency) === $this->payway->currency()
            && abs((float) $payment->amount - $amount) < self::AMOUNT_TOLERANCE
            // At least a minute left: less than that and it dies mid-scan.
            && $payment->expires_at?->gt(now()->addMinute());

        return $usable ? $payment : null;
    }

    /**
     * Keep the whole gateway conversation on the row, without losing what was
     * there before (the generate-qr response, the sandbox flag).
     */
    private function mergeGateway(Payment $payment, array $result, string $source, array $extra = []): array
    {
        return array_merge($payment->gateway_response ?? [], [
            'gateway' => self::GATEWAY,
            'check' => $result['raw'] ?? [],
            'payment_status' => $result['status'] ?? null,
            'confirmed_by' => $source,
            'confirmed_at' => now()->toIso8601String(),
        ], $extra);
    }

    /**
     * transaction_hash is a unique column, so a value PayWay reuses (or an
     * empty one) would break an otherwise good payment. Fall back to the
     * invoice, which is unique by definition.
     */
    private function uniqueHash(mixed $value, Payment $payment): string
    {
        $value = $this->nullableString($value);

        if ($value === null) {
            return (string) $payment->invoice_no;
        }

        $taken = Payment::query()
            ->where('transaction_hash', $value)
            ->whereKeyNot($payment->getKey())
            ->exists();

        return $taken ? $payment->invoice_no.'-'.$value : $value;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';

        return $value === '' ? null : $value;
    }
}
