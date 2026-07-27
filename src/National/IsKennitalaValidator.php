<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Core\Checksum\Weights;

/**
 * Iceland — kennitala, ten digits: `DDMMYY`, a two-digit serial, a check digit
 * and finally a century marker (`9` for the 1900s, `0` for the 2000s).
 *
 * Weighted mod-11 with the key `32765432` over the first eight digits — the
 * same key Norway uses, and with the same consequence: a remainder of 1 makes
 * the number unissuable.
 *
 * Companies get a kennitala too, with 40 added to the day, which is why the day
 * field is allowed to run past 31.
 */
final class IsKennitalaValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['IS'];
    protected const array SCHEMES   = ['kennitala'];

    private const array WEIGHTS = [3, 2, 7, 6, 5, 4, 3, 2];

    public function verify(string $compact): bool
    {
        if (strlen($compact) !== 10 || ! ctype_digit($compact)) {
            return false;
        }

        $day   = (int) substr($compact, 0, 2);
        $month = (int) substr($compact, 2, 2);

        // Companies are offset by 40 in the day field.
        if ($day > 40) {
            $day -= 40;
        }

        if ($day < 1 || $day > 31 || $month < 1 || $month > 12) {
            return false;
        }

        $sum   = Weights::sum(substr($compact, 0, 8), self::WEIGHTS);
        $check = Weights::mod11ComplementDigit($sum);

        return $check !== null && $check === (int) $compact[8];
    }
}
