<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

/**
 * Spain — NIF (natural persons, ex-DNI) and NIE (foreign residents).
 *
 * Both are the same algorithm: take the eight-digit number and index the
 * 23-letter alphabet `TRWAGMYFPDXBNJZSQVHLCKE` with `number mod 23`. The NIE
 * differs only in that its leading letter stands in for a digit — `X` → 0,
 * `Y` → 1, `Z` → 2 — which is how the series was extended once `X` ran out.
 *
 * The alphabet deliberately omits `I`, `Ñ`, `O` and `U` to avoid confusion with
 * `1` and `0`, which is also why a mistyped digit almost always produces a
 * wrong letter rather than another valid NIF.
 */
final class EsDniValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['ES'];
    protected const array SCHEMES   = ['nif', 'nie'];

    private const string LETTERS = 'TRWAGMYFPDXBNJZSQVHLCKE';

    /** NIE prefix => the digit it stands for. */
    private const array NIE_PREFIXES = ['X' => '0', 'Y' => '1', 'Z' => '2'];

    public function verify(string $compact): bool
    {
        if (strlen($compact) !== 9) {
            return false;
        }

        $body   = substr($compact, 0, 8);
        $letter = $compact[8];

        $first = $body[0];

        if (isset(self::NIE_PREFIXES[$first])) {
            $body = self::NIE_PREFIXES[$first] . substr($body, 1);
        }

        if (! ctype_digit($body)) {
            return false;
        }

        return self::LETTERS[((int) $body) % 23] === $letter;
    }
}
