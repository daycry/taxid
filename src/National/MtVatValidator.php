<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Core\Checksum\Weights;

/**
 * Malta — VAT number, eight digits.
 *
 * The last two are `37 - (weighted sum mod 37)` over the first six, weighted
 * 3,4,6,7,8,9. A modulus of 37 is unusual — it lets the two-digit check span
 * 01–37 and makes single-digit errors impossible to hide.
 */
final class MtVatValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['MT'];
    protected const array SCHEMES   = ['vat'];

    private const array WEIGHTS = [3, 4, 6, 7, 8, 9];

    public function verify(string $compact): bool
    {
        if (strlen($compact) !== 8 || ! ctype_digit($compact)) {
            return false;
        }

        $sum = Weights::sum(substr($compact, 0, 6), self::WEIGHTS);

        return 37 - $sum % 37 === (int) substr($compact, 6, 2);
    }
}
