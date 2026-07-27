<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Core\Checksum\Weights;

/**
 * Chile — RUT / RUN, seven to eight digits and a verifier that may be `K`.
 *
 * Cycling weights 2…7 applied from the right, mod-11, with the two overflow
 * cases written as `K` (for 10) and `0` (for 11). The `K` is the giveaway that
 * a value is Chilean, and the reason the scheme's pattern is not purely
 * numeric.
 */
final class ClRutValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['CL'];
    protected const array SCHEMES   = ['rut'];

    private const array WEIGHTS = [2, 3, 4, 5, 6, 7];

    public function verify(string $compact): bool
    {
        $length = strlen($compact);

        if ($length < 8 || $length > 9) {
            return false;
        }

        $body     = substr($compact, 0, -1);
        $verifier = $compact[$length - 1];

        if (! ctype_digit($body)) {
            return false;
        }

        $remainder = Weights::cyclicSum($body, self::WEIGHTS) % 11;

        $expected = match (11 - $remainder) {
            11      => '0',
            10      => 'K',
            default => (string) (11 - $remainder),
        };

        return $expected === $verifier;
    }
}
