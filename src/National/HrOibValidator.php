<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Core\Checksum\Iso7064;

/**
 * Croatia — OIB (osobni identifikacijski broj), eleven digits, used by both
 * individuals and companies and doubling as the VAT number behind `HR`.
 *
 * Straight ISO/IEC 7064 MOD 11,10 over the first ten digits. Croatia adopted
 * the OIB in 2009 precisely to have one number with a standard checksum, in
 * place of the several older identifiers it replaced.
 */
final class HrOibValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['HR'];
    protected const array SCHEMES   = ['oib'];

    public function verify(string $compact): bool
    {
        return strlen($compact) === 11 && Iso7064::isValidMod1110($compact);
    }
}
