<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Contracts\ChecksumAwareValidatorInterface;
use Daycry\TaxId\Core\Checksum\Weights;

/**
 * Czechia — DIČ (tax number) and IČO (company number).
 *
 * The DIČ behind `CZ` takes three shapes, and which one it is follows from the
 * length alone:
 *
 * - **8 digits** — a legal entity's IČO: weights 8…2, check digit
 *   `(11 - sum mod 11) mod 10`.
 * - **9 digits** — an individual's pre-1954 rodné číslo, which predates the
 *   check-digit rule. Only the date part is sanity-checked.
 * - **10 digits** — a modern rodné číslo: the whole number must be divisible by
 *   11, with a documented exception for 1954–1985 births where a remainder of
 *   10 was written as a check digit of `0`.
 */
final class CzDicValidator extends AbstractNationalValidator implements ChecksumAwareValidatorInterface
{
    protected const array COUNTRIES = ['CZ'];
    protected const array SCHEMES   = ['dic', 'ico'];

    private const array ICO_WEIGHTS = [8, 7, 6, 5, 4, 3, 2];

    public function hasChecksum(string $compact): bool
    {
        return strlen($compact) !== 9;
    }

    public function verify(string $compact): bool
    {
        if (! ctype_digit($compact)) {
            return false;
        }

        return match (strlen($compact)) {
            8       => self::isValidIco($compact),
            9       => self::isPlausibleBirthNumber($compact),
            10      => self::isValidBirthNumber($compact),
            default => false,
        };
    }

    /**
     * The eight-digit IČO check, shared with Slovakia's identically-shaped IČO —
     * both descend from the same Czechoslovak standard.
     */
    public static function isValidIco(string $digits): bool
    {
        if (strlen($digits) !== 8 || ! ctype_digit($digits)) {
            return false;
        }

        $remainder = Weights::sum(substr($digits, 0, 7), self::ICO_WEIGHTS) % 11;

        return (11 - $remainder) % 10 === (int) $digits[7];
    }

    /**
     * Pre-1954 rodné číslo: no check digit ever existed, so only the encoded
     * date is examined.
     *
     * Public and static for the same reason as {@see isValidIco()}: Slovakia
     * kept the identical standard after 1993, so {@see SkRodneCisloValidator}
     * shares this rather than carrying a second copy of it.
     */
    public static function isPlausibleBirthNumber(string $digits): bool
    {
        return strlen($digits) === 9 && ctype_digit($digits) && self::hasPlausibleDate($digits);
    }

    /**
     * Modern ten-digit rodné číslo. Shared with Slovakia — see
     * {@see isPlausibleBirthNumber()}.
     */
    public static function isValidBirthNumber(string $digits): bool
    {
        if (strlen($digits) !== 10 || ! ctype_digit($digits) || ! self::hasPlausibleDate($digits)) {
            return false;
        }

        $body  = (int) substr($digits, 0, 9);
        $check = (int) $digits[9];

        $remainder = $body % 11;

        // 1954-1985: a remainder of 10 was recorded as a check digit of 0.
        if ($remainder === 10) {
            return $check === 0;
        }

        return $remainder === $check;
    }

    /**
     * Month is offset by 50 for women and, since 2004, by a further 20 when a
     * day's numbers run out; day must be 1–31.
     */
    private static function hasPlausibleDate(string $digits): bool
    {
        $month = (int) substr($digits, 2, 2) % 50 % 20;
        $day   = (int) substr($digits, 4, 2);

        return $month >= 1 && $month <= 12 && $day >= 1 && $day <= 31;
    }
}
