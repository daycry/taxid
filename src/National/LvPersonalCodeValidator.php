<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Core\Checksum\Weights;

/**
 * Latvia — personas kods, eleven digits.
 *
 * Historically `DDMMYY` plus a century marker and a serial; since 2017 new
 * codes start with `32` and carry no birth date at all. Both forms share one
 * check digit: `(1101 - weighted sum) mod 11 mod 10`.
 *
 * Because the modern form has no date, the date is not validated here — only
 * the checksum, which is the part that actually holds for every issued code.
 */
final class LvPersonalCodeValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['LV'];
    protected const array SCHEMES   = ['personas_kods'];

    private const array WEIGHTS = [1, 6, 3, 7, 9, 10, 5, 8, 4, 2];

    public function verify(string $compact): bool
    {
        if (strlen($compact) !== 11 || ! ctype_digit($compact)) {
            return false;
        }

        $sum = Weights::sum(substr($compact, 0, 10), self::WEIGHTS);

        return (1101 - $sum) % 11 % 10 === (int) $compact[10];
    }
}
