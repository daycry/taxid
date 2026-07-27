<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

/**
 * Türkiye — VKN (vergi kimlik numarası), the ten-digit tax number for legal
 * entities.
 *
 * An unusual algorithm: each of the first nine digits is shifted by its
 * distance from the end, and — unless that shift lands on 9 — multiplied by a
 * power of two and reduced `mod 9`. The check digit closes the total `mod 10`.
 *
 * The `mod 9` step is what makes it more than a weighted sum, and why it cannot
 * be expressed with the shared {@see \Daycry\TaxId\Core\Checksum\Weights} helper.
 */
final class TrVknValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['TR'];
    protected const array SCHEMES   = ['vkn'];

    public function verify(string $compact): bool
    {
        if (strlen($compact) !== 10 || ! ctype_digit($compact)) {
            return false;
        }

        $sum = 0;

        for ($i = 0; $i < 9; $i++) {
            $shifted = ((int) $compact[$i] + 9 - $i) % 10;

            $sum += $shifted === 9
                ? $shifted
                : ($shifted * (2 ** (9 - $i))) % 9;
        }

        $check = $sum % 10 === 0 ? 0 : 10 - $sum % 10;

        return $check === (int) $compact[9];
    }
}
