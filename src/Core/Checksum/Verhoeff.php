<?php

declare(strict_types=1);

namespace Daycry\TaxId\Core\Checksum;

/**
 * The Verhoeff check-digit algorithm (1969), over the dihedral group D₅.
 *
 * Luxembourg's matricule uses it for the thirteenth digit, with a {@see Luhn}
 * digit in the twelfth, and that pairing is the whole point: Luhn misses an
 * adjacent transposition whose two digits differ by five (`09` ↔ `90`,
 * `27` ↔ `72`), while Verhoeff misses none at all. Two digits from two families
 * catch strictly more than two digits from one.
 *
 * What buys that is non-commutative multiplication, which is also why the
 * tables cannot be rewritten as arithmetic: {@see Luhn} and {@see Weights} both
 * work in ℤ/10ℤ, where a transposition error can cancel itself out.
 *
 * Pure and total. A non-digit character contributes `0` rather than raising,
 * the same convention {@see Weights} documents, because callers hand over a
 * value that already matched its scheme's pattern.
 */
final class Verhoeff
{
    /**
     * Multiplication table of D₅ — five rotations then five reflections. Not
     * symmetric, and that asymmetry is the algorithm.
     *
     * @var int[][]
     */
    private const array D = [
        [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
        [1, 2, 3, 4, 0, 6, 7, 8, 9, 5],
        [2, 3, 4, 0, 1, 7, 8, 9, 5, 6],
        [3, 4, 0, 1, 2, 8, 9, 5, 6, 7],
        [4, 0, 1, 2, 3, 9, 5, 6, 7, 8],
        [5, 9, 8, 7, 6, 0, 4, 3, 2, 1],
        [6, 5, 9, 8, 7, 1, 0, 4, 3, 2],
        [7, 6, 5, 9, 8, 2, 1, 0, 4, 3],
        [8, 7, 6, 5, 9, 3, 2, 1, 0, 4],
        [9, 8, 7, 6, 5, 4, 3, 2, 1, 0],
    ];

    /**
     * The permutation applied at each position, cycling with period 8.
     *
     * @var int[][]
     */
    private const array P = [
        [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
        [1, 5, 7, 6, 2, 8, 3, 0, 9, 4],
        [5, 8, 0, 3, 7, 9, 6, 1, 4, 2],
        [8, 9, 1, 6, 0, 4, 3, 5, 2, 7],
        [9, 4, 5, 3, 1, 2, 6, 8, 7, 0],
        [4, 2, 8, 6, 5, 7, 3, 9, 0, 1],
        [2, 7, 9, 3, 8, 0, 6, 4, 1, 5],
        [7, 0, 4, 6, 9, 1, 3, 2, 5, 8],
    ];

    /**
     * Multiplicative inverses in D₅: only 1↔4 and 2↔3 move.
     *
     * @var int[]
     */
    private const array INV = [0, 4, 3, 2, 1, 5, 6, 7, 8, 9];

    /**
     * Whether `$digits` (payload **including** its trailing check digit) satisfies
     * the Verhoeff condition.
     */
    public static function isValid(string $digits): bool
    {
        if ($digits === '' || ! ctype_digit($digits)) {
            return false;
        }

        return self::interim($digits, 0) === 0;
    }

    /**
     * The check digit that makes `$payload` (given **without** it) Verhoeff-valid.
     */
    public static function checkDigit(string $payload): int
    {
        return self::INV[self::interim($payload, 1)];
    }

    /**
     * Fold the digits right to left through the permutation and then the group.
     *
     * `$offset` is what separates the two operations: `0` verifies a value whose
     * check digit is already in place, `1` shifts every permutation up one so
     * position zero stays free for the digit about to be appended.
     */
    private static function interim(string $digits, int $offset): int
    {
        $carry  = 0;
        $length = strlen($digits);

        for ($i = 0; $i < $length; $i++) {
            $digit = (int) $digits[$length - 1 - $i];
            $carry = self::D[$carry][self::P[($i + $offset) % 8][$digit]];
        }

        return $carry;
    }
}
