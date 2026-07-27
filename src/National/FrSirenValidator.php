<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Core\Checksum\Luhn;

/**
 * France — SIREN (the nine-digit legal-entity number) and SIRET (SIREN plus a
 * five-digit establishment code, fourteen digits in all).
 *
 * Both are plain Luhn over the whole value. Two documented exceptions exist and
 * are handled explicitly:
 *
 * - **La Poste**, SIREN `356000000`, whose SIRETs are not Luhn-valid; instead
 *   the sum of their fourteen digits must be a multiple of 5.
 * - **Sole traders and public bodies** registered before the Luhn rule was
 *   applied still validate normally, so no exception is needed there.
 */
final class FrSirenValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['FR', 'MC'];
    protected const array SCHEMES   = ['siren', 'siret'];

    private const string LA_POSTE_SIREN = '356000000';

    public function verify(string $compact): bool
    {
        if (! ctype_digit($compact)) {
            return false;
        }

        $length = strlen($compact);

        if ($length !== 9 && $length !== 14) {
            return false;
        }

        if ($length === 14 && str_starts_with($compact, self::LA_POSTE_SIREN)) {
            return array_sum(array_map(intval(...), str_split($compact))) % 5 === 0;
        }

        return Luhn::isValid($compact);
    }
}
