<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Contracts\ReservedValueDetectorInterface;

/**
 * United States — Individual Taxpayer Identification Number, nine digits.
 *
 * An ITIN is deliberately shaped like an SSN the SSA would never issue: it
 * always begins with `9`, and its middle two digits fall in one of the ranges
 * the IRS has opened — 50–65, 70–88, 90–92 and 94–99. (70–80 came first;
 * the rest were added as the ranges filled.)
 *
 * No check digit exists, so out-of-range values are reported as reserved.
 */
final class UsItinValidator extends AbstractNationalValidator implements ReservedValueDetectorInterface
{
    protected const array COUNTRIES = ['US'];
    protected const array SCHEMES   = ['itin'];

    /** Allocated middle-group ranges, inclusive. */
    private const array GROUPS = [[50, 65], [70, 88], [90, 92], [94, 99]];

    public function verify(string $compact): bool
    {
        return strlen($compact) === 9 && ctype_digit($compact) && $compact[0] === '9';
    }

    public function isReserved(string $compact): bool
    {
        if (strlen($compact) !== 9 || ! ctype_digit($compact)) {
            return false;
        }

        $group = (int) substr($compact, 3, 2);

        foreach (self::GROUPS as [$low, $high]) {
            if ($group >= $low && $group <= $high) {
                return false;
            }
        }

        return true;
    }
}
