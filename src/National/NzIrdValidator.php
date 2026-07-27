<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Core\Checksum\Weights;

/**
 * New Zealand — IRD number, eight or nine digits.
 *
 * The base is left-padded to eight digits and weighted `3 2 7 6 5 4 3 2`,
 * mod-11. If that demands the unwritable check digit of 10, a **second** weight
 * set — `7 4 3 2 5 2 7 6` — is tried; only if that also lands on 10 is the
 * number rejected.
 *
 * Inland Revenue also constrains the base to the range 10 000 000 – 150 000 000,
 * which is checked here: it is the cheapest way to reject a number that is
 * arithmetically fine but outside the issued space.
 */
final class NzIrdValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['NZ'];
    protected const array SCHEMES   = ['ird'];

    private const array WEIGHTS_PRIMARY   = [3, 2, 7, 6, 5, 4, 3, 2];
    private const array WEIGHTS_SECONDARY = [7, 4, 3, 2, 5, 2, 7, 6];

    public function verify(string $compact): bool
    {
        $length = strlen($compact);

        if (($length !== 8 && $length !== 9) || ! ctype_digit($compact)) {
            return false;
        }

        $base  = str_pad(substr($compact, 0, -1), 8, '0', STR_PAD_LEFT);
        $check = (int) $compact[$length - 1];

        $value = (int) $base;

        if ($value < 10000000 || $value > 150000000) {
            return false;
        }

        $candidate = $this->checkDigit($base, self::WEIGHTS_PRIMARY);

        if ($candidate === null) {
            $candidate = $this->checkDigit($base, self::WEIGHTS_SECONDARY);
        }

        return $candidate !== null && $candidate === $check;
    }

    /**
     * @param int[] $weights
     *
     * @return int|null `null` when the weighting demands a check digit of 10.
     */
    private function checkDigit(string $base, array $weights): ?int
    {
        $remainder = Weights::sum($base, $weights) % 11;

        if ($remainder === 0) {
            return 0;
        }

        $check = 11 - $remainder;

        return $check === 10 ? null : $check;
    }
}
