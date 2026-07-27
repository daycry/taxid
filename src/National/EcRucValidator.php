<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Core\Checksum\Weights;

/**
 * Ecuador — cédula (ten digits) and RUC (thirteen).
 *
 * Three different algorithms hide behind one format, selected by the **third**
 * digit:
 *
 * - **0–5, natural person** — mod-10 with alternating 2/1 coefficients, digits
 *   over 9 reduced by 9 (a Luhn in all but name), check digit in position 10.
 *   A natural person's RUC is that cédula plus `001`.
 * - **6, public body** — mod-11 with the key `32765432` over the first eight
 *   digits, check digit in position 9, followed by `0001`.
 * - **9, private company** — mod-11 with the key `432765432` over the first
 *   *nine* digits, check digit in position 10, followed by `001`.
 *
 * The two weightings differ in length as well as value, which is why they are
 * applied to different-sized bodies rather than shared.
 *
 * The first two digits are the province and must be 01–24, or 30 for citizens
 * registered abroad.
 */
final class EcRucValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['EC'];
    protected const array SCHEMES   = ['ruc', 'cedula'];

    private const array NATURAL_COEFFICIENTS = [2, 1, 2, 1, 2, 1, 2, 1, 2];
    private const array PUBLIC_WEIGHTS       = [3, 2, 7, 6, 5, 4, 3, 2];
    private const array PRIVATE_WEIGHTS      = [4, 3, 2, 7, 6, 5, 4, 3, 2];

    public function verify(string $compact): bool
    {
        $length = strlen($compact);

        if (($length !== 10 && $length !== 13) || ! ctype_digit($compact)) {
            return false;
        }

        $province = (int) substr($compact, 0, 2);

        if (($province < 1 || $province > 24) && $province !== 30) {
            return false;
        }

        $third = (int) $compact[2];

        return match (true) {
            $third < 6   => $this->isValidNatural($compact, $length),
            $third === 6 => $this->isValidPublic($compact, $length),
            $third === 9 => $this->isValidPrivate($compact, $length),
            default      => false,
        };
    }

    private function isValidNatural(string $compact, int $length): bool
    {
        if ($length === 13 && ! str_ends_with($compact, '001')) {
            return false;
        }

        $sum = 0;

        foreach (self::NATURAL_COEFFICIENTS as $index => $coefficient) {
            $value = ((int) $compact[$index]) * $coefficient;
            $sum += $value > 9 ? $value - 9 : $value;
        }

        return (10 - $sum % 10) % 10 === (int) $compact[9];
    }

    private function isValidPublic(string $compact, int $length): bool
    {
        if ($length !== 13 || ! str_ends_with($compact, '0001')) {
            return false;
        }

        return $this->mod11Check($compact, self::PUBLIC_WEIGHTS, 8);
    }

    private function isValidPrivate(string $compact, int $length): bool
    {
        if ($length !== 13 || ! str_ends_with($compact, '001')) {
            return false;
        }

        return $this->mod11Check($compact, self::PRIVATE_WEIGHTS, 9);
    }

    /**
     * @param int[] $weights           As many weights as there are body digits.
     * @param int   $checkPosition     0-based index of the check digit; the body
     *                                 is everything before it.
     */
    private function mod11Check(string $compact, array $weights, int $checkPosition): bool
    {
        $remainder = Weights::sum(substr($compact, 0, $checkPosition), $weights) % 11;
        $check     = $remainder === 0 ? 0 : 11 - $remainder;

        return $check < 10 && $check === (int) $compact[$checkPosition];
    }
}
