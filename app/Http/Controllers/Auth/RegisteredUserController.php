<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Rules\NotRestrictedName;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', new NotRestrictedName],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Laravel's built-in listener emails the signed verification link
        // here. A mail-server hiccup must not turn a finished sign-up into an
        // error page: the account exists, so log it and let the user press
        // "Resend" on the next screen.
        try {
            event(new Registered($user));
            EmailVerificationNotificationController::startCooldown($user);
            $emailSent = true;
        } catch (Throwable $e) {
            Log::error('Verification email could not be sent after registration.', [
                'user_id' => $user->id,
                'exception' => $e::class,
                'message' => $e->getMessage(),
            ]);
            $emailSent = false;
        }

        Auth::login($user);

        $redirect = redirect(route('verification.notice', absolute: false));

        return $emailSent
            ? $redirect
            : $redirect->withErrors([
                'verification' => __("Your account is ready, but we couldn't send the verification email. Please press \"Resend\" below."),
            ]);
    }
}
