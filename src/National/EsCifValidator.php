<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

/**
 * Spain — CIF, the identifier of legal entities (now formally part of the NIF
 * series, but universally still called a CIF).
 *
 * Shape: an organisation-type letter, seven digits, and a control character.
 * The control is a Luhn-style sum over the seven digits — odd positions
 * doubled with their digits re-added, even positions taken as they are — folded
 * to `(10 - total mod 10) mod 10`.
 *
 * The twist is *how* that control is written, which depends on the leading
 * letter:
 *
 * - `A B E H` — public limited companies and similar: always a **digit**.
 * - `K P Q R S N W` — public bodies, congregations, non-residents: always a
 *   **letter**, taken from `JABCDEFGHI`.
 * - everything else (`C D F G J L M U V`): either form is legal, so both are
 *   accepted.
 */
final class EsCifValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['ES'];
    protected const array SCHEMES   = ['cif'];

    private const string CONTROL_LETTERS = 'JABCDEFGHI';
    private const string DIGIT_ONLY      = 'ABEH';
    private const string LETTER_ONLY     = 'KPQRSNW';

    public function verify(string $compact): bool
    {
        if (strlen($compact) !== 9) {
            return false;
        }

        $organisation = $compact[0];
        $digits       = substr($compact, 1, 7);
        $control      = $compact[8];

        if (! ctype_digit($digits)) {
            return false;
        }

        $sum = 0;

        for ($i = 0; $i < 7; $i++) {
            $digit = (int) $digits[$i];

            if ($i % 2 === 0) {
                // Odd position in the 1-indexed official description.
                $doubled = $digit * 2;
                $sum += intdiv($doubled, 10) + $doubled % 10;
            } else {
                $sum += $digit;
            }
        }

        $expectedDigit  = (10 - $sum % 10) % 10;
        $expectedLetter = self::CONTROL_LETTERS[$expectedDigit];

        if (str_contains(self::DIGIT_ONLY, $organisation)) {
            return $control === (string) $expectedDigit;
        }

        if (str_contains(self::LETTER_ONLY, $organisation)) {
            return $control === $expectedLetter;
        }

        return $control === (string) $expectedDigit || $control === $expectedLetter;
    }
}
