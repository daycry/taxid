<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

/**
 * Japan — corporate number (法人番号), thirteen digits.
 *
 * The check digit comes **first**, not last: the twelve digits behind it are
 * weighted 1 and 2 alternately from the right, summed, and the leading digit is
 * `9 - (sum mod 9)`.
 *
 * Individuals use the twelve-digit My Number instead, which the registry keeps
 * as a separate scheme.
 */
final class JpCorporateNumberValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['JP'];
    protected const array SCHEMES   = ['houjinbangou'];

    public function verify(string $compact): bool
    {
        if (strlen($compact) !== 13 || ! ctype_digit($compact)) {
            return false;
        }

        $body = substr($compact, 1);
        $sum  = 0;

        // n counts from 1 at the rightmost digit of the 12-digit body.
        for ($n = 1; $n <= 12; $n++) {
            $digit = (int) $body[12 - $n];
            $sum += $digit * ($n % 2 === 1 ? 1 : 2);
        }

        return 9 - $sum % 9 === (int) $compact[0];
    }
}
