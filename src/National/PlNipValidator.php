<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Core\Checksum\Weights;

/**
 * Poland — NIP (Numer Identyfikacji Podatkowej), ten digits; the same digits
 * form the VAT number behind `PL`.
 *
 * Weighted mod-11 with the weights 6,5,7,2,3,4,5,6,7. The remainder *is* the
 * check digit, so a remainder of 10 is unusable and those numbers are never
 * issued.
 */
final class PlNipValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['PL'];
    protected const array SCHEMES   = ['nip'];

    private const array WEIGHTS = [6, 5, 7, 2, 3, 4, 5, 6, 7];

    public function verify(string $compact): bool
    {
        if (strlen($compact) !== 10 || ! ctype_digit($compact)) {
            return false;
        }

        $remainder = Weights::sum(substr($compact, 0, 9), self::WEIGHTS) % 11;

        return $remainder !== 10 && $remainder === (int) $compact[9];
    }
}
