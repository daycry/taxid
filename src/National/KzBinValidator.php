<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Core\Checksum\Weights;

/**
 * Kazakhstan — the shared BIN/IIN two-pass mod-11 control.
 * https://adilet.zan.kz/rus/docs/P070000352_
 * https://adilet.zan.kz/rus/docs/V2300032942
 * Only the checksum is enforced: no inferred birth date or entity category.
 */
final class KzBinValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['KZ'];
    protected const array SCHEMES = ['bin'];

    public function verify(string $compact): bool
    {
        if (strlen($compact) !== 12 || ! ctype_digit($compact)) {
            return false;
        }

        $body = substr($compact, 0, 11);
        $check = Weights::sum($body, [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11]) % 11;

        if ($check === 10) {
            $check = Weights::sum($body, [3, 4, 5, 6, 7, 8, 9, 10, 11, 1, 2]) % 11;
        }

        return $check !== 10 && $check === (int) $compact[11];
    }
}
