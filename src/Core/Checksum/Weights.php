<?php

declare(strict_types=1);

namespace Daycry\TaxId\Core\Checksum;

/**
 * Weighted-sum primitives — the single most reused building block across the
 * bundled national validators.
 *
 * The overwhelming majority of tax-ID check digits are "multiply each digit by
 * a positional weight, add it all up, take a modulus". Two weight layouts cover
 * nearly every jurisdiction:
 *
 * - a **fixed** weight per position ({@see sum()}), used by most of the EU and
 *   by Brazil, Colombia, Peru…;
 * - a **cycling** weight sequence ({@see cyclicSum()}), used where the value's
 *   length varies (Chile's RUT, Russia's 12-digit INN…).
 *
 * All methods are pure and total: a non-digit character contributes `0` rather
 * than raising, because callers always hand over a value that already matched
 * its scheme's pattern.
 */
final class Weights
{
    /**
     * Sum of `digit[i] * weights[i]`, left to right.
     *
     * Iterates over the shorter of the two, so a caller may pass a longer weight
     * table than the payload (handy when one table serves several lengths).
     *
     * @param string $digits  Digit characters.
     * @param int[]  $weights Positional weights, index-aligned with `$digits`.
     */
    public static function sum(string $digits, array $weights): int
    {
        $weights = array_values($weights);
        $length  = min(strlen($digits), count($weights));
        $total   = 0;

        for ($i = 0; $i < $length; $i++) {
            $total += ((int) $digits[$i]) * $weights[$i];
        }

        return $total;
    }

    /**
     * Sum with a repeating weight sequence.
     *
     * @param string $digits       Digit characters.
     * @param int[]  $weights      The sequence to cycle through.
     * @param bool   $rightToLeft  When true (the default) the cycle starts at the
     *                             LAST digit and walks backwards — the usual
     *                             convention, since it keeps the weighting stable
     *                             for values of differing length.
     */
    public static function cyclicSum(string $digits, array $weights, bool $rightToLeft = true): int
    {
        $weights = array_values($weights);
        $count   = count($weights);

        if ($count === 0) {
            return 0;
        }

        $length = strlen($digits);
        $total  = 0;

        for ($i = 0; $i < $length; $i++) {
            $digit  = (int) $digits[$rightToLeft ? $length - 1 - $i : $i];
            $total += $digit * $weights[$i % $count];
        }

        return $total;
    }

    /**
     * The "`11 - (sum % 11)`, with 10 and 11 folded away" rule shared by every
     * Latin-American mod-11 scheme and by several European ones.
     *
     * @param int      $sum      A weighted sum, typically from {@see sum()}.
     * @param int|null $tenAs    Value to return when the raw result is 10;
     *                           `null` means "10 is not a legal check digit".
     * @param int|null $elevenAs Value to return when the raw result is 11
     *                           (i.e. `sum % 11 === 0`); defaults to `0`.
     *
     * @return int|null The check digit, or `null` when the sum yields an
     *                  impossible digit and the caller declared no folding for it.
     */
    public static function mod11ComplementDigit(int $sum, ?int $tenAs = null, ?int $elevenAs = 0): ?int
    {
        $result = 11 - ($sum % 11);

        if ($result === 10) {
            return $tenAs;
        }

        if ($result === 11) {
            return $elevenAs;
        }

        return $result;
    }

    /**
     * The other common mod-11 convention: `sum % 11`, where a remainder below 2
     * means a check digit of `0`.
     *
     * Used by Brazil (CPF/CNPJ) and Colombia (NIT), among others.
     */
    public static function mod11RemainderDigit(int $sum): int
    {
        $remainder = $sum % 11;

        return $remainder < 2 ? 0 : 11 - $remainder;
    }
}
