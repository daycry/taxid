<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

/**
 * Netherlands — burgerservicenummer (BSN), the nine-digit citizen number used
 * as the personal tax identifier.
 *
 * The *elfproef*: weights 9 down to 2 over the first eight digits, then the
 * ninth subtracted rather than added; the total must be divisible by 11. That
 * final minus sign is the whole trick — it makes the ninth digit a genuine
 * check digit instead of just another weighted term.
 */
final class NlBsnValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['NL'];
    protected const array SCHEMES   = ['bsn'];

    public function verify(string $compact): bool
    {
        if (strlen($compact) !== 9 || ! ctype_digit($compact)) {
            return false;
        }

        if ($compact === '000000000') {
            return false;
        }

        $total = 0;

        for ($i = 0; $i < 8; $i++) {
            $total += ((int) $compact[$i]) * (9 - $i);
        }

        $total -= (int) $compact[8];

        return $total % 11 === 0;
    }
}
