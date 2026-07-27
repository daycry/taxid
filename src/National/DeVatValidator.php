<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Core\Checksum\Iso7064;

/**
 * Germany — Umsatzsteuer-Identifikationsnummer (USt-IdNr), nine digits behind
 * the `DE` prefix.
 *
 * The Bundeszentralamt für Steuern describes it as a product/sum recurrence,
 * which is precisely ISO/IEC 7064 MOD 11,10: carry starts at 10, each digit
 * folds into `sum = (d + carry) mod 10` (with 0 read as 10) and
 * `carry = 2 * sum mod 11`; the check digit closes it at `(11 - carry) mod 10`.
 *
 * Note this is a *different* number from the Steuernummer a business quotes to
 * its local tax office, and from the personal Identifikationsnummer — Germany
 * issues all three, and the registry keeps them as separate schemes.
 */
final class DeVatValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['DE'];
    protected const array SCHEMES   = ['vat'];

    public function verify(string $compact): bool
    {
        if (strlen($compact) !== 9 || ! ctype_digit($compact)) {
            return false;
        }

        // A leading zero would make it an eight-digit number wearing a disguise.
        if ($compact[0] === '0') {
            return false;
        }

        return Iso7064::isValidMod1110($compact);
    }
}
