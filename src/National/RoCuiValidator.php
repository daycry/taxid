<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Core\Checksum\Weights;

/**
 * Romania — CUI / CIF, the company tax number: two to ten digits.
 *
 * The body is right-aligned into nine positions against the fixed key
 * `753217532`, summed, multiplied by ten and reduced `mod 11`, with 10 folded
 * to `0`. The left-padding is what lets one algorithm serve every length.
 */
final class RoCuiValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['RO'];
    protected const array SCHEMES   = ['cui'];

    private const array WEIGHTS = [7, 5, 3, 2, 1, 7, 5, 3, 2];

    public function verify(string $compact): bool
    {
        $length = strlen($compact);

        if ($length < 2 || $length > 10 || ! ctype_digit($compact)) {
            return false;
        }

        $body  = str_pad(substr($compact, 0, -1), 9, '0', STR_PAD_LEFT);
        $check = (Weights::sum($body, self::WEIGHTS) * 10) % 11;

        return ($check === 10 ? 0 : $check) === (int) $compact[$length - 1];
    }
}
