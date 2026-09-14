<?php

namespace App\Support;

/**
 * The single normalization mechanism for skill names, aliases and search terms.
 *
 * Deliberately conservative: symbols that distinguish real skills from one
 * another (`+` in C++, `#` in C#, `.` in Vue.js) are preserved, so `c`, `c++`
 * and `c#` never collapse into the same normalized value.
 */
class SkillNameNormalizer
{
    /**
     * Normalize a skill name, alias or search term for comparison.
     *
     * Rules, in order:
     *  1. trim leading/trailing whitespace
     *  2. lowercase
     *  3. treat `/` as a word separator ("UI / UX" and "UI UX" agree)
     *  4. collapse any run of whitespace into a single space
     */
    public static function normalize(?string $value): string
    {
        $value = mb_strtolower(trim((string) $value));
        $value = str_replace('/', ' ', $value);
        $value = (string) preg_replace('/\s+/u', ' ', $value);

        return trim($value);
    }

    /**
     * Whether two values are equivalent once normalized.
     */
    public static function matches(?string $first, ?string $second): bool
    {
        $first = self::normalize($first);

        return $first !== '' && $first === self::normalize($second);
    }
}
