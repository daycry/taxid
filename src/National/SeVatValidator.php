<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Core\Checksum\Luhn;

/**
 * Sweden — momsregistreringsnummer (VAT), twelve digits.
 *
 * Simply the ten-digit organisationsnummer with a two-digit branch suffix,
 * conventionally `01`. Only the first ten digits take part in the Luhn check;
 * the suffix is a sequence number, not a checksum.
 */
final class SeVatValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['SE'];
    protected const array SCHEMES   = ['vat'];

    public function verify(string $compact): bool
    {
        if (strlen($compact) !== 12 || ! ctype_digit($compact)) {
            return false;
        }

        return Luhn::isValid(substr($compact, 0, 10));
    }
}
