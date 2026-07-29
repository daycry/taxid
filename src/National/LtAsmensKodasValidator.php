<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Core\Checksum\Weights;

/**
 * Lithuania — asmens kodas, the eleven-digit personal code, `GYYMMDDNNNC`.
 *
 * Two-pass weighted mod-11: weights 1…9,1 first and, when that produces the
 * unwritable 10, weights 3…9,1,2,3, with a second 10 folding to `0`. Identical
 * to Estonia's isikukood — the reference implementations share one function —
 * but kept as its own class rather than adding `LT` to
 * {@see EeIsikukoodValidator}: a shared class would make
 * `supports('EE', 'asmens_kodas')` answer true, and the eight lines of weights
 * are cheaper than a validator that claims schemes it has never heard of. The
 * same reason Sweden has three Luhn classes instead of one.
 *
 * `G` encodes sex and century (1/2 = 1800s, 3/4 = 1900s, 5/6 = 2000s). A
 * leading 9 marks a code issued without a known birth date, so the date fields
 * are only range-checked when there is a date to check.
 */
final class LtAsmensKodasValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['LT'];
    protected const array SCHEMES   = ['asmens_kodas'];

    private const array WEIGHTS_FIRST  = [1, 2, 3, 4, 5, 6, 7, 8, 9, 1];
    private const array WEIGHTS_SECOND = [3, 4, 5, 6, 7, 8, 9, 1, 2, 3];

    public function verify(string $compact): bool
    {
        if (strlen($compact) !== 11 || ! ctype_digit($compact)) {
            return false;
        }

        if ($compact[0] !== '9') {
            $month = (int) substr($compact, 3, 2);
            $day   = (int) substr($compact, 5, 2);

            if ($month < 1 || $month > 12 || $day < 1 || $day > 31) {
                return false;
            }
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
