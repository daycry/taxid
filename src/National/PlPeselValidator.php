<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Core\Checksum\Weights;

/**
 * Poland — PESEL, the eleven-digit personal number, which doubles as the tax
 * identifier for individuals not running a business.
 *
 * Weights 1,3,7,9 repeating over the first ten digits, check digit
 * `(10 - sum mod 10) mod 10`.
 *
 * The leading `YYMMDD` encodes the birth date with the month offset by 20, 40,
 * 60 or 80 for centuries other than the 1900s. That is not verified here: the
 * date is data, not a checksum, and rejecting an unusual-looking one would
 * simply be wrong.
 */
final class PlPeselValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['PL'];
    protected const array SCHEMES   = ['pesel'];

    private const array WEIGHTS = [1, 3, 7, 9, 1, 3, 7, 9, 1, 3];

    public function verify(string $compact): bool
    {
        if (strlen($compact) !== 11 || ! ctype_digit($compact)) {
            return false;
        }

        $sum = Weights::sum(substr($compact, 0, 10), self::WEIGHTS);

        return (10 - $sum % 10) % 10 === (int) $compact[10];
    }
}
