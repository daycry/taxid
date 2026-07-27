<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Core\Checksum\Weights;

/**
 * Finland — Y-tunnus (business ID), seven digits plus a check digit; the same
 * eight digits form the VAT number behind `FI`.
 *
 * Weighted mod-11 with the weights 7,9,10,5,8,4,2. A remainder of exactly 1 is
 * unusable — it would call for a check digit of 10 — so those numbers are never
 * issued and are rejected here.
 */
final class FiYtunnusValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['FI'];
    protected const array SCHEMES   = ['ytunnus', 'vat'];

    private const array WEIGHTS = [7, 9, 10, 5, 8, 4, 2];

    public function verify(string $compact): bool
    {
        if (strlen($compact) !== 8 || ! ctype_digit($compact)) {
            return false;
        }

        $remainder = Weights::sum(substr($compact, 0, 7), self::WEIGHTS) % 11;

        if ($remainder === 1) {
            return false;
        }

        $check = $remainder === 0 ? 0 : 11 - $remainder;

        return $check === (int) $compact[7];
    }
}
