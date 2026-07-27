<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Core\Checksum\Weights;

/**
 * Denmark — CVR-nummer, eight digits, also used as the VAT number behind `DK`.
 *
 * Weighted mod-11 with the weights 2,7,6,5,4,3,2. A remainder of 1 would demand
 * a check digit of 10, which cannot be written in one position, so those
 * candidate numbers are simply never issued — and a value claiming one is
 * invalid.
 */
final class DkCvrValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['DK'];
    protected const array SCHEMES   = ['cvr'];

    private const array WEIGHTS = [2, 7, 6, 5, 4, 3, 2];

    public function verify(string $compact): bool
    {
        if (strlen($compact) !== 8 || ! ctype_digit($compact)) {
            return false;
        }

        if ($compact[0] === '0') {
            return false;
        }

        $sum   = Weights::sum(substr($compact, 0, 7), self::WEIGHTS);
        $check = Weights::mod11ComplementDigit($sum);

        return $check !== null && $check === (int) $compact[7];
    }
}
