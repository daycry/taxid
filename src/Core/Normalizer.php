<?php

declare(strict_types=1);

namespace Daycry\TaxId\Core;

/**
 * Canonicalizes raw input before anything else looks at it.
 *
 * Purely mechanical and non-judgmental: uppercase, then drop whitespace and the
 * separators humans actually type into a tax ID — `.`, `-`, `+`, `/`, `\`, `_`,
 * `(`, `)`, `[`, `]`, `:`, `,`, `'`. So `12.345.678-Z`, `12 345 678 Z` and
 * `12345678z` all become `12345678Z`. The `+` earns its place through Finland,
 * whose henkilötunnus uses it as the century marker for the 1800s.
 *
 * What it deliberately does **not** do:
 *
 * - validate anything, or drop characters it doesn't recognise. An `@` survives
 *   normalization precisely so {@see Validator} can report `IllegalCharacters`
 *   instead of silently "fixing" the input.
 * - strip a country/VAT prefix. That needs registry knowledge and is
 *   {@see SchemeDetector}'s job, which only strips a prefix when doing so
 *   actually produces a recognised value.
 *
 * Running a check before normalization (e.g. matching a pattern against
 * lowercase input) is the classic ordering bug this class exists to prevent.
 */
final class Normalizer
{
    /**
     * Characters treated as noise. Kept as a character class rather than a
     * `str_replace()` list so the regex engine does it in one pass.
     */
    private const SEPARATORS = '/[\s.\-+\/\\\\_()\[\]:,\']+/';

    public function normalize(string $taxId): string
    {
        return (string) preg_replace(self::SEPARATORS, '', strtoupper($taxId));
    }
}
