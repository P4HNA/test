<?php

namespace App\Services;

use App\Models\EmailChangeRequest;
use App\Models\User;
use App\Notifications\EmailChangeVerificationCode;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

/**
 * Handles the email-change verification-code flow: a change to the user's
 * email is applied only once they prove they control the new inbox by
 * entering the code sent there. See ProfileController::update() (which
 * starts a change) and ProfileEmailChangeController (which confirms,
 * resends, or cancels one).
 */
class EmailChangeVerifier
{
    private const CODE_EXPIRES_IN_MINUTES = 10;

    private const MAX_ATTEMPTS = 5;

    /**
     * Minimum gap between two code emails for the same user, whether the
     * send came from the profile form or the "Resend code" button. Kept in
     * the app cache via RateLimiter, so it survives a page refresh.
     */
    public const RESEND_COOLDOWN_SECONDS = 60;

    /**
     * Start (or restart) a pending email change: generate a new code,
     * store its hash, and email the code to the NEW address -- never the
     * current one, since the point is to prove the user controls it.
     *
     * Overwriting the hash means any previously sent code stops working.
     * Refused while the resend cooldown is still running.
     *
     * @return 'sent'|'cooldown'|'failed'
     */
    public function start(User $user, string $newEmail): string
    {
        $cooldownKey = $this->cooldownKey($user);

        if (RateLimiter::tooManyAttempts($cooldownKey, 1)) {
            return 'cooldown';
        }

        RateLimiter::hit($cooldownKey, self::RESEND_COOLDOWN_SECONDS);

        $code = $this->generateCode();

        EmailChangeRequest::updateOrCreate(
            ['user_id' => $user->id],
            [
                'new_email' => $newEmail,
                'code_hash' => Hash::make($code),
                'attempts' => 0,
                'expires_at' => now()->addMinutes(self::CODE_EXPIRES_IN_MINUTES),
            ]
        );

        // Sent right away (the notification is deliberately not queued), so
        // a missing queue worker can't silently hold the code back, and the
        // plaintext code is never written to the jobs table.
        try {
            Notification::route('mail', $newEmail)
                ->notify(new EmailChangeVerificationCode($code, $newEmail));
        } catch (Throwable $e) {
            // Nothing left the server, so don't make the user wait out a
            // cooldown before retrying. Only the error class and transport
            // message are logged -- never the code.
            RateLimiter::clear($cooldownKey);

            Log::error('Email-change verification code could not be sent.', [
                'user_id' => $user->id,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return 'failed';
        }

        return 'sent';
    }

    /**
     * Resend a code for whichever email change is already pending, if any.
     *
     * @return 'sent'|'cooldown'|'failed'|'none'
     */
    public function resend(User $user): string
    {
        $pending = $this->pendingFor($user);

        if (! $pending) {
            return 'none';
        }

        return $this->start($user, $pending->new_email);
    }

    /**
     * Seconds left before another code may be sent (0 when allowed now).
     */
    public function secondsUntilResendAllowed(User $user): int
    {
        $key = $this->cooldownKey($user);

        if (! RateLimiter::tooManyAttempts($key, 1)) {
            return 0;
        }

        return max(1, RateLimiter::availableIn($key));
    }

    public function pendingFor(User $user): ?EmailChangeRequest
    {
        return EmailChangeRequest::where('user_id', $user->id)->first();
    }

    /**
     * Discard a pending email change without applying it.
     */
    public function cancel(User $user): void
    {
        $this->pendingFor($user)?->delete();
    }

    /**
     * @return 'ok'|'invalid'|'expired'|'too_many_attempts'|'none'
     */
    public function confirm(User $user, string $code): string
    {
        $pending = $this->pendingFor($user);

        if (! $pending) {
            return 'none';
        }

        if ($pending->expires_at->isPast()) {
            $pending->delete();

            return 'expired';
        }

        if ($pending->attempts >= self::MAX_ATTEMPTS) {
            $pending->delete();

            return 'too_many_attempts';
        }

        if (! Hash::check($code, $pending->code_hash)) {
            $pending->increment('attempts');

            return 'invalid';
        }

        // The code proved the user controls the new inbox, so it is safe
        // to mark the new address verified immediately -- no second,
        // separate "click this link" step needed.
        $user->forceFill([
            'email' => $pending->new_email,
            'email_verified_at' => now(),
        ])->save();

        $pending->delete();

        return 'ok';
    }

    private function cooldownKey(User $user): string
    {
        return 'email-change-code:'.$user->id;
    }

    private function generateCode(): string
    {
        return (string) random_int(100000, 999999);
    }
}
