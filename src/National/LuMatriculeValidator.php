<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Core\Checksum\Luhn;
use Daycry\TaxId\Core\Checksum\Verhoeff;

/**
 * Luxembourg — matricule, the thirteen-digit numéro d'identification national,
 * which is also an individual's TIN. Thirteen digits since 1 July 2014; the
 * older eleven-digit form is not this scheme.
 *
 * Structure is the birth date, a three-digit serial, then two check digits —
 * and both are computed over the **same** first eleven digits, not one over
 * eleven and the next over twelve. The twelfth is Luhn, the thirteenth is
 * Verhoeff. Two families rather than two of the same: Luhn is blind to an
 * adjacent transposition whose digits differ by five, and Verhoeff is not.
 *
 * The date is not validated. The two check digits already protect the value
 * well, and whether matricules are ever issued with a zero month or day is
 * unconfirmed — the same reasoning {@see LvPersonalCodeValidator} records for
 * Latvia's undated modern codes.
 */
final class LuMatriculeValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['LU'];
    protected const array SCHEMES   = ['matricule'];

    public function verify(string $compact): bool
    {
        if (strlen($compact) !== 13 || ! ctype_digit($compact)) {
            return false;
        }

        $base = substr($compact, 0, 11);

        return Luhn::isValid($base . $compact[11])
            && Verhoeff::checkDigit($base) === (int) $compact[12];
    }
}
