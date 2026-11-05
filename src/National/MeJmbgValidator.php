<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Core\Checksum\Weights;

/**
 * Montenegro — personal JMBG, separate from the eight-digit PIB.
 * Checks calendar dates and domestic registration regions 21–29.
 * Weights: 765432765432; remainder zero gives control zero, one is unissuable.
 * https://wapi.gov.me/download/0877efd1-4ca1-44d9-9794-fde9d91812ae?version=1.0
 * Remainder-zero convention: https://regos.hr/app/uploads/2018/07/algoritmiobrazacr-s.pdf
 */
final class MeJmbgValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['ME'];
    protected const array SCHEMES = ['jmbg'];

    public function verify(string $compact): bool
    {
        if (preg_match('/^[0-9]{7}2[1-9][0-9]{4}\z/', $compact) !== 1) {
            return false;
        }

        $day = (int) substr($compact, 0, 2);
        $month = (int) substr($compact, 2, 2);
        $year = (int) substr($compact, 4, 3);
        $year += $year >= 800 ? 1000 : 2000;

        if (! checkdate($month, $day, $year)) {
            return false;
        }

        $sum = Weights::sum(substr($compact, 0, 12), [7, 6, 5, 4, 3, 2, 7, 6, 5, 4, 3, 2]);

        return Weights::mod11ComplementDigit($sum) === (int) $compact[12];
    }
}
