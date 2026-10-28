<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Core\Checksum\Weights;

/**
 * Japan — My Number, eleven payload digits and a mod-11 control digit.
 * Article 5: https://laws.e-gov.go.jp/law/426M60000008085
 */
final class JpMyNumberValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['JP'];
    protected const array SCHEMES = ['mynumber'];

    public function verify(string $compact): bool
    {
        if (strlen($compact) !== 12 || ! ctype_digit($compact)) {
            return false;
        }

        $sum = Weights::sum(substr($compact, 0, 11), [6, 5, 4, 3, 2, 7, 6, 5, 4, 3, 2]);
        $remainder = $sum % 11;

        return ($remainder <= 1 ? 0 : 11 - $remainder) === (int) $compact[11];
    }
}
