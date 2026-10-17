<?php

namespace App\Http\Controllers;

use App\Http\Requests\ConfirmEmailChangeRequest;
use App\Services\EmailChangeVerifier;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

/**
 * Confirms, resends, or cancels the pending email-change code started by
 * ProfileController::update(). See App\Services\EmailChangeVerifier for the
 * actual rules (expiry, one-time use, attempt limit).
 */
class ProfileEmailChangeController extends Controller
{
    public function __construct(private readonly EmailChangeVerifier $verifier) {}

    /**
     * Send a fresh code for the already-pending email change.
     */
    public function resend(Request $request): RedirectResponse
    {
        $user = $request->user();
        $result = $this->verifier->resend($user);

        return match ($result) {
            'sent' => Redirect::route('profile.edit')->with('status', 'email-code-resent'),
            'cooldown' => Redirect::route('profile.edit')->withErrors([
                'resend' => self::cooldownMessage($this->verifier->secondsUntilResendAllowed($user)),
            ], 'emailChange'),
            'failed' => Redirect::route('profile.edit')->withErrors([
                'resend' => self::sendFailedMessage(),
            ], 'emailChange'),
            default => Redirect::route('profile.edit'),
        };
    }

    public static function cooldownMessage(int $seconds): string
    {
        return "A code was sent recently. You can request another one in {$seconds} ".($seconds === 1 ? 'second' : 'seconds').'.';
    }

    public static function sendFailedMessage(): string
    {
        return "We couldn't send the confirmation code right now. Please try again in a moment.";
    }

    /**
     * Confirm the code and, if correct, apply the pending email change.
     */
    public function confirm(ConfirmEmailChangeRequest $request): RedirectResponse
    {
        $result = $this->verifier->confirm($request->user(), $request->validated()['code']);

        if ($result === 'ok') {
            return Redirect::route('profile.edit')->with('status', 'email-updated');
        }

        $message = match ($result) {
            'expired' => 'This code has expired. Request a new one.',
            'too_many_attempts' => 'Too many incorrect attempts. Request a new code.',
            'none' => 'There is no pending email change to confirm.',
            default => 'That code is incorrect.',
        };

        return Redirect::route('profile.edit')->withErrors(['code' => $message], 'emailChange');
    }

    /**
     * Discard the pending email change without applying it.
     */
    public function cancel(Request $request): RedirectResponse
    {
        $this->verifier->cancel($request->user());

        return Redirect::route('profile.edit');
    }
}
