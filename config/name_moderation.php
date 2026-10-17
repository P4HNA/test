<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Restricted profile-name dictionary
    |--------------------------------------------------------------------------
    |
    | This is the single place to add/remove restricted profile-name terms.
    | Do not scatter new hard-coded checks in controllers/requests -- add
    | entries here instead. Keep the list reviewed and intentional: only add
    | a term once you're confident it is genuinely vulgar, sexual, or
    | intentionally offensive -- not merely a word that "sounds like" one.
    |
    | Matching behaviour (see App\Support\NameModerator):
    |   - "khmer_*" lists are matched as a SUBSTRING inside the user's fully
    |     cleaned name. Khmer script isn't reliably space-separated into
    |     words, so substring matching is necessary to catch a blocked term
    |     embedded in a longer chosen name. This means short Khmer entries
    |     carry a higher false-positive risk than longer ones -- see
    |     "khmer_fragments" below.
    |   - "latin_slang" entries are matched as a WHOLE WORD/TOKEN of the
    |     cleaned name (never as a substring of a longer word). Short Latin
    |     letter sequences (3-4 chars) are far more likely to appear
    |     innocently inside an unrelated word or name, so substring matching
    |     would be too aggressive for these.
    |
    | All comparisons are done after Unicode normalization (NFC), lowercasing
    | (for Latin), collapsing any run of repeated characters down to one
    | (comparison-only -- defeats letter-doubling tricks like "jjkkjj" for
    | "jkj"), and stripping spaces/punctuation/separators -- see
    | App\Support\NameModerator::squash().
    |
    */

    // Clearly vulgar / sexual terms (Khmer script).
    'khmer_vulgar' => [
        'ចុយ',
        'ក្តួយ',
    ],

    // Known obfuscated / reversed-name tricks derived from offensive wording.
    // Add the literal obfuscated string here, not the "real" word it hides.
    'khmer_obfuscated' => [
        'ជាខ្នុយ',
    ],

    // Short Khmer fragments that must be blocked per platform requirements,
    // kept in their own list because substring-matching a very short
    // sequence carries a real risk of matching inside an unrelated,
    // legitimate Khmer name. If moderators see legitimate names being
    // rejected, investigate entries here first.
    'khmer_fragments' => [
        'ក្ត',
    ],

    // Romanized / Latin-typed Khmer slang and obfuscated variants, plus a
    // few unambiguous English profanities. Matched as a whole word/token,
    // never a substring -- see note above. Avoid short English words whose
    // repeated-letter collapse would hit real names (e.g. "ass" -> "as").
    'latin_slang' => [
        'ov hg',
        'kdmv',
        'jkj',
        'jmr',

        // English
        // Whole-word matching means each inflection needs its own entry.
        'fuck',
        'fucks',
        'fucker',
        'fuckers',
        'fucking',
        'fuckin',
        'fucked',
        'motherfucker',
        'motherfuckers',
        'motherfucking',
        'shit',
        'shits',
        'shitty',
        'bullshit',
        'bitch',
        'bitches',
        'cunt',
        'cunts',
        'whore',
        'whores',
        'slut',
        'sluts',
    ],

    /*
    |--------------------------------------------------------------------------
    | Explicitly NOT blocked (for the record)
    |--------------------------------------------------------------------------
    |
    | Words that merely resemble a blocked term, or common short strings that
    | would collide with legitimate names/initials under naive matching, are
    | deliberately left out of the lists above. Document any such decision
    | here so a future maintainer doesn't accidentally "re-add" it without
    | context.
    |
    | (No exclusions recorded yet -- add a short note + reasoning here if a
    | future candidate term is investigated and rejected.)
    |
    */

];
