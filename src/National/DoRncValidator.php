<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Core\Checksum\Weights;

/**
 * Dominican Republic — RNC (Registro Nacional del Contribuyente), nine digits.
 *
 * Weighted mod-11 with the key `79865432`, and two special cases at the low
 * end: a remainder of 0 gives a check digit of `2` and a remainder of 1 gives
 * `1`, rather than the usual complement.
 *
 * Individuals use their eleven-digit cédula instead, which the registry keeps
 * as a separate scheme.
 */
final class DoRncValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['DO'];
    protected const array SCHEMES   = ['rnc'];

    private const array WEIGHTS = [7, 9, 8, 6, 5, 4, 3, 2];

    public function verify(string $compact): bool
    {
        if (strlen($compact) !== 9 || ! ctype_digit($compact)) {
            return false;
        }

        $remainder = Weights::sum(substr($compact, 0, 8), self::WEIGHTS) % 11;

        $check = match ($remainder) {
            0       => 2,
            1       => 1,
            default => 11 - $remainder,
        };

        return $check === (int) $compact[8];
    }
}
