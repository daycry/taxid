<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

/**
 * Australia — ABN (Australian Business Number), eleven digits.
 *
 * Subtract 1 from the leading digit, apply the weights
 * `10 1 3 5 7 9 11 13 15 17 19`, and the total must be divisible by 89. The
 * "subtract 1" step is what stops an ABN from ever starting with `0`, and the
 * odd weights make it very good at catching transpositions.
 */
final class AuAbnValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['AU'];
    protected const array SCHEMES   = ['abn'];

    private const array WEIGHTS = [10, 1, 3, 5, 7, 9, 11, 13, 15, 17, 19];

    public function verify(string $compact): bool
    {
        if (strlen($compact) !== 11 || ! ctype_digit($compact)) {
            return false;
        }

        $sum = 0;

        foreach (self::WEIGHTS as $index => $weight) {
            $digit = (int) $compact[$index];

            if ($index === 0) {
                $digit--;
            }

            $sum += $digit * $weight;
        }

        return $sum > 0 && $sum % 89 === 0;
    }
}
