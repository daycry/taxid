<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Core\Checksum\Mod97;
use Daycry\TaxId\Core\Checksum\Weights;

/**
 * Netherlands — btw-identificatienummer: nine digits, the letter `B`, and a
 * two-digit sequence number.
 *
 * Two generations coexist and both must be accepted:
 *
 * - **Classic** (companies, and individuals before 2020): the nine digits pass
 *   the *elfproef* — weights 9…2 over the first eight, remainder `mod 11`
 *   equals the ninth digit.
 * - **Post-2020 btw-id** for sole traders: deliberately unlinked from the
 *   citizen's BSN and therefore random, so it fails the elfproef by design. It
 *   instead satisfies a mod-97 check over the full `NL…B..` string with letters
 *   replaced by their IBAN-style values (`A`=10 … `Z`=35): the result must be 1.
 *
 * A value passing **either** check is accepted. Should the mod-97 reading ever
 * prove wrong, the classic path is unaffected — the failure mode is a rejected
 * new-style number, never an accepted invalid one.
 */
final class NlBtwValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['NL'];
    protected const array SCHEMES   = ['vat'];

    private const array WEIGHTS = [9, 8, 7, 6, 5, 4, 3, 2];

    public function verify(string $compact): bool
    {
        if (strlen($compact) !== 12 || $compact[9] !== 'B') {
            return false;
        }

        $digits   = substr($compact, 0, 9);
        $sequence = substr($compact, 10, 2);

        if (! ctype_digit($digits) || ! ctype_digit($sequence)) {
            return false;
        }

        // The sequence number is 1-based; "00" is never issued.
        if ($sequence === '00') {
            return false;
        }

        return $this->passesElfproef($digits) || $this->passesMod97($compact);
    }

    private function passesElfproef(string $digits): bool
    {
        $sum = Weights::sum(substr($digits, 0, 8), self::WEIGHTS);

        return $sum % 11 === (int) $digits[8];
    }

    /**
     * Mod-97 check over `NL` + the full value, letters expanded to `A`=10…`Z`=35.
     */
    private function passesMod97(string $compact): bool
    {
        $expanded = '';

        foreach (str_split('NL' . $compact) as $char) {
            $expanded .= ctype_alpha($char)
                ? (string) (ord($char) - ord('A') + 10)
                : $char;
        }

        return Mod97::remainder($expanded) === 1;
    }
}
