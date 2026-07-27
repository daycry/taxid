<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Core\Checksum\Luhn;

/**
 * Italy — partita IVA, eleven digits.
 *
 * The Agenzia delle Entrate describes it as "odd positions summed, even
 * positions doubled and reduced by nine when they exceed nine", which for an
 * eleven-digit value is the Luhn algorithm exactly.
 *
 * The first seven digits identify the taxpayer, the next three the issuing
 * province (`001`–`100`, plus `120`, `121`, `888`, `999`); that range is *not*
 * enforced, since the province list has been extended over time.
 */
final class ItPartitaIvaValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['IT'];
    protected const array SCHEMES   = ['piva'];

    public function verify(string $compact): bool
    {
        if (strlen($compact) !== 11 || ! ctype_digit($compact)) {
            return false;
        }

        return Luhn::isValid($compact);
    }
}
