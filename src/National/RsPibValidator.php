<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Core\Checksum\Iso7064;

/**
 * Serbia — PIB (poreski identifikacioni broj), nine digits.
 *
 * ISO/IEC 7064 MOD 11,10 over the first eight digits, the same standard Croatia
 * chose for the OIB.
 */
final class RsPibValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['RS'];
    protected const array SCHEMES   = ['pib'];

    public function verify(string $compact): bool
    {
        return strlen($compact) === 9 && Iso7064::isValidMod1110($compact);
    }
}
