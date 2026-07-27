<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Core\Checksum\Iso7064;

/**
 * China — resident identity card number (居民身份证号码), eighteen characters,
 * which doubles as an individual's taxpayer identification number.
 *
 * Six digits of administrative division, eight of birth date, three of
 * sequence, and an ISO/IEC 7064 MOD 11-2 check character — the one place a
 * Chinese ID can show a letter, `X` standing in for a check value of 10.
 */
final class CnRicValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['CN'];
    protected const array SCHEMES   = ['ric'];

    public function verify(string $compact): bool
    {
        if (strlen($compact) !== 18) {
            return false;
        }

        $body = substr($compact, 0, 17);

        if (! ctype_digit($body)) {
            return false;
        }

        $month = (int) substr($compact, 10, 2);
        $day   = (int) substr($compact, 12, 2);

        if ($month < 1 || $month > 12 || $day < 1 || $day > 31) {
            return false;
        }

        return Iso7064::mod112($body) === $compact[17];
    }
}
