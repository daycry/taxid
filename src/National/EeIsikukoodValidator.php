<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Core\Checksum\Weights;

/**
 * Estonia — isikukood, the eleven-digit personal identification code.
 *
 * Two-pass weighted mod-11: weights 1…9,1 first and, when that produces the
 * unwritable 10, weights 3…9,1,2,3, with a second 10 folding to `0`. The same
 * two-attempt shape Bulgaria and Lithuania use — a common solution to the same
 * mod-11 problem.
 *
 * The leading digit encodes sex and century (1/2 = 1800s, 3/4 = 1900s,
 * 5/6 = 2000s), constrained by the registry pattern.
 */
final class EeIsikukoodValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['EE'];
    protected const array SCHEMES   = ['isikukood'];

    private const array WEIGHTS_FIRST  = [1, 2, 3, 4, 5, 6, 7, 8, 9, 1];
    private const array WEIGHTS_SECOND = [3, 4, 5, 6, 7, 8, 9, 1, 2, 3];

    public function verify(string $compact): bool
    {
        if (strlen($compact) !== 11 || ! ctype_digit($compact)) {
            return false;
        }

        $body  = substr($compact, 0, 10);
        $check = Weights::sum($body, self::WEIGHTS_FIRST) % 11;

        if ($check === 10) {
            $check = Weights::sum($body, self::WEIGHTS_SECOND) % 11;
            $check = $check === 10 ? 0 : $check;
        }

        return $check === (int) $compact[10];
    }
}
