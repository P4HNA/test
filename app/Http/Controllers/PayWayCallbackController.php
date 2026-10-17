<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Services\PayWayVerifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Where ABA PayWay tells us a payment happened.
 *
 * Unauthenticated and CSRF-exempt by necessity -- the caller is PayWay's server,
 * which has no session and no token from us (see bootstrap/app.php).
 *
 * That makes the request itself untrustworthy: anyone who finds this URL can
 * POST whatever they like to it. So nothing in the body is believed. The only
 * thing taken from it is the transaction id, which is used to go and ask PayWay
 * directly what happened. A forged callback naming someone else's transaction
 * therefore achieves exactly what a real one would, and one naming a
 * transaction that was never paid achieves nothing.
 */
class PayWayCallbackController extends Controller
{
    /**
     * How often one paid payment may be re-asked about after a callback.
     *
     * Long enough to swallow PayWay's retry storm, short enough that a refund
     * notification is never the one that gets dropped.
     */
    private const RECHECK_EVERY_SECONDS = 300;

    public function __invoke(Request $request, PayWayVerifier $verifier): JsonResponse
    {
        // Logged first and in full: when a payment goes wrong this is often the
        // only record of what PayWay actually sent.
        Log::info('PayWay callback received', [
            'ip' => $request->ip(),
            'content_type' => $request->header('Content-Type'),
            'payload' => $request->all(),
            // json/form decoding can silently drop a malformed body, so keep
            // the bytes too (capped -- this is a log, not storage).
            'raw' => mb_substr($request->getContent(), 0, 2000),
        ]);

        $tranId = $this->tranId($request);

        if ($tranId === null) {
            Log::warning('PayWay callback had no transaction id — ignored');

            // 200, not 4xx: PayWay retries on an error status, and retrying will
            // not put an id into a body that never had one.
            return $this->ack('ignored');
        }

        $payment = Payment::query()->where('invoice_no', $tranId)->first();

        if (! $payment) {
            Log::warning('PayWay callback for an unknown transaction — ignored', [
                'tran_id' => $tranId,
            ]);

            return $this->ack('unknown');
        }

        if (! $verifier->isPayWay($payment)) {
            // A Bakong-era invoice. PayWay has no such transaction to confirm.
            Log::warning('PayWay callback matched a non-PayWay payment — ignored', [
                'tran_id' => $tranId,
            ]);

            return $this->ack('ignored');
        }

        try {
            // The callback is only a nudge. This is what actually decides.
            //
            // A row we already think is paid still gets re-asked, because for a
            // paid payment the thing that can have changed is a refund, and
            // settle() short-circuits on paid (so the page's 3-second poll stays
            // free). But not on every retry: PayWay retries this callback, and
            // those retries arrive within moments of the payment, so asking each
            // time would turn one confirmation into five identical questions.
            // The throttle below lets the first one through and drops the storm,
            // while a real refund -- which arrives hours or days later -- is
            // always outside the window and always asked about.
            if (! $payment->isPaid()) {
                // Never throttled: a retry arriving while the payment is still
                // pending is one we WANT to ask about, because the answer may
                // have changed to APPROVED since the last attempt.
                $state = $verifier->settle($payment);

                // We have just heard from PayWay about this transaction, so the
                // retries following this one have nothing new to learn.
                if ($state === 'paid') {
                    $this->markAsked($payment);
                }
            } elseif ($this->mayRecheck($payment)) {
                $state = $verifier->recheckPaid($payment);
            } else {
                $state = 'paid';
            }
        } catch (Throwable $e) {
            report($e);

            Log::error('PayWay callback could not be confirmed with check-transaction', [
                'tran_id' => $tranId,
                'error' => $e->getMessage(),
            ]);

            // 500 on purpose: we could not reach PayWay, so a retry is exactly
            // what we want. The page's own polling covers us if it never comes.
            return response()->json(['status' => 'retry'], 500);
        }

        Log::info('PayWay callback handled', [
            'tran_id' => $tranId,
            'state' => $state,
        ]);

        return $this->ack($state);
    }

    /**
     * True at most once per payment per window, for re-asking about a paid row.
     *
     * Cache::add is atomic, so two retries arriving together cannot both win. On
     * a cache failure this returns true: missing a refund is worse than one extra
     * question to PayWay.
     */
    private function mayRecheck(Payment $payment): bool
    {
        try {
            return Cache::add(
                $this->recheckKey($payment),
                true,
                now()->addSeconds(self::RECHECK_EVERY_SECONDS)
            );
        } catch (Throwable) {
            return true;
        }
    }

    /** Record that PayWay has just been asked about this payment. */
    private function markAsked(Payment $payment): void
    {
        try {
            Cache::put($this->recheckKey($payment), true, now()->addSeconds(self::RECHECK_EVERY_SECONDS));
        } catch (Throwable) {
            // A cache that cannot be written only costs an extra question later.
        }
    }

    private function recheckKey(Payment $payment): string
    {
        return 'payway_recheck_'.$payment->getKey();
    }

    /**
     * The transaction id, wherever PayWay decided to put it this time.
     *
     * The field differs between PayWay's own products and API versions, so all
     * three documented spellings are accepted.
     */
    private function tranId(Request $request): ?string
    {
        foreach (['tran_id', 'merchant_ref', 'transaction_id'] as $key) {
            $value = $request->input($key);

            if (is_scalar($value) && trim((string) $value) !== '') {
                return trim((string) $value);
            }
        }

        return null;
    }

    private function ack(string $state): JsonResponse
    {
        return response()->json(['status' => 'ok', 'state' => $state]);
    }
}
