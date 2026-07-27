<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Core\Checksum\Luhn;

/**
 * Canada — Business Number, nine digits, Luhn.
 *
 * A CRA program account extends it with a two-letter program identifier and a
 * four-digit reference (`123456789RT0001`); only the nine-digit root is
 * checksummed, so the suffix is stripped first.
 */
final class CaBnValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['CA'];
    protected const array SCHEMES   = ['bn'];

    public function verify(string $compact): bool
    {
        // Strip a program account suffix: two letters plus four digits.
        if (strlen($compact) === 15) {
            $compact = substr($compact, 0, 9);
        }

        return strlen($compact) === 9 && ctype_digit($compact) && Luhn::isValid($compact);
    }
}
