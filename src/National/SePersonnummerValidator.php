<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Core\Checksum\Luhn;

/**
 * Sweden — personnummer, the personal identity number.
 *
 * Written as ten digits (`YYMMDDNNNC`) or twelve with the century in front.
 * Only the ten-digit core takes part in the Luhn check, so the century prefix
 * is dropped first.
 *
 * The `-` / `+` separator (the latter marking someone over 100) is stripped
 * during normalization: it duplicates information the twelve-digit form already
 * carries, and nothing in the checksum depends on it.
 */
final class SePersonnummerValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['SE'];
    protected const array SCHEMES   = ['personnummer'];

    public function verify(string $compact): bool
    {
        if (! ctype_digit($compact)) {
            return false;
        }

        $core = match (strlen($compact)) {
            10      => $compact,
            12      => substr($compact, 2),
            default => null,
        };

        return $core !== null && Luhn::isValid($core);
    }
}
