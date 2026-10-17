<?php

namespace App\Support;

/**
 * Detects restricted/offensive profile names.
 *
 * The dictionary lives in config/name_moderation.php -- this class only
 * contains the normalization/matching logic, so new terms never require a
 * code change (see that config file for how to add entries).
 */
class NameModerator
{
    /**
     * Characters treated as separators/punctuation when comparing names:
     * whitespace, dots, dashes, underscores, asterisks, quotes, slashes, etc.
     */
    private const SEPARATOR_PATTERN = '/[\s.\-_*~,\'"|\/\\\\]+/u';

    /**
     * Determine whether a given (not-yet-saved) profile name is restricted.
     */
    public static function isRestricted(string $rawName): bool
    {
        if (trim($rawName) === '') {
            return false;
        }

        $squashedName = self::squash($rawName);

        // Khmer terms: matched as a substring of the fully cleaned name,
        // since Khmer script isn't reliably space-separated into words.
        foreach (self::khmerTerms() as $term) {
            $squashedTerm = self::squash($term);

            if ($squashedTerm !== '' && str_contains($squashedName, $squashedTerm)) {
                return true;
            }
        }

        // Latin/romanized slang: matched as a whole word/token (or as the
        // entire squashed name), never as a substring of a longer word --
        // short letter sequences are too likely to appear innocently inside
        // an unrelated word otherwise.
        $latinTerms = self::latinTerms();

        if (in_array($squashedName, $latinTerms, true)) {
            return true;
        }

        foreach (self::tokens($rawName) as $token) {
            if (in_array($token, $latinTerms, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * All Khmer-script blocked terms from config, merged into one list.
     *
     * @return array<int, string>
     */
    private static function khmerTerms(): array
    {
        return [
            ...config('name_moderation.khmer_vulgar', []),
            ...config('name_moderation.khmer_obfuscated', []),
            ...config('name_moderation.khmer_fragments', []),
        ];
    }

    /**
     * All Latin/romanized blocked terms from config, pre-squashed for
     * comparison.
     *
     * @return array<int, string>
     */
    private static function latinTerms(): array
    {
        return array_values(array_map(
            static fn (string $term): string => self::squash($term),
            config('name_moderation.latin_slang', [])
        ));
    }

    /**
     * Normalize a string for comparison:
     *  - Unicode-normalize to NFC (so combined vs. precomposed Khmer/Latin
     *    characters compare equal),
     *  - lowercase (affects Latin only; Khmer script has no case),
     *  - collapse any run of repeated identical characters down to one
     *    occurrence (defeats letter-doubling obfuscation like "jjkkjj" for
     *    "jkj") -- this is comparison-only and never touches the name the
     *    user actually sees or that gets saved; the tiny blocklist here
     *    makes false collisions from this (e.g. "Anna" -> "Ana") very
     *    unlikely, but review this if the dictionary grows a lot,
     *  - strip whitespace/punctuation/separator characters entirely.
     */
    public static function squash(string $value): string
    {
        if (class_exists(\Normalizer::class)) {
            $normalized = \Normalizer::normalize($value, \Normalizer::FORM_C);
            $value = $normalized !== false ? $normalized : $value;
        }

        $value = mb_strtolower($value, 'UTF-8');
        $value = preg_replace('/(.)\1+/us', '$1', $value) ?? $value;
        $value = preg_replace(self::SEPARATOR_PATTERN, '', $value) ?? $value;

        return trim($value);
    }

    /**
     * Split a name into normalized comparison tokens, using the same
     * separators as squash() but preserved as split points instead of being
     * removed outright.
     *
     * @return array<int, string>
     */
    private static function tokens(string $rawName): array
    {
        if (class_exists(\Normalizer::class)) {
            $normalized = \Normalizer::normalize($rawName, \Normalizer::FORM_C);
            $rawName = $normalized !== false ? $normalized : $rawName;
        }

        $value = mb_strtolower($rawName, 'UTF-8');
        $value = preg_replace('/(.)\1+/us', '$1', $value) ?? $value;

        $tokens = preg_split(self::SEPARATOR_PATTERN, $value, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_map('trim', $tokens);
    }
}
