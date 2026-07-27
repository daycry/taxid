<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Core\Checksum\Weights;

/**
 * Australia — TFN (Tax File Number), eight or nine digits.
 *
 * Weights `1 4 3 7 5 8 6 9 10` and the total must be divisible by 11. Older
 * eight-digit numbers use the first eight weights.
 *
 * A TFN is highly sensitive under Australian privacy law — it may not be used
 * as a general identifier. Validate it if you must collect it, but think twice
 * before storing or logging it; {@see \Daycry\TaxId\Enums\TaxIdFormat::Anonymized}
 * exists for exactly this.
 */
final class AuTfnValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['AU'];
    protected const array SCHEMES   = ['tfn'];

    private const array WEIGHTS = [1, 4, 3, 7, 5, 8, 6, 9, 10];

    public function verify(string $compact): bool
    {
        $length = strlen($compact);

        if (($length !== 8 && $length !== 9) || ! ctype_digit($compact)) {
            return false;
        }

        return Weights::sum($compact, self::WEIGHTS) % 11 === 0;
    }
}
