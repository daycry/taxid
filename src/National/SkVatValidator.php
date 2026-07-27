<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

/**
 * Slovakia — IČ DPH (VAT number), ten digits.
 *
 * Unusually, there is no check *digit*: the number as a whole must be divisible
 * by 11. Two extra constraints narrow it further — the first digit is never
 * zero, and the third is one of 2, 3, 4, 7, 8 or 9 (it encodes the taxpayer
 * category).
 */
final class SkVatValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['SK'];
    protected const array SCHEMES   = ['vat'];

    private const string CATEGORY_DIGITS = '234789';

    public function verify(string $compact): bool
    {
        if (strlen($compact) !== 10 || ! ctype_digit($compact)) {
            return false;
        }

        if ($compact[0] === '0' || ! str_contains(self::CATEGORY_DIGITS, $compact[2])) {
            return false;
        }

        return ((int) $compact) % 11 === 0;
    }
}
