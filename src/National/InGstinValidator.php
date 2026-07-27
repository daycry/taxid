<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

/**
 * India — GSTIN, fifteen characters: a two-digit state code, the taxpayer's
 * ten-character PAN, an entity number, a literal `Z`, and a check character.
 *
 * The check is a Luhn run over base 36 rather than base 10: character values
 * come from `0-9A-Z`, alternate positions are doubled, and each product is
 * folded by adding its quotient and remainder against 36 (the base-36
 * equivalent of "subtract 9").
 *
 * Because the PAN sits inside the GSTIN, a typo anywhere — state, PAN or entity
 * number — changes the final character.
 */
final class InGstinValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['IN'];
    protected const array SCHEMES   = ['gstin'];

    private const string ALPHABET = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    private const int BASE        = 36;

    public function verify(string $compact): bool
    {
        if (strlen($compact) !== 15) {
            return false;
        }

        $factor = 2;
        $sum    = 0;

        for ($i = 13; $i >= 0; $i--) {
            $value = strpos(self::ALPHABET, $compact[$i]);

            if ($value === false) {
                return false;
            }

            $addend = $factor * $value;
            $factor = $factor === 2 ? 1 : 2;

            $sum += intdiv($addend, self::BASE) + $addend % self::BASE;
        }

        $check = (self::BASE - $sum % self::BASE) % self::BASE;

        return self::ALPHABET[$check] === $compact[14];
    }
}
