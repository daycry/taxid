<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Contracts\ReservedValueDetectorInterface;

/**
 * United States — Employer Identification Number, nine digits.
 *
 * An EIN has **no check digit**. Its first two digits are a campus prefix that
 * used to encode geography and no longer does; the IRS publishes the list of
 * prefixes in use, but it has grown over the years and pinning it down here
 * would reject legitimately-issued numbers as the list changes.
 *
 * So only the one prefix that is unambiguously never issued — `00` — is treated
 * as reserved. Everything else is accepted on shape, which is the honest limit
 * of what can be checked offline. Confirming an EIN really belongs to a
 * business requires the IRS TIN-matching programme, not arithmetic.
 */
final class UsEinValidator extends AbstractNationalValidator implements ReservedValueDetectorInterface
{
    protected const array COUNTRIES = ['US'];
    protected const array SCHEMES   = ['ein'];

    public function verify(string $compact): bool
    {
        return strlen($compact) === 9 && ctype_digit($compact);
    }

    public function isReserved(string $compact): bool
    {
        return str_starts_with($compact, '00');
    }
}
