<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

/**
 * China — Unified Social Credit Identifier (统一社会信用代码), eighteen characters.
 *
 * The alphabet has 31 symbols: digits plus the uppercase letters, minus `I`,
 * `O`, `S`, `V` and `Z` — the ones a human might read as `1`, `0`, `5`, `U` or
 * `2`. Weights are powers of three reduced mod 31, and the check character
 * closes the sum at `31 - (sum mod 31)`, with 31 folding to `0`.
 *
 * The first character encodes the registering authority and the second the
 * entity type; positions 3–8 are the administrative division and 9–17 the
 * organisation code.
 */
final class CnUsccValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['CN'];
    protected const array SCHEMES   = ['uscc'];

    private const string ALPHABET = '0123456789ABCDEFGHJKLMNPQRTUWXY';
    private const int BASE        = 31;

    private const array WEIGHTS = [1, 3, 9, 27, 19, 26, 16, 17, 20, 29, 25, 13, 8, 24, 10, 30, 28];

    public function verify(string $compact): bool
    {
        if (strlen($compact) !== 18) {
            return false;
        }

        $sum = 0;

        for ($i = 0; $i < 17; $i++) {
            $value = strpos(self::ALPHABET, $compact[$i]);

            if ($value === false) {
                return false;
            }

            $sum += $value * self::WEIGHTS[$i];
        }

        $check = self::BASE - $sum % self::BASE;

        if ($check === self::BASE) {
            $check = 0;
        }

        return self::ALPHABET[$check] === $compact[17];
    }
}
