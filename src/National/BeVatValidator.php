<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

/**
 * Belgium — BTW/TVA/USt number, ten digits.
 *
 * The last two digits are `97 - (first eight mod 97)`.
 *
 * Belgium widened the number from nine to ten digits in 2008 by prefixing a
 * `0`; nine-digit values are still quoted occasionally, so the registry pattern
 * accepts them and this validator left-pads before checking, which is exactly
 * what the widening did to existing numbers.
 */
final class BeVatValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['BE'];
    protected const array SCHEMES   = ['vat'];

    public function verify(string $compact): bool
    {
        if (! ctype_digit($compact)) {
            return false;
        }

        if (strlen($compact) === 9) {
            $compact = '0' . $compact;
        }

        if (strlen($compact) !== 10) {
            return false;
        }

        $base  = (int) substr($compact, 0, 8);
        $check = (int) substr($compact, 8, 2);

        return $check === 97 - $base % 97;
    }
}
