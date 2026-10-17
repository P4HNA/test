<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\User;
use App\Support\NameModerator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    /**
     * Redirect the user to the provider authentication page.
     */
    public function redirect($provider)
    {
        if ($provider !== 'google') {
            abort(404);
        }

        return Socialite::driver($provider)->redirect();
    }

    /**
     * Obtain the user information from the provider.
     */
    public function callback(Request $request, $provider)
    {
        if ($provider !== 'google') {
            abort(404);
        }

        // Handle error when user cancels
        if ($request->has('error')) {
            return redirect()->route('login')->with('status', 'Authentication canceled.');
        }

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Exception $e) {
            Log::error("{$provider} OAuth failed", ['exception' => $e]);

            return redirect()->route('login')->withErrors(['email' => 'Unable to authenticate with '.ucfirst($provider).'. Please try again.']);
        }

        $email = $socialUser->getEmail();

        if (! $email) {
            return redirect()->route('login')->withErrors(['email' => 'No email address provided by '.ucfirst($provider).'.']);
        }

        // Google says whether it has confirmed this address belongs to the
        // person signing in (the "email_verified" claim of its userinfo).
        $googleVerifiedEmail = (bool) (((array) ($socialUser->user ?? []))['email_verified'] ?? false);

        // Look for an existing linked social account
        $account = SocialAccount::where('provider', $provider)
            ->where('provider_id', $socialUser->getId())
            ->first();

        if ($account) {
            $user = $account->user;

            if ($googleVerifiedEmail && ! $user->hasVerifiedEmail() && strcasecmp($user->email, $email) === 0) {
                $user->markEmailAsVerified();
            }
        } else {
            // Find an existing user by email
            $user = User::where('email', $email)->first();

            if ($user) {
                // Linking Google to an existing account is only safe when
                // Google has confirmed the address -- otherwise anyone could
                // attach a Google login to someone else's account.
                if (! $googleVerifiedEmail) {
                    return redirect()->route('login')->withErrors([
                        'email' => __('Google has not confirmed this email address yet, so we cannot link it to your account. Please log in with your password instead.'),
                    ]);
                }

                // Pre-hijack guard: someone may have registered this address
                // first (never verifying it) and still know its password.
                // Google has now proven who really owns the address, so that
                // unverified password stops working. The real owner signs in
                // with Google, or uses "Forgot password" to set a new one.
                if (! $user->hasVerifiedEmail()) {
                    $user->forceFill([
                        'password' => Hash::make(Str::random(64)),
                        'remember_token' => Str::random(60),
                        'email_verified_at' => now(),
                    ])->save();
                }
            } else {
                // New account: the Google profile name goes through the same
                // moderation as a typed-in name (registration/profile). A
                // restricted name means no account is created at all.
                $name = trim((string) ($socialUser->getName() ?? $socialUser->getNickname() ?? '')) ?: 'User';

                if (NameModerator::isRestricted($name)) {
                    return redirect()->route('login')->withErrors([
                        'email' => __("We couldn't create your account because the name on your Google profile isn't allowed here. Please sign up with your email and choose a different name."),
                    ]);
                }

                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make(Str::random(32)),
                    'role' => 'student',
                ]);

                if ($googleVerifiedEmail) {
                    $user->markEmailAsVerified();
                } else {
                    // Rare (e.g. an unconfirmed Workspace address): verify it
                    // the normal way. A mail failure is logged; "Resend" works.
                    EmailVerificationNotificationController::send($user);
                }
            }

            // Create the social account link
            $user->socialAccounts()->create([
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
                'provider_email' => $email,
                'provider_name' => $socialUser->getName(),
                'provider_avatar' => $socialUser->getAvatar(),
            ]);
        }

        if ($user->isBanned()) {
            return redirect()->route('login')->withErrors([
                'email' => __('This account has been suspended. Contact support if you think this is a mistake.'),
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();

        // Social login acts as a recent secure authentication
        $request->session()->put('auth.password_confirmed_at', time());

        return redirect()->intended(route('dashboard', absolute: false));
    }
}
