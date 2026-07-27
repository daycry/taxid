<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Core\Checksum\Weights;

/**
 * Poland — REGON, the statistical business number: nine digits for an entity,
 * fourteen for one of its local units.
 *
 * Both are weighted mod-11 with a check digit of `sum mod 11`, folded to `0`
 * when that lands on 10. The fourteen-digit form checks the nine-digit core
 * first, then its own longer weighting — so a local unit whose parent number is
 * wrong fails immediately.
 */
final class PlRegonValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['PL'];
    protected const array SCHEMES   = ['regon'];

    private const array WEIGHTS_9  = [8, 9, 2, 3, 4, 5, 6, 7];
    private const array WEIGHTS_14 = [2, 4, 8, 5, 0, 9, 7, 3, 6, 1, 2, 4, 8];

    public function verify(string $compact): bool
    {
        if (! ctype_digit($compact)) {
            return false;
        }

        return match (strlen($compact)) {
            9       => $this->checkDigit($compact, self::WEIGHTS_9),
            14      => $this->checkDigit(substr($compact, 0, 9), self::WEIGHTS_9)
                && $this->checkDigit($compact, self::WEIGHTS_14),
            default => false,
        };
    }

    /**
     * @param int[] $weights
     */
    private function checkDigit(string $digits, array $weights): bool
    {
        $body      = substr($digits, 0, -1);
        $remainder = Weights::sum($body, $weights) % 11;

        return ($remainder === 10 ? 0 : $remainder) === (int) $digits[strlen($digits) - 1];
    }
}
