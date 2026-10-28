<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Core\Checksum\Weights;

/**
 * North Macedonia — personal EMBG, distinct from the tax-registration EDB.
 * Regions 41-49 identify citizens; 04 identifies foreign residents.
 * Structure: https://www.refworld.org/sites/default/files/attachments/5aabd7614.pdf
 * Uses the shared Yugoslav personal-number mod-11 rule, accepting a zero
 * control for either complement 10 or 11, as in the reference implementation.
 */
final class MkEmbgValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['MK'];
    protected const array SCHEMES = ['embg'];

    public function verify(string $compact): bool
    {
        if (strlen($compact) !== 13 || preg_match('/^\d{7}(?:4[1-9]|04)\d{4}$/', $compact) !== 1) {
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

        return Weights::mod11ComplementDigit($sum, tenAs: 0) === (int) $compact[12];
    }
}
