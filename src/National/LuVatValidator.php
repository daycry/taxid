<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

/**
 * Luxembourg — numéro de TVA, eight digits.
 *
 * The last two digits are the first six taken `mod 89`. Unusually small
 * modulus, and unusually simple: no weighting at all.
 */
final class LuVatValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['LU'];
    protected const array SCHEMES   = ['vat'];

    public function verify(string $compact): bool
    {
        if (strlen($compact) !== 8 || ! ctype_digit($compact)) {
            return false;
        }

        return (int) substr($compact, 6, 2) === ((int) substr($compact, 0, 6)) % 89;
    }
}
