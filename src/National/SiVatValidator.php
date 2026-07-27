<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Core\Checksum\Weights;

/**
 * Slovenia — davčna številka (tax number), eight digits, also the VAT number
 * behind `SI`.
 *
 * Weighted mod-11 with the weights 8…2. A computed check of 10 is written as
 * `0`; a computed check of 11 cannot occur in a number that was actually
 * issued, so it is treated as invalid.
 */
final class SiVatValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['SI'];
    protected const array SCHEMES   = ['vat'];

    private const array WEIGHTS = [8, 7, 6, 5, 4, 3, 2];

    public function verify(string $compact): bool
    {
        if (strlen($compact) !== 8 || ! ctype_digit($compact)) {
            return false;
        }

        $check = 11 - Weights::sum(substr($compact, 0, 7), self::WEIGHTS) % 11;

        if ($check === 11) {
            return false;
        }

        if ($check === 10) {
            $check = 0;
        }

        return $check === (int) $compact[7];
    }
}
