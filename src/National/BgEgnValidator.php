<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Core\Checksum\Weights;

/**
 * Bulgaria — ЕГН (единен граждански номер), the ten-digit personal number,
 * which also serves as an individual's VAT number behind `BG`.
 *
 * Weighted mod-11 with the key `248510973`, a remainder of 10 folding to `0`.
 *
 * The month field encodes the century by offset: `+20` for the 1800s and `+40`
 * for the 2000s. That is validated here, because an out-of-range month means
 * the value is not an EGN at all rather than merely mistyped.
 */
final class BgEgnValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['BG'];
    protected const array SCHEMES   = ['egn'];

    private const array WEIGHTS = [2, 4, 8, 5, 10, 9, 7, 3, 6];

    public function verify(string $compact): bool
    {
        if (strlen($compact) !== 10 || ! ctype_digit($compact)) {
            return false;
        }

        $month = (int) substr($compact, 2, 2);
        $day   = (int) substr($compact, 4, 2);

        if ($month > 40) {
            $month -= 40;
        } elseif ($month > 20) {
            $month -= 20;
        }

        if ($month < 1 || $month > 12 || $day < 1 || $day > 31) {
            return false;
        }

        $check = Weights::sum(substr($compact, 0, 9), self::WEIGHTS) % 11;

        return ($check === 10 ? 0 : $check) === (int) $compact[9];
    }
}
