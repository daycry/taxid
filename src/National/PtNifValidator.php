<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Core\Checksum\Weights;

/**
 * Portugal — NIF / NIPC, the single nine-digit number used by both individuals
 * and companies (and, prefixed `PT`, as the VAT number).
 *
 * Plain weighted mod-11: weights 9 down to 2 over the first eight digits, check
 * digit `11 - (sum mod 11)`, with anything from 10 upwards folded to `0`.
 *
 * The leading digit encodes the taxpayer's category (`1`/`2`/`3` individuals,
 * `5` companies, `6` public bodies, `9` informal entities…) but the registry
 * pattern only requires it to be non-zero: the ranges have been extended
 * repeatedly, and hard-coding today's list would reject tomorrow's valid NIFs.
 */
final class PtNifValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['PT'];
    protected const array SCHEMES   = ['nif'];

    private const array WEIGHTS = [9, 8, 7, 6, 5, 4, 3, 2];

    public function verify(string $compact): bool
    {
        if (strlen($compact) !== 9 || ! ctype_digit($compact)) {
            return false;
        }

        $sum   = Weights::sum(substr($compact, 0, 8), self::WEIGHTS);
        $check = 11 - $sum % 11;

        if ($check >= 10) {
            $check = 0;
        }

        return $check === (int) $compact[8];
    }
}
