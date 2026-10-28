<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Core\Checksum\Weights;

/**
 * Switzerland — AHV/AVS/OASI personal number: 756, nine random digits, EAN-13 control.
 * https://www.zas.admin.ch/de/ahv-nummer-identifikator
 */
final class ChAhvValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['CH'];
    protected const array SCHEMES = ['ahv'];

    public function verify(string $compact): bool
    {
        return strlen($compact) === 13
            && ctype_digit($compact)
            && str_starts_with($compact, '756')
            && Weights::sum($compact, [1, 3, 1, 3, 1, 3, 1, 3, 1, 3, 1, 3, 1]) % 10 === 0;
    }
}
