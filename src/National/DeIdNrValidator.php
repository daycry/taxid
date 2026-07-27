<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Core\Checksum\Iso7064;

/**
 * Germany — steuerliche Identifikationsnummer (IdNr), the eleven-digit lifelong
 * personal tax number that replaced the old Steuernummer for individuals.
 *
 * The check digit closes an ISO/IEC 7064 MOD 11,10 run over the first ten
 * digits, exactly as the VAT number does.
 *
 * The specification also constrains the first ten digits themselves — within
 * them exactly one digit repeats, and a digit repeated three times may not be
 * consecutive — and that rule is **not** enforced here. It has been reworded
 * across BZSt releases, so implementing yesterday's version risks rejecting
 * numbers that are perfectly valid today; the check digit alone already catches
 * every single-digit typo.
 */
final class DeIdNrValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['DE'];
    protected const array SCHEMES   = ['idnr'];

    public function verify(string $compact): bool
    {
        if (strlen($compact) !== 11 || ! ctype_digit($compact)) {
            return false;
        }

        if ($compact[0] === '0') {
            return false;
        }

        return Iso7064::isValidMod1110($compact);
    }
}
