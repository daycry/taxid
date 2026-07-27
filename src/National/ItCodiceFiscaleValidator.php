<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

/**
 * Italy — codice fiscale, the sixteen-character personal identifier.
 *
 * Unlike almost every other tax ID here it is *derived* from the person:
 * consonants of the surname and given name, year and month of birth, day
 * offset by 40 for women, the Belfiore code of the birthplace, and finally a
 * check letter.
 *
 * That check letter comes from two lookup tables — one for odd positions, one
 * for even (both 1-indexed) — summed and reduced `mod 26`. The odd table is
 * deliberately irregular, which is what gives the code its error-detecting
 * power over a plain sum.
 *
 * **Omocodia** is handled: when two people would collide, the tax agency
 * replaces digits with letters (`L M N P Q R S T U V` for 0–9) from the right,
 * so those letters are translated back before the numeric fields are read.
 */
final class ItCodiceFiscaleValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['IT'];
    protected const array SCHEMES   = ['cf'];

    /** Substitutions used by the omocodia scheme, letter => original digit. */
    private const array OMOCODIA = [
        'L' => '0', 'M' => '1', 'N' => '2', 'P' => '3', 'Q' => '4',
        'R' => '5', 'S' => '6', 'T' => '7', 'U' => '8', 'V' => '9',
    ];

    private const array ODD = [
        '0' => 1, '1' => 0, '2' => 5, '3' => 7, '4' => 9, '5' => 13, '6' => 15,
        '7' => 17, '8' => 19, '9' => 21, 'A' => 1, 'B' => 0, 'C' => 5, 'D' => 7,
        'E' => 9, 'F' => 13, 'G' => 15, 'H' => 17, 'I' => 19, 'J' => 21, 'K' => 2,
        'L' => 4, 'M' => 18, 'N' => 20, 'O' => 11, 'P' => 3, 'Q' => 6, 'R' => 8,
        'S' => 12, 'T' => 14, 'U' => 16, 'V' => 10, 'W' => 22, 'X' => 25,
        'Y' => 24, 'Z' => 23,
    ];

    public function verify(string $compact): bool
    {
        if (strlen($compact) !== 16) {
            return false;
        }

        $sum = 0;

        for ($i = 0; $i < 15; $i++) {
            $char = $compact[$i];

            if ($i % 2 === 0) {
                // Odd position in the 1-indexed official table.
                if (! isset(self::ODD[$char])) {
                    return false;
                }

                $sum += self::ODD[$char];

                continue;
            }

            $value = self::evenValue($char);

            if ($value === null) {
                return false;
            }

            $sum += $value;
        }

        return chr(ord('A') + $sum % 26) === $compact[15];
    }

    /**
     * Even-position value: digits are themselves, letters are their zero-based
     * alphabet index.
     */
    private static function evenValue(string $char): ?int
    {
        if ($char >= '0' && $char <= '9') {
            return (int) $char;
        }

        if ($char >= 'A' && $char <= 'Z') {
            return ord($char) - ord('A');
        }

        return null;
    }

    /**
     * Undo omocodia substitutions in the numeric fields of a codice fiscale.
     *
     * Public because the same translation is what lets an application recover
     * the encoded birth date from a substituted code.
     */
    public static function decodeOmocodia(string $compact): string
    {
        if (strlen($compact) !== 16) {
            return $compact;
        }

        // Positions (0-based) that hold digits in a non-substituted code.
        foreach ([6, 7, 9, 10, 12, 13, 14] as $position) {
            $char = $compact[$position];

            if (isset(self::OMOCODIA[$char])) {
                $compact[$position] = self::OMOCODIA[$char];
            }
        }

        return $compact;
    }
}
