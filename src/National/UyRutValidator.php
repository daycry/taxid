<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Core\Checksum\Weights;

/**
 * Uruguay — RUT (Registro Único Tributario), twelve digits.
 *
 * Weighted mod-11 with the DGI key `43298765432`. A computed check of 10 cannot
 * be written and those numbers are not issued.
 */
final class UyRutValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['UY'];
    protected const array SCHEMES   = ['rut'];

    private const array WEIGHTS = [4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

    public function verify(string $compact): bool
    {
        if (strlen($compact) !== 12 || ! ctype_digit($compact)) {
            return false;
        }

        $check = Weights::mod11ComplementDigit(
            Weights::sum(substr($compact, 0, 11), self::WEIGHTS),
        );

        return $check !== null && $check === (int) $compact[11];
    }
}
