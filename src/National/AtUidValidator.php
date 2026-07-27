<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

/**
 * Austria — Umsatzsteuer-Identifikationsnummer (UID): the letter `U` followed
 * by eight digits.
 *
 * The `U` is part of the *national* number, not of the `AT` country prefix —
 * which is why the compact form here keeps it and the invoice form reads
 * `ATU12345678`.
 *
 * Alternating 1/2 weighting over the seven digits before the check, with
 * doubled values folded to their digit sum, closed by `(96 - total) mod 10`.
 * The odd-looking 96 is just `100 - 4` chosen so the result lands in 0–9.
 */
final class AtUidValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['AT'];
    protected const array SCHEMES   = ['uid'];

    public function verify(string $compact): bool
    {
        if (strlen($compact) !== 9 || $compact[0] !== 'U') {
            return false;
        }

        $digits = substr($compact, 1);

        if (! ctype_digit($digits)) {
            return false;
        }

        $sum = 0;

        for ($i = 0; $i < 7; $i++) {
            $digit = (int) $digits[$i];

            if ($i % 2 === 1) {
                $digit *= 2;
                $digit  = intdiv($digit, 10) + $digit % 10;
            }

            $sum += $digit;
        }

        return (96 - $sum) % 10 === (int) $digits[7];
    }
}
