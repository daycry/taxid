<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Core\Checksum\Weights;

/**
 * Australia — ACN (Australian Company Number), nine digits.
 *
 * Descending weights 8…1 over the first eight digits, check digit
 * `(10 - sum mod 10) mod 10`. A company's ABN is its ACN with a two-digit
 * prefix, which is why the two checks are independent.
 */
final class AuAcnValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['AU'];
    protected const array SCHEMES   = ['acn'];

    private const array WEIGHTS = [8, 7, 6, 5, 4, 3, 2, 1];

    public function verify(string $compact): bool
    {
        if (strlen($compact) !== 9 || ! ctype_digit($compact)) {
            return false;
        }

        $sum = Weights::sum(substr($compact, 0, 8), self::WEIGHTS);

        return (10 - $sum % 10) % 10 === (int) $compact[8];
    }
}
