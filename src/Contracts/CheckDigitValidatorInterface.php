<?php

declare(strict_types=1);

namespace Daycry\TaxId\Contracts;

/**
 * A country/scheme-specific check-digit algorithm.
 *
 * One implementation per algorithm, wired to a scheme through the registry's
 * `check_digit` entry (a class-string). Implementations MUST be stateless and
 * cheap to construct — {@see \Daycry\TaxId\Core\CheckDigitValidatorFactory}
 * instantiates and memoizes exactly one instance per class.
 *
 * A scheme with **no** validator is a deliberate skip, never a failure: many
 * jurisdictions publish a format but no verifier algorithm.
 *
 * @see docs/superpowers/specs/2026-07-27-daycry-taxid-v1-design.md §5
 * @see docs/adding-a-country.md
 */
interface CheckDigitValidatorInterface
{
    /**
     * Whether this validator is the right one for that country/scheme pair.
     *
     * Guards against a registry typo silently wiring the wrong algorithm; the
     * data-integrity test asserts it for every bundled scheme.
     *
     * @param string $countryCode ISO 3166-1 alpha-2, uppercase.
     * @param string $scheme      Registry scheme code, lowercase.
     */
    public function supports(string $countryCode, string $scheme): bool;

    /**
     * Verify the check digit(s) of an already-normalized, already-pattern-matched
     * value.
     *
     * Receives the **compact** form (uppercase, unseparated, no VAT prefix) and
     * may assume it matches the scheme's pattern. MUST never throw.
     */
    public function verify(string $compact): bool;
}
