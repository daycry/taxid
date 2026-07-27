<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Core\Checksum\Weights;

/**
 * Paraguay — RUC, a body of up to eight digits plus a check digit.
 *
 * "Base 11": cycling weights that simply count 2, 3, 4… from the right, mod-11,
 * with 11 folded to `0` and 10 to `1`.
 */
final class PyRucValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['PY'];
    protected const array SCHEMES   = ['ruc'];

    private const array WEIGHTS = [2, 3, 4, 5, 6, 7, 8, 9];

    public function verify(string $compact): bool
    {
        $length = strlen($compact);

        if ($length < 5 || $length > 9 || ! ctype_digit($compact)) {
            return false;
        }

        $check = Weights::mod11ComplementDigit(
            Weights::cyclicSum(substr($compact, 0, -1), self::WEIGHTS),
            tenAs: 1,
            elevenAs: 0,
        );

        return $check === (int) $compact[$length - 1];
    }
}
