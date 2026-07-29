<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Core\Checksum\Weights;

/**
 * Norway — fødselsnummer, the eleven-digit national identity number,
 * `DDMMYYNNNKK`.
 *
 * Two mod-11 check digits, each the complement of a weighted sum: the first
 * over the nine digits before it, the second over all ten. A digit that
 * computes to 10 cannot be written, so that serial is simply never issued.
 *
 * Two variants offset a field by 40 and only one of them belongs here:
 *
 * - a **D-number**, issued to non-residents, adds 40 to the day. It is a real
 *   tax identifier and shares this checksum, so it is accepted.
 * - an **H-number** adds 40 to the month instead. It exists only inside the
 *   health service, and its checksum is perfectly valid — which is exactly why
 *   the month has to be range-checked rather than trusted to arithmetic.
 */
final class NoFodselsnummerValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['NO'];
    protected const array SCHEMES   = ['fodselsnummer'];

    private const array WEIGHTS_FIRST  = [3, 7, 6, 1, 8, 9, 4, 5, 2];
    private const array WEIGHTS_SECOND = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];

    public function verify(string $compact): bool
    {
        if (strlen($compact) !== 11 || ! ctype_digit($compact)) {
            return false;
        }

        $day   = (int) substr($compact, 0, 2);
        $month = (int) substr($compact, 2, 2);

        if ($day > 40) {
            $day -= 40;   // a D-number, not an error
        }

        if ($day < 1 || $day > 31 || $month < 1 || $month > 12) {
            return false;
        }

        $first = Weights::mod11ComplementDigit(Weights::sum(substr($compact, 0, 9), self::WEIGHTS_FIRST));

        if ($first === null || $first !== (int) $compact[9]) {
            return false;
        }

        $second = Weights::mod11ComplementDigit(Weights::sum(substr($compact, 0, 10), self::WEIGHTS_SECOND));

        return $second !== null && $second === (int) $compact[10];
    }
}
