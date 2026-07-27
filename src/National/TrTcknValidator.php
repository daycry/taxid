<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

/**
 * Türkiye — T.C. Kimlik No, the eleven-digit citizen number, which doubles as
 * the personal tax number.
 *
 * Two check digits, both simple and both required:
 *
 * - the tenth is `(7 × sum of the odd positions − sum of the even positions) mod 10`;
 * - the eleventh is the sum of the first ten digits `mod 10`.
 *
 * The first digit is never zero.
 */
final class TrTcknValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['TR'];
    protected const array SCHEMES   = ['tckn'];

    public function verify(string $compact): bool
    {
        if (strlen($compact) !== 11 || ! ctype_digit($compact) || $compact[0] === '0') {
            return false;
        }

        $odd  = 0;   // positions 1, 3, 5, 7, 9
        $even = 0;   // positions 2, 4, 6, 8

        for ($i = 0; $i < 9; $i++) {
            if ($i % 2 === 0) {
                $odd += (int) $compact[$i];
            } else {
                $even += (int) $compact[$i];
            }
        }

        $tenth = (7 * $odd - $even) % 10;

        if ($tenth < 0) {
            $tenth += 10;
        }

        if ($tenth !== (int) $compact[9]) {
            return false;
        }

        return ($odd + $even + $tenth) % 10 === (int) $compact[10];
    }
}
