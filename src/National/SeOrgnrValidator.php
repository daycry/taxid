<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Core\Checksum\Luhn;

/**
 * Sweden — organisationsnummer, ten digits, Luhn.
 *
 * The first two digits encode the legal form (16 = trading partnership,
 * 20 = state/municipal, 5x = limited company…). That mapping is informational
 * only and not enforced: the list has grown over time.
 */
final class SeOrgnrValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['SE'];
    protected const array SCHEMES   = ['orgnr'];

    public function verify(string $compact): bool
    {
        return strlen($compact) === 10 && ctype_digit($compact) && Luhn::isValid($compact);
    }
}
