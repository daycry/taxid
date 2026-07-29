<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

/**
 * Ireland — VAT number, in three coexisting shapes:
 *
 * - **Old style** — seven digits and a check letter (`1234567T`).
 * - **Old individual style** — a digit, a letter or `+`/`*`, five digits and a
 *   check letter (`1A23456W`). The second character takes no part in the
 *   checksum, and the leading digit moves to the **end** of the body, which is
 *   read as `0` + the five middle digits + that leading digit. Dropping it
 *   instead — which this class did before 1.2.0 — computes a different letter.
 * - **Post-2013** — seven digits, a check letter and a trailing `A`–`I` or `W`
 *   (`1234567FA`). That trailing letter joins the sum with weight 9.
 *
 * The check letter comes from `sum mod 23` indexed into `WABCDEFGHIJKLMNOPQRSTUV`,
 * where a remainder of 0 gives `W`.
 */
final class IeVatValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['IE'];
    protected const array SCHEMES   = ['vat'];

    private const string LETTERS = 'WABCDEFGHIJKLMNOPQRSTUV';
    private const array WEIGHTS  = [8, 7, 6, 5, 4, 3, 2];

    public function verify(string $compact): bool
    {
        $length = strlen($compact);

        if ($length !== 8 && $length !== 9) {
            return false;
        }

        // Old individual style: the second character is a letter, + or *, and
        // plays no part in the checksum. The leading digit moves to the end, so
        // the seven-character body is 0 + the five middle digits + that digit.
        if ($length === 8 && ! ctype_digit($compact[1])) {
            $body = '0' . substr($compact, 2, 5) . $compact[0];

            if (! ctype_digit($body)) {
                return false;
            }

            $extra = 0;
        } else {
            $body = substr($compact, 0, 7);

            if (! ctype_digit($body)) {
                return false;
            }

            // Post-2013 style: the trailing letter contributes with weight 9.
            $extra = $length === 9 ? $this->trailingValue($compact[8]) * 9 : 0;

            if ($extra < 0) {
                return false;
            }
        }

        $sum = $extra;

        foreach (self::WEIGHTS as $index => $weight) {
            $sum += ((int) $body[$index]) * $weight;
        }

        return self::LETTERS[$sum % 23] === $compact[7];
    }

    /**
     * `A`–`I` are 1–9 and `W` is 0; anything else is not a legal trailing
     * character, reported as `-1` so the caller can reject it.
     */
    private function trailingValue(string $char): int
    {
        if ($char === 'W') {
            return 0;
        }

        if ($char >= 'A' && $char <= 'I') {
            return ord($char) - ord('A') + 1;
        }

        return -1;
    }
}
