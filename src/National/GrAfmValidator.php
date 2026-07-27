<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

/**
 * Greece — ΑΦΜ (AFM), nine digits. Quoted internationally with the prefix `EL`
 * rather than `GR`, which is why the registry records a VAT prefix distinct
 * from the country code.
 *
 * The weights are successive powers of two — 256, 128, … , 2 — over the first
 * eight digits, reduced `mod 11` with 10 folding to `0`. Doubling weights make
 * the check unusually good at catching transpositions.
 */
final class GrAfmValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['GR'];
    protected const array SCHEMES   = ['afm'];

    public function verify(string $compact): bool
    {
        if (strlen($compact) !== 9 || ! ctype_digit($compact)) {
            return false;
        }

        if ($compact === '000000000') {
            return false;
        }

        $sum = 0;

        for ($i = 0; $i < 8; $i++) {
            $sum += ((int) $compact[$i]) * (2 ** (8 - $i));
        }

        return $sum % 11 % 10 === (int) $compact[8];
    }
}
