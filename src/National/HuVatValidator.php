<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Core\Checksum\Weights;

/**
 * Hungary — közösségi adószám (the eight-digit EU VAT number) and the full
 * eleven-digit adószám it is drawn from.
 *
 * Only the first eight digits are checksummed: weights 9,7,3,1,9,7,3 and a
 * check digit of `(10 - sum mod 10) mod 10`. The ninth digit of the long form
 * marks the VAT regime and the last two the county — both are data, not
 * verification, so they are matched by the pattern and not by this validator.
 */
final class HuVatValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['HU'];
    protected const array SCHEMES   = ['anum', 'adoszam'];

    private const array WEIGHTS = [9, 7, 3, 1, 9, 7, 3];

    public function verify(string $compact): bool
    {
        $length = strlen($compact);

        if (($length !== 8 && $length !== 11) || ! ctype_digit($compact)) {
            return false;
        }

        $sum = Weights::sum(substr($compact, 0, 7), self::WEIGHTS);

        return (10 - $sum % 10) % 10 === (int) $compact[7];
    }
}
