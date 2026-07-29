<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Core\Checksum\Weights;

/**
 * Hungary — adóazonosító jel, the ten-digit personal tax identifier NAV issues
 * to individuals. Not to be confused with the corporate adószám, which is
 * eleven digits and lives in this registry under its own scheme.
 *
 * Each of the first nine digits is multiplied by its own position — one, two,
 * three and so on — and the sum modulo eleven is the tenth digit. The value
 * always begins with 8, the code for a natural person.
 *
 * A remainder of ten is not folded to zero: it cannot be written in one digit,
 * so the office skips that serial entirely. Folding it here would accept
 * identifiers that were never issued, so it is rejected instead.
 *
 * Digits two to six encode the birth date as the number of days elapsed since
 * 1 January 1867. That is not decoded — nothing in the checksum depends on it,
 * and a date this validator cannot see is a date it cannot get wrong.
 */
final class HuAdoazonositoValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['HU'];
    protected const array SCHEMES   = ['adoazonosito'];

    private const array WEIGHTS = [1, 2, 3, 4, 5, 6, 7, 8, 9];

    public function verify(string $compact): bool
    {
        if (strlen($compact) !== 10 || ! ctype_digit($compact) || $compact[0] !== '8') {
            return false;
        }

        $remainder = Weights::sum(substr($compact, 0, 9), self::WEIGHTS) % 11;

        return $remainder !== 10 && $remainder === (int) $compact[9];
    }
}
