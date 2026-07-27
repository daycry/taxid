<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

/**
 * Colombia — NIT (Número de Identificación Tributaria): a body of up to fifteen
 * digits plus a *dígito de verificación*.
 *
 * The weights are the first fifteen primes-ish constants published by the DIAN
 * (`3 7 13 17 19 23 29 37 41 43 47 53 59 67 71`), applied from the right so one
 * table serves every length. The check is `sum mod 11`, or `11 - that` once it
 * reaches 2 — the same fold Brazil uses.
 */
final class CoNitValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['CO'];
    protected const array SCHEMES   = ['nit'];

    private const array WEIGHTS = [3, 7, 13, 17, 19, 23, 29, 37, 41, 43, 47, 53, 59, 67, 71];

    public function verify(string $compact): bool
    {
        $length = strlen($compact);

        if ($length < 6 || $length > 16 || ! ctype_digit($compact)) {
            return false;
        }

        $body  = substr($compact, 0, -1);
        $sum   = 0;
        $count = strlen($body);

        for ($i = 0; $i < $count; $i++) {
            $sum += ((int) $body[$count - 1 - $i]) * self::WEIGHTS[$i];
        }

        $remainder = $sum % 11;
        $check     = $remainder < 2 ? $remainder : 11 - $remainder;

        return $check === (int) $compact[$length - 1];
    }
}
