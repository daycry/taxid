<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

/**
 * Finland — henkilötunnus, the personal identity code, `DDMMYYCZZZQ`.
 *
 * The nine digits of the date and the serial, read as one integer, modulo 31,
 * indexed into a 31-character alphabet. `C` is the century marker and takes no
 * part in it: `+` for the 1800s, `-` or `U`-`Y` for the 1900s, `A`-`F` for the
 * 2000s.
 *
 * Which is why this validator accepts two lengths. Normalization strips `+` and
 * `-`, leaving ten characters; a letter marker survives, leaving eleven. Both
 * are the same code and produce the same check character.
 *
 * Serials 900-999 mark temporary identifiers. They are really issued, so they
 * are accepted; rejecting them would be inventing a rule.
 */
final class FiHenkilotunnusValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['FI'];
    protected const array SCHEMES   = ['henkilotunnus'];

    /**
     * G, I, O, Q and Z are absent on purpose — they are the characters a reader
     * mistakes for 6, 1, 0, 0 and 2.
     */
    private const string ALPHABET = '0123456789ABCDEFHJKLMNPRSTUVWXY';

    public function verify(string $compact): bool
    {
        $length = strlen($compact);

        if ($length !== 10 && $length !== 11) {
            return false;
        }

        // Eleven characters means the century marker survived normalization, so
        // it sits at index 6 and is skipped; ten means it was punctuation.
        $body = $length === 11
            ? substr($compact, 0, 6) . substr($compact, 7, 3)
            : substr($compact, 0, 9);

        if (! ctype_digit($body)) {
            return false;
        }

        $day   = (int) substr($body, 0, 2);
        $month = (int) substr($body, 2, 2);

        // A range check, not a calendar: the house convention, since a wrong
        // month means the value is not a henkilötunnus rather than mistyped.
        if ($day < 1 || $day > 31 || $month < 1 || $month > 12) {
            return false;
        }

        return self::ALPHABET[((int) $body) % 31] === $compact[$length - 1];
    }
}
