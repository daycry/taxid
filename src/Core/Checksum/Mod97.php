<?php

declare(strict_types=1);

namespace Daycry\TaxId\Core\Checksum;

/**
 * Modulo-97 over arbitrarily long numeric strings.
 *
 * The same primitive IBAN check digits are built on; among tax IDs it backs
 * Belgium's BTW/TVA, the United Kingdom's VAT number and France's TVA
 * intracommunautaire key.
 *
 * Computed digit by digit (`r = (r * 10 + d) % 97`) so it needs neither bcmath
 * nor GMP and never overflows: the running value stays below 979.
 */
final class Mod97
{
    /**
     * `$numeric mod 97`. Non-digit characters are skipped.
     */
    public static function remainder(string $numeric): int
    {
        $remainder = 0;
        $length    = strlen($numeric);

        for ($i = 0; $i < $length; $i++) {
            $char = $numeric[$i];

            if ($char < '0' || $char > '9') {
                continue;
            }

            $remainder = ($remainder * 10 + (int) $char) % 97;
        }

        return $remainder;
    }
}
