<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Contracts\ReservedValueDetectorInterface;
use Daycry\TaxId\Core\Checksum\Weights;

/**
 * Brazil — CNPJ (Cadastro Nacional da Pessoa Jurídica), fourteen digits:
 * an eight-digit root, a four-digit branch (`0001` for the head office) and two
 * check digits.
 *
 * Same mod-11 rule as the CPF, but the weights restart at 9 after descending to
 * 2 — hence the `5 4 3 2 9 8 7 6 5 4 3 2` pattern.
 *
 * Fourteen identical digits pass the arithmetic and are never issued; they are
 * flagged as reserved.
 */
final class BrCnpjValidator extends AbstractNationalValidator implements ReservedValueDetectorInterface
{
    protected const array COUNTRIES = ['BR'];
    protected const array SCHEMES   = ['cnpj'];

    private const array WEIGHTS_FIRST  = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
    private const array WEIGHTS_SECOND = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

    public function verify(string $compact): bool
    {
        if (strlen($compact) !== 14 || ! ctype_digit($compact)) {
            return false;
        }

        $first = Weights::mod11RemainderDigit(
            Weights::sum(substr($compact, 0, 12), self::WEIGHTS_FIRST),
        );

        if ($first !== (int) $compact[12]) {
            return false;
        }

        $second = Weights::mod11RemainderDigit(
            Weights::sum(substr($compact, 0, 13), self::WEIGHTS_SECOND),
        );

        return $second === (int) $compact[13];
    }

    public function isReserved(string $compact): bool
    {
        return preg_match('/^(\d)\1{13}$/', $compact) === 1;
    }
}
