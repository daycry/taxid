<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

/**
 * Cyprus — VAT number: eight digits and a check letter.
 *
 * Digits in odd positions (1-indexed) are pushed through an irregular
 * substitution table before being summed; even-position digits count as
 * themselves. The total `mod 26` indexes the alphabet to give the letter.
 *
 * The substitution table is the same idea as Italy's codice fiscale — a
 * deliberately non-linear map, so that swapping two digits almost always
 * changes the letter.
 */
final class CyVatValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['CY'];
    protected const array SCHEMES   = ['vat'];

    /** Odd-position substitution table, digit => contribution. */
    private const array ODD = [0 => 1, 1 => 0, 2 => 5, 3 => 7, 4 => 9, 5 => 13, 6 => 15, 7 => 17, 8 => 19, 9 => 21];

    public function verify(string $compact): bool
    {
        if (strlen($compact) !== 9) {
            return false;
        }

        $digits = substr($compact, 0, 8);
        $letter = $compact[8];

        if (! ctype_digit($digits) || ! ctype_upper($letter)) {
            return false;
        }

        // A registration never starts with 12.
        if (str_starts_with($digits, '12')) {
            return false;
        }

        $sum = 0;

        for ($i = 0; $i < 8; $i++) {
            $digit = (int) $digits[$i];
            $sum += $i % 2 === 0 ? self::ODD[$digit] : $digit;
        }

        return chr(ord('A') + $sum % 26) === $letter;
    }
}
