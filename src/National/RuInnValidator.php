<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Core\Checksum\Weights;

/**
 * Russia — ИНН (INN): ten digits for an organisation, twelve for an individual.
 *
 * Both use the same weighting family, closed with `sum mod 11 mod 10` — the
 * second modulus folding the otherwise unwritable 10 down to `0`.
 *
 * The twelve-digit form carries **two** check digits: the eleventh closes a
 * pass over the first ten, and the twelfth closes a longer pass over the first
 * eleven. Each weight table is the next one shifted by one position, which is
 * what makes the two checks independent.
 */
final class RuInnValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['RU'];
    protected const array SCHEMES   = ['inn'];

    private const array WEIGHTS_10 = [2, 4, 10, 3, 5, 9, 4, 6, 8];
    private const array WEIGHTS_11 = [7, 2, 4, 10, 3, 5, 9, 4, 6, 8];
    private const array WEIGHTS_12 = [3, 7, 2, 4, 10, 3, 5, 9, 4, 6, 8];

    public function verify(string $compact): bool
    {
        if (! ctype_digit($compact)) {
            return false;
        }

        return match (strlen($compact)) {
            10      => $this->digit($compact, self::WEIGHTS_10) === (int) $compact[9],
            12      => $this->digit($compact, self::WEIGHTS_11) === (int) $compact[10]
                && $this->digit($compact, self::WEIGHTS_12) === (int) $compact[11],
            default => false,
        };
    }

    /**
     * @param int[] $weights
     */
    private function digit(string $compact, array $weights): int
    {
        return Weights::sum($compact, $weights) % 11 % 10;
    }
}
