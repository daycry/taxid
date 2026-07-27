<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Core\Checksum\Weights;

/**
 * Peru — RUC (Registro Único de Contribuyentes), eleven digits.
 *
 * Weighted mod-11 with the key `5432765432`. The two overflow cases are folded
 * rather than skipped: a computed 10 is written as `0` and an 11 as `1`, so
 * every combination of the first ten digits is usable.
 *
 * The leading pair marks the taxpayer type (`10`/`15`/`17` individuals,
 * `20` companies…) and is left to the registry pattern.
 */
final class PeRucValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['PE'];
    protected const array SCHEMES   = ['ruc'];

    private const array WEIGHTS = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];

    public function verify(string $compact): bool
    {
        if (strlen($compact) !== 11 || ! ctype_digit($compact)) {
            return false;
        }

        $check = Weights::mod11ComplementDigit(
            Weights::sum(substr($compact, 0, 10), self::WEIGHTS),
            tenAs: 0,
            elevenAs: 1,
        );

        return $check === (int) $compact[10];
    }
}
