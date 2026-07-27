<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

/**
 * Slovakia — IČO, the eight-digit organisation number.
 *
 * Identical to the Czech IČO: both countries kept the Czechoslovak numbering
 * standard after 1993, so the algorithm is shared rather than duplicated.
 */
final class SkIcoValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['SK'];
    protected const array SCHEMES   = ['ico'];

    public function verify(string $compact): bool
    {
        return CzDicValidator::isValidIco($compact);
    }
}
