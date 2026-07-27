<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Core\Checksum\Weights;

/**
 * Latvia — PVN reģistrācijas numurs (VAT), eleven digits.
 *
 * Latvia issues two very different numbers under one format:
 *
 * - **Legal entities**, whose first digit is 4 or above: a weighted mod-11 with
 *   the key `9148310257 6`, plus a documented correction that subtracts 45 when
 *   the number starts with `9` and the remainder is exactly 4 — a patch applied
 *   when the `9…` series was opened.
 * - **Natural persons**, whose number is a date-based personal code (the first
 *   six digits are `DDMMYY`): the VAT context defines no additional check, so
 *   those are accepted on shape and validated by
 *   {@see LvPersonalCodeValidator} under their own scheme.
 */
final class LvVatValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['LV'];
    protected const array SCHEMES   = ['vat'];

    private const array WEIGHTS = [9, 1, 4, 8, 3, 10, 2, 5, 7, 6];

    public function verify(string $compact): bool
    {
        if (strlen($compact) !== 11 || ! ctype_digit($compact)) {
            return false;
        }

        // A leading digit below 4 marks a natural person's date-based code.
        if ((int) $compact[0] < 4) {
            return $this->hasPlausibleBirthDate($compact);
        }

        $total = Weights::sum(substr($compact, 0, 10), self::WEIGHTS);

        if ($compact[0] === '9' && $total % 11 === 4) {
            $total -= 45;
        }

        $remainder = $total % 11;

        $check = match (true) {
            $remainder === 4 => 0,
            $remainder > 4   => 14 - $remainder,
            default          => 3 - $remainder,
        };

        return $check === (int) $compact[10];
    }

    private function hasPlausibleBirthDate(string $compact): bool
    {
        $day   = (int) substr($compact, 0, 2);
        $month = (int) substr($compact, 2, 2);

        return $day >= 1 && $day <= 31 && $month >= 1 && $month <= 12;
    }
}
