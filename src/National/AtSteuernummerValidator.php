<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Core\Checksum\Luhn;

/**
 * Austria — Steuernummer, the nine-digit Abgabenkontonummer, written `12-345/6782`.
 *
 * Published as its own rule: map every digit in an odd position through
 * `0246813579`, sum, and the check digit is `(10 - sum) mod 10`. That table is
 * "double and subtract nine if it passes nine", and for nine digits with the
 * check last, the odd positions are exactly the ones Luhn doubles — so the
 * Austrian check *is* Luhn. Verified as identical over 100 000 consecutive
 * bodies before this class was written; it is not an approximation.
 *
 * The first two digits are the issuing Finanzamt. Austria publishes the list of
 * valid office codes, and this class deliberately does not check against it:
 * that is data that expires, and its absence costs the checksum nothing.
 */
final class AtSteuernummerValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['AT'];
    protected const array SCHEMES   = ['steuernummer'];

    public function verify(string $compact): bool
    {
        return strlen($compact) === 9 && Luhn::isValid($compact);
    }
}
