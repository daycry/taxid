<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

/**
 * Lithuania — PVM mokėtojo kodas (VAT), nine digits for a legal entity or
 * twelve for a natural person.
 *
 * Both mark themselves: the second-to-last digit is always `1`. The check digit
 * is a weighted mod-11 whose weights simply count upwards from 1, wrapping
 * after 9; if it lands on the unwritable 10, the whole sum is recomputed with
 * every weight shifted by two, and a second 10 folds to `0`.
 */
final class LtPvmValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['LT'];
    protected const array SCHEMES   = ['pvm'];

    public function verify(string $compact): bool
    {
        $length = strlen($compact);

        if (($length !== 9 && $length !== 12) || ! ctype_digit($compact)) {
            return false;
        }

        // The "temporarily registered / natural person" marker.
        if ($compact[$length - 2] !== '1') {
            return false;
        }

        $body  = substr($compact, 0, $length - 1);
        $check = $this->weightedRemainder($body, 1);

        if ($check === 10) {
            $check = $this->weightedRemainder($body, 3);
            $check = $check === 10 ? 0 : $check;
        }

        return $check === (int) $compact[$length - 1];
    }

    /**
     * Weights run `$start`, `$start + 1`, … wrapping back to 1 after 9.
     */
    private function weightedRemainder(string $body, int $start): int
    {
        $sum    = 0;
        $length = strlen($body);

        for ($i = 0; $i < $length; $i++) {
            $weight = ($start + $i - 1) % 9 + 1;
            $sum += ((int) $body[$i]) * $weight;
        }

        return $sum % 11;
    }
}
