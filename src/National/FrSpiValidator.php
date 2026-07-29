<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

/**
 * France — numéro fiscal de référence, the thirteen-digit SPI printed on an
 * avis d'imposition.
 *
 * The last three digits are a control key: the first ten, read as one number,
 * modulo 511. The first digit is 0, 1, 2 or 3.
 *
 * The remainder is accumulated digit by digit rather than through a cast. Ten
 * digits exceed what a 32-bit platform's integer holds, and this namespace
 * already carries {@see \Daycry\TaxId\Core\Checksum\Mod97} for exactly that
 * reason — no reason to add a second place that only works on 64-bit.
 */
final class FrSpiValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['FR'];
    protected const array SCHEMES   = ['spi'];

    private const int MODULUS = 511;

    public function verify(string $compact): bool
    {
        if (strlen($compact) !== 13 || ! ctype_digit($compact)) {
            return false;
        }

        $remainder = 0;

        for ($i = 0; $i < 10; $i++) {
            $remainder = ($remainder * 10 + (int) $compact[$i]) % self::MODULUS;
        }

        return sprintf('%03d', $remainder) === substr($compact, 10);
    }
}
