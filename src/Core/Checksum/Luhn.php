<?php

declare(strict_types=1);

namespace Daycry\TaxId\Core\Checksum;

/**
 * The Luhn (ISO/IEC 7812-1 "mod 10, double every second") algorithm.
 *
 * Used well beyond payment cards: France's SIREN/SIRET, Sweden's
 * organisationsnummer and personnummer, Canada's Business Number and SIN,
 * Israel's ID, and India's GSTIN (over a base-36 alphabet — see
 * {@see \Daycry\TaxId\National\InGstinValidator}).
 *
 * Catches every single-digit error and almost every adjacent transposition,
 * which is exactly the failure mode a hand-typed tax ID has.
 */
final class Luhn
{
    /**
     * Whether `$digits` (payload **including** its trailing check digit) satisfies
     * the Luhn condition.
     */
    public static function isValid(string $digits): bool
    {
        if ($digits === '' || ! ctype_digit($digits)) {
            return false;
        }

        return self::sum($digits) % 10 === 0;
    }

    /**
     * The check digit that makes `$payload` (given **without** it) Luhn-valid.
     */
    public static function checkDigit(string $payload): int
    {
        return (10 - self::sum($payload . '0') % 10) % 10;
    }

    /**
     * Luhn sum: walking right to left, every second digit is doubled and, when
     * the double exceeds 9, reduced by 9 (equivalently, its digits summed).
     */
    private static function sum(string $digits): int
    {
        $total  = 0;
        $double = false;

        for ($i = strlen($digits) - 1; $i >= 0; $i--) {
            $digit = (int) $digits[$i];

            if ($double) {
                $digit *= 2;

                if ($digit > 9) {
                    $digit -= 9;
                }
            }

            $total += $digit;
            $double = ! $double;
        }

        return $total;
    }
}
