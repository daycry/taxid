<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Core\Checksum\Weights;

/**
 * Estonia — KMKR number (VAT), nine digits.
 *
 * Weights 3,7,1 repeating over the first eight digits, check digit
 * `(10 - sum mod 10) mod 10`. A mod-10 close rather than mod-11, so every
 * combination of the first eight digits yields a usable check digit and no
 * numbers have to be skipped.
 */
final class EeVatValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['EE'];
    protected const array SCHEMES   = ['vat'];

    private const array WEIGHTS = [3, 7, 1, 3, 7, 1, 3, 7];

    public function verify(string $compact): bool
    {
        if (strlen($compact) !== 9 || ! ctype_digit($compact)) {
            return false;
        }

        $sum = Weights::sum(substr($compact, 0, 8), self::WEIGHTS);

        return (10 - $sum % 10) % 10 === (int) $compact[8];
    }
}
