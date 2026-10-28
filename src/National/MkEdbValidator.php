<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Contracts\ReservedValueDetectorInterface;
use Daycry\TaxId\Core\Checksum\Weights;

/**
 * North Macedonia — EDB tax registration number, thirteen digits.
 * Weighted mod-11 control corroborated against public government examples;
 * provenance and limitations are recorded in docs/algorithms.md.
 * The all-zero placeholder is explicitly rejected as a library policy.
 */
final class MkEdbValidator extends AbstractNationalValidator implements ReservedValueDetectorInterface
{
    protected const array COUNTRIES = ['MK'];
    protected const array SCHEMES = ['edb'];

    public function verify(string $compact): bool
    {
        if (strlen($compact) !== 13 || ! ctype_digit($compact) || $this->isReserved($compact)) {
            return false;
        }

        $sum = Weights::sum(substr($compact, 0, 12), [7, 6, 5, 4, 3, 2, 7, 6, 5, 4, 3, 2]);

        return Weights::mod11ComplementDigit($sum, tenAs: 0) === (int) $compact[12];
    }

    public function isReserved(string $compact): bool
    {
        return $compact === '0000000000000';
    }
}
