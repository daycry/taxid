<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Contracts\ReservedValueDetectorInterface;

/**
 * United States — Social Security Number, nine digits.
 *
 * There is **no check digit**: since randomization in 2011 an SSN carries no
 * derivable structure at all. What the SSA does publish is which values it will
 * never issue, and that is what this class enforces:
 *
 * - area (first three) `000`, `666`, or anything from `900` up;
 * - group (middle two) `00`;
 * - serial (last four) `0000`.
 *
 * Those cases are reported as {@see \Daycry\TaxId\Enums\ViolationCode::ReservedValue}
 * rather than a check-digit failure, because that is honestly what they are.
 * `verify()` therefore always succeeds — the pattern already did the structural
 * work.
 *
 * Note the widely-quoted `078-05-1120` (the "Woolworth wallet" number) *is*
 * structurally valid; it was issued, just to a great many people by accident.
 */
final class UsSsnValidator extends AbstractNationalValidator implements ReservedValueDetectorInterface
{
    protected const array COUNTRIES = ['US'];
    protected const array SCHEMES   = ['ssn'];

    public function verify(string $compact): bool
    {
        return strlen($compact) === 9 && ctype_digit($compact);
    }

    public function isReserved(string $compact): bool
    {
        if (strlen($compact) !== 9 || ! ctype_digit($compact)) {
            return false;
        }

        $area   = substr($compact, 0, 3);
        $group  = substr($compact, 3, 2);
        $serial = substr($compact, 5, 4);

        return $area === '000'
            || $area === '666'
            || $area[0] === '9'
            || $group === '00'
            || $serial === '0000';
    }
}
