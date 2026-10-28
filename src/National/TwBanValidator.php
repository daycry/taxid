<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

/**
 * Taiwan — business number. Since 2023 the digit-summed weighted total must
 * be divisible by 5, with the special treatment of a seventh digit of 7.
 * https://www.fia.gov.tw/singlehtml/3?cntId=c4d9cff38c8642ef8872774ee9987283
 */
final class TwBanValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['TW'];
    protected const array SCHEMES = ['ban'];

    public function verify(string $compact): bool
    {
        if (strlen($compact) !== 8 || ! ctype_digit($compact)) {
            return false;
        }

        $total = 0;

        foreach ([1, 2, 1, 2, 1, 2, 4, 1] as $i => $weight) {
            $product = (int) $compact[$i] * $weight;
            $total += intdiv($product, 10) + $product % 10;
        }

        return $total % 5 === 0 || ($compact[6] === '7' && ($total + 1) % 5 === 0);
    }
}
