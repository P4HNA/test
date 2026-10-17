<?php

namespace App\Rules;

use App\Support\NameModerator;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Rejects profile/display names that match the restricted-name dictionary
 * (see config/name_moderation.php). Shows a single, profile-friendly
 * message rather than exposing any matching details.
 */
class NotRestrictedName implements ValidationRule
{
    /**
     * Explains the policy without naming which word matched, so the
     * dictionary itself is never revealed.
     */
    public const MESSAGE = 'This name isn’t allowed. Inappropriate, offensive, or vulgar names can’t be used — please choose a different name.';

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        if (NameModerator::isRestricted($value)) {
            $fail(self::MESSAGE);
        }
    }
}
