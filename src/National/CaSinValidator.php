<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Contracts\ReservedValueDetectorInterface;
use Daycry\TaxId\Core\Checksum\Luhn;

/**
 * Canada — Social Insurance Number, nine digits, Luhn.
 *
 * The first digit identifies the issuing region and is never `0` or `8`.
 * A leading `9` marks a temporary SIN issued to someone who is neither a
 * citizen nor a permanent resident — valid, but worth flagging in some
 * contexts, so it is *not* treated as reserved here.
 */
final class CaSinValidator extends AbstractNationalValidator implements ReservedValueDetectorInterface
{
    protected const array COUNTRIES = ['CA'];
    protected const array SCHEMES   = ['sin'];

    public function verify(string $compact): bool
    {
        return strlen($compact) === 9 && ctype_digit($compact) && Luhn::isValid($compact);
    }

    public function isReserved(string $compact): bool
    {
        return strlen($compact) === 9 && ($compact[0] === '0' || $compact[0] === '8');
    }
}
