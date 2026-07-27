<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

/**
 * Venezuela — RIF (Registro Único de Información Fiscal): a type letter and
 * nine digits.
 *
 * The letter is not decoration — it enters the checksum with weight 4 and its
 * own numeric value (`V` 1, `E` 2, `J` 3, `P` 4, `G` 5), so a company RIF and a
 * personal one with the same digits have different check digits. The remaining
 * weights are `3 2 7 6 5 4 3 2`, mod-11, with both overflow cases folded to `0`.
 */
final class VeRifValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['VE'];
    protected const array SCHEMES   = ['rif'];

    private const array TYPE_VALUES = ['V' => 1, 'E' => 2, 'J' => 3, 'P' => 4, 'G' => 5];
    private const array WEIGHTS     = [3, 2, 7, 6, 5, 4, 3, 2];

    public function verify(string $compact): bool
    {
        if (strlen($compact) !== 10) {
            return false;
        }

        $type   = $compact[0];
        $digits = substr($compact, 1);

        if (! isset(self::TYPE_VALUES[$type]) || ! ctype_digit($digits)) {
            return false;
        }

        $sum = self::TYPE_VALUES[$type] * 4;

        foreach (self::WEIGHTS as $index => $weight) {
            $sum += ((int) $digits[$index]) * $weight;
        }

        $check = 11 - $sum % 11;

        if ($check >= 10) {
            $check = 0;
        }

        return $check === (int) $digits[8];
    }
}
