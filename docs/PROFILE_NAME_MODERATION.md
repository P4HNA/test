# Profile-Name Moderation

Students/users can choose their own display name at `/profile` and during
registration. Some names must be rejected: vulgar/sexual terms, offensive
terms, and known slang/obfuscated variants, in both Khmer script and
Latin/romanized Khmer slang.

## Where it lives

| Concern | File |
|---|---|
| The restricted-name dictionary (edit this to add/remove terms) | `config/name_moderation.php` |
| Normalization + matching logic (rarely needs to change) | `app/Support/NameModerator.php` |
| The validation rule wired into forms | `app/Rules/NotRestrictedName.php` |
| Where it's applied | `app/Http/Requests/ProfileUpdateRequest.php` (profile update), `app/Http/Controllers/Auth/RegisteredUserController.php` (registration) |
| Tests | `tests/Unit/NameModeratorTest.php`, plus cases in `tests/Feature/ProfileTest.php` and `tests/Feature/Auth/RegistrationTest.php` |

**To add or remove a term, only edit `config/name_moderation.php`.** No other
file should need to change.

## How matching works

- Before comparing, a name is Unicode-normalized (NFC), lowercased (Latin
  only — Khmer has no case), any run of repeated identical characters is
  collapsed to one for comparison purposes only (defeats letter-doubling
  tricks like `jjkkjj` for `jkj` — never affects what's actually saved), and
  whitespace/punctuation/separator characters are stripped.
- **Khmer terms** (`khmer_vulgar`, `khmer_obfuscated`, `khmer_fragments`) are
  matched as a **substring** of the cleaned name, because Khmer script isn't
  reliably space-separated into words — a blocked term could be embedded
  inside a longer chosen name with no space around it.
- **Latin/romanized terms** (`latin_slang`) are matched as a **whole
  word/token** of the cleaned name (or the entire cleaned name), never as a
  substring of a longer word. Short Latin letter sequences (3–4 characters)
  are too likely to appear innocently inside an unrelated word or name for
  substring matching to be safe.

## Why some entries are riskier than others

`khmer_fragments` (currently just `ក្ត`) is kept in its own list because it's
short enough that substring-matching it could, in principle, match inside a
legitimate Khmer name that happens to contain that syllable cluster. It's
blocked because it was specified as a hard platform requirement, but if
moderators see real users getting rejected, this is the first place to
investigate — consider whether the term needs a longer/more specific form
instead.

## Adding a new term

1. Decide which category it belongs to (vulgar/sexual, insulting/offensive,
   obfuscated/slang variant) and which list in `config/name_moderation.php`
   matches its script/matching behavior.
2. Add it to that list. Don't add ambiguous words that merely *resemble* an
   offensive term — that causes false positives. If you considered and
   rejected a candidate term, note it and why in the "Explicitly NOT blocked"
   comment block at the bottom of the config file, so the next person doesn't
   re-litigate it from scratch.
3. Add a case to `tests/Unit/NameModeratorTest.php` covering the new term
   (and, if it's a tricky one, a case confirming it does *not* over-match
   legitimate names).
4. Run `php artisan test`.

## Scope: what's covered and what isn't

- Covered: the `/profile` update form and the registration form — the two
  places a user directly types a name into a text field.
- **Not covered:** the Google Sign-In flow (`SocialAuthController`). A name
  created via Google Sign-In comes from the user's actual, Google-verified
  account name rather than free text they typed to deliberately pick
  something offensive, and rejecting it outright would block their ability
  to log in at all. If a student signs in with Google and their account name
  happens to look like a chosen slang/offensive name, they can still be
  asked to change it afterward via `/profile`, where it will be checked like
  any other name change. If moderation of Google-supplied names is wanted
  too, that's a separate design decision (e.g., silently substituting a
  default display name instead of blocking login) and hasn't been
  implemented here.
