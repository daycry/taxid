<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Core\Checksum\Weights;

/**
 * South Korea — business registration number (사업자등록번호), ten digits.
 *
 * Weights `1 3 7 1 3 7 1 3 5`, plus one extra term that is easy to miss: the
 * ninth digit's product with 5 is *also* divided by ten and its quotient added
 * to the sum. The check digit then closes it at `(10 - sum mod 10) mod 10`.
 *
 * That extra carry term is what distinguishes the Korean algorithm from an
 * ordinary weighted mod-10.
 */
final class KrBrnValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['KR'];
    protected const array SCHEMES   = ['brn'];

    private const array WEIGHTS = [1, 3, 7, 1, 3, 7, 1, 3, 5];

    public function verify(string $compact): bool
    {
        if (strlen($compact) !== 10 || ! ctype_digit($compact)) {
            return false;
        }

        $sum = Weights::sum(substr($compact, 0, 9), self::WEIGHTS);
        $sum += intdiv(((int) $compact[8]) * 5, 10);

        return (10 - $sum % 10) % 10 === (int) $compact[9];
    }
}
