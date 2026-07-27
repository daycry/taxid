<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Core\Checksum\Luhn;

/**
 * Israel — nine-digit identifier, shared by the personal ID (מספר זהות), the
 * company number (ח.פ.) and the VAT-registered dealer number (עוסק מורשה).
 *
 * All three are Luhn over the nine digits. Because they share both format and
 * checksum, the leading digit is the only hint of which is which — `5` marks a
 * company, `9` a non-resident — and the registry keeps them as separate schemes
 * matched by that first digit.
 */
final class IlIdValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['IL'];
    protected const array SCHEMES   = ['id', 'hp'];

    public function verify(string $compact): bool
    {
        return strlen($compact) === 9 && ctype_digit($compact) && Luhn::isValid($compact);
    }
}
