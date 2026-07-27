<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Core\Checksum\Luhn;

/**
 * France — numéro de TVA intracommunautaire: a two-character key followed by
 * the nine-digit SIREN.
 *
 * When the key is numeric it is derived from the SIREN and fully checkable:
 * `key = (12 + 3 * (SIREN mod 97)) mod 97`.
 *
 * Keys containing letters exist too (they were introduced when the numeric
 * space grew tight) and the DGFiP does not publish how those are computed. For
 * those the key is accepted as-is and only the SIREN's Luhn check is enforced —
 * a deliberate, documented limitation rather than an oversight: inventing a
 * rule would reject valid numbers.
 */
final class FrTvaValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['FR', 'MC'];
    protected const array SCHEMES   = ['tva'];

    public function verify(string $compact): bool
    {
        if (strlen($compact) !== 11) {
            return false;
        }

        $key   = substr($compact, 0, 2);
        $siren = substr($compact, 2);

        if (! ctype_digit($siren) || ! Luhn::isValid($siren)) {
            return false;
        }

        if (! ctype_digit($key)) {
            return true;
        }

        return (int) $key === (12 + 3 * ((int) $siren % 97)) % 97;
    }
}
