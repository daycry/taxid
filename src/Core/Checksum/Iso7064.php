<?php

declare(strict_types=1);

namespace Daycry\TaxId\Core\Checksum;

/**
 * ISO/IEC 7064 check-character systems.
 *
 * Three of its variants show up in tax identifiers:
 *
 * - **MOD 11,10** — a pure-numeric hybrid system. Croatia's OIB and Serbia's PIB
 *   use it; so does Germany's 11-digit Steuerliche Identifikationsnummer.
 * - **MOD 11-2** — powers-of-two weighting with an `X` escape for a remainder of
 *   10. Backs China's 18-digit resident identity number.
 * - **MOD 37,36** — the alphanumeric sibling, over `0-9A-Z`.
 *
 * Every method takes the payload **without** its check character and returns the
 * character that completes it, so callers verify by comparison. All are pure.
 */
final class Iso7064
{
    private const ALPHABET_36 = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';

    /**
     * ISO 7064 MOD 11,10 check digit for a numeric payload.
     *
     * The hybrid recurrence: carry starts at 10; per digit
     * `s = (d + carry) mod 10` (0 folded to 10), `carry = (s * 2) mod 11`;
     * the check digit is `(11 - carry) mod 10`.
     *
     * @param string $payload Digits, without the check digit.
     */
    public static function mod1110(string $payload): int
    {
        $carry  = 10;
        $length = strlen($payload);

        for ($i = 0; $i < $length; $i++) {
            $step = ((int) $payload[$i] + $carry) % 10;

            if ($step === 0) {
                $step = 10;
            }

            $carry = ($step * 2) % 11;
        }

        return (11 - $carry) % 10;
    }

    /**
     * Whether a full numeric value (payload + trailing check digit) is MOD 11,10 valid.
     */
    public static function isValidMod1110(string $full): bool
    {
        if (strlen($full) < 2 || ! ctype_digit($full)) {
            return false;
        }

        return self::mod1110(substr($full, 0, -1)) === (int) $full[-1];
    }

    /**
     * ISO 7064 MOD 11-2 check character for a numeric payload: `'0'`–`'9'` or `'X'`.
     *
     * @param string $payload Digits, without the check character.
     */
    public static function mod112(string $payload): string
    {
        $carry  = 0;
        $length = strlen($payload);

        for ($i = 0; $i < $length; $i++) {
            $carry = (($carry + (int) $payload[$i]) * 2) % 11;
        }

        $check = (12 - $carry) % 11;

        return $check === 10 ? 'X' : (string) $check;
    }

    /**
     * ISO 7064 MOD 37,36 check character for an alphanumeric payload (`0-9A-Z`).
     *
     * @param string $payload Uppercase alphanumerics, without the check character.
     */
    public static function mod3736(string $payload): string
    {
        $carry  = 36;
        $length = strlen($payload);

        for ($i = 0; $i < $length; $i++) {
            $value = strpos(self::ALPHABET_36, $payload[$i]);

            if ($value === false) {
                $value = 0;
            }

            $step = ($value + $carry) % 36;

            if ($step === 0) {
                $step = 36;
            }

            $carry = ($step * 2) % 37;
        }

        return self::ALPHABET_36[(37 - $carry) % 36];
    }
}
