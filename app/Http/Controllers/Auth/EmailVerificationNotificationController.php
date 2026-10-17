<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

class EmailVerificationNotificationController extends Controller
{
    /**
     * Minimum gap between two verification emails to the same account. Kept
     * in the app cache (RateLimiter), so it survives a page refresh. Same
     * mechanism and length as the email-change code cooldown.
     */
    public const COOLDOWN_SECONDS = 60;

    /**
     * Send a new email verification notification.
     */
    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        $wait = self::secondsUntilResendAllowed($user);

        if ($wait > 0) {
            return back()->withErrors([
                'verification' => __('We just sent you an email. You can request another in :seconds seconds.', ['seconds' => $wait]),
            ]);
        }

        if (! self::send($user)) {
            return back()->withErrors([
                'verification' => __("We couldn't send the email right now. Please try again in a moment."),
            ]);
        }

        return back()->with('status', 'verification-link-sent');
    }

    /**
     * Email the verification link and start the cooldown. Returns false (and
     * logs the reason) if the mail server refused it, so callers can tell the
     * user instead of crashing.
     */
    public static function send(User $user): bool
    {
        try {
            $user->sendEmailVerificationNotification();
        } catch (Throwable $e) {
            Log::error('Verification email could not be sent.', [
                'user_id' => $user->id,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);

            return false;
        }

        self::startCooldown($user);

        return true;
    }

    public static function startCooldown(User $user): void
    {
        RateLimiter::hit(self::cooldownKey($user), self::COOLDOWN_SECONDS);
    }

    public static function secondsUntilResendAllowed(User $user): int
    {
        $key = self::cooldownKey($user);

        return RateLimiter::tooManyAttempts($key, 1) ? max(1, RateLimiter::availableIn($key)) : 0;
    }

    private static function cooldownKey(User $user): string
    {
        return 'verify-email:'.$user->id;
    }
}
