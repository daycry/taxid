<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

/**
 * Belgium — rijksregisternummer / numéro de registre national, eleven digits:
 * `YYMMDD` + a three-digit sequence + a two-digit check.
 *
 * The check is `97 - (first nine mod 97)`. Because the year is only two digits,
 * people born from 2000 onwards would collide with the 1900s; the fix is to
 * prefix a literal `2` to the nine digits before taking the modulus. Both
 * readings are tried, which is what every implementation must do — there is no
 * other marker of the century in the number.
 */
final class BeNationalNumberValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['BE'];
    protected const array SCHEMES   = ['nn'];

    public function verify(string $compact): bool
    {
        if (strlen($compact) !== 11 || ! ctype_digit($compact)) {
            return false;
        }

        $base  = substr($compact, 0, 9);
        $check = (int) substr($compact, 9, 2);

        // Born before 2000, then born from 2000 on (century marker prefixed).
        return $check === 97 - ((int) $base) % 97
            || $check === 97 - ((int) ('2' . $base)) % 97;
    }
}
