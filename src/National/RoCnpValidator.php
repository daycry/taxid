<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Core\Checksum\Weights;

/**
 * Romania — CNP (cod numeric personal), thirteen digits.
 *
 * Weighted mod-11 against the key `279146358279`; a remainder of 10 is written
 * as a check digit of `1`.
 *
 * The leading digit encodes sex and century (1/2 for the 1900s, 3/4 for the
 * 1800s, 5/6 for the 2000s, 7/8/9 for residents and foreigners) and is
 * constrained by the registry pattern rather than here.
 */
final class RoCnpValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['RO'];
    protected const array SCHEMES   = ['cnp'];

    private const array WEIGHTS = [2, 7, 9, 1, 4, 6, 3, 5, 8, 2, 7, 9];

    public function verify(string $compact): bool
    {
        if (strlen($compact) !== 13 || ! ctype_digit($compact)) {
            return false;
        }

        $remainder = Weights::sum(substr($compact, 0, 12), self::WEIGHTS) % 11;

        return ($remainder === 10 ? 1 : $remainder) === (int) $compact[12];
    }
}
