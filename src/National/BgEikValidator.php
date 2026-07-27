<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Core\Checksum\Weights;

/**
 * Bulgaria — ЕИК / BULSTAT, the company number: nine digits for an entity,
 * thirteen for a branch.
 *
 * The nine-digit check uses weights 1…8 and, on the awkward remainder of 10,
 * retries with weights 3…10 before finally folding to `0`. The thirteen-digit
 * form validates its nine-digit core and then adds a second, shorter pass with
 * exactly the same two-attempt structure.
 */
final class BgEikValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['BG'];
    protected const array SCHEMES   = ['eik'];

    private const array WEIGHTS_9_FIRST   = [1, 2, 3, 4, 5, 6, 7, 8];
    private const array WEIGHTS_9_SECOND  = [3, 4, 5, 6, 7, 8, 9, 10];
    private const array WEIGHTS_13_FIRST  = [2, 7, 3, 5];
    private const array WEIGHTS_13_SECOND = [4, 9, 5, 7];

    public function verify(string $compact): bool
    {
        if (! ctype_digit($compact)) {
            return false;
        }

        return match (strlen($compact)) {
            9       => $this->isValidBase($compact),
            13      => $this->isValidBase(substr($compact, 0, 9)) && $this->isValidBranch($compact),
            default => false,
        };
    }

    private function isValidBase(string $digits): bool
    {
        $body = substr($digits, 0, 8);

        return $this->twoPassCheck($body, self::WEIGHTS_9_FIRST, self::WEIGHTS_9_SECOND)
            === (int) $digits[8];
    }

    private function isValidBranch(string $digits): bool
    {
        $body = substr($digits, 8, 4);

        return $this->twoPassCheck($body, self::WEIGHTS_13_FIRST, self::WEIGHTS_13_SECOND)
            === (int) $digits[12];
    }

    /**
     * The Bulgarian two-attempt rule: first weighting, and only if it yields the
     * unwritable 10 does the second weighting apply; a second 10 becomes `0`.
     *
     * @param int[] $first
     * @param int[] $second
     */
    private function twoPassCheck(string $body, array $first, array $second): int
    {
        $check = Weights::sum($body, $first) % 11;

        if ($check !== 10) {
            return $check;
        }

        $check = Weights::sum($body, $second) % 11;

        return $check === 10 ? 0 : $check;
    }
}
