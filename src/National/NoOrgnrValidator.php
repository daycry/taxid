<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Core\Checksum\Weights;

/**
 * Norway — organisasjonsnummer, nine digits; the same digits followed by `MVA`
 * form the VAT number.
 *
 * Weighted mod-11 with the key `32765432`. A remainder of 1 would demand a
 * check digit of 10, so the Brønnøysund register simply skips those candidates.
 */
final class NoOrgnrValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['NO'];
    protected const array SCHEMES   = ['orgnr', 'mva'];

    private const array WEIGHTS = [3, 2, 7, 6, 5, 4, 3, 2];

    public function verify(string $compact): bool
    {
        // The VAT form is the organisation number with an MVA suffix.
        if (str_ends_with($compact, 'MVA')) {
            $compact = substr($compact, 0, -3);
        }

        if (strlen($compact) !== 9 || ! ctype_digit($compact)) {
            return false;
        }

        $sum   = Weights::sum(substr($compact, 0, 8), self::WEIGHTS);
        $check = Weights::mod11ComplementDigit($sum);

        return $check !== null && $check === (int) $compact[8];
    }
}
