<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Core\Checksum\Weights;

/**
 * Argentina — CUIT / CUIL, eleven digits: a two-digit type prefix, the eight
 * digits of the person's DNI (or a sequence for companies) and a check digit.
 *
 * Weighted mod-11 with the key `5432765432`. A computed digit of 10 is
 * unusable, so AFIP shifts the type prefix instead of issuing it — meaning a
 * value that demands 10 was never issued and is invalid.
 */
final class ArCuitValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['AR'];
    protected const array SCHEMES   = ['cuit'];

    private const array WEIGHTS = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];

    public function verify(string $compact): bool
    {
        if (strlen($compact) !== 11 || ! ctype_digit($compact)) {
            return false;
        }

        $sum   = Weights::sum(substr($compact, 0, 10), self::WEIGHTS);
        $check = Weights::mod11ComplementDigit($sum);

        return $check !== null && $check === (int) $compact[10];
    }
}
