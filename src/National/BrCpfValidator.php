<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Contracts\ReservedValueDetectorInterface;
use Daycry\TaxId\Core\Checksum\Weights;

/**
 * Brazil — CPF (Cadastro de Pessoas Físicas), eleven digits.
 *
 * Two mod-11 check digits, each over everything before it: weights count down
 * from 10 for the first and from 11 for the second, and a remainder below 2
 * gives a check digit of `0`.
 *
 * The eleven-identical-digits values (`111.111.111-11` and friends) satisfy
 * both checks arithmetically but are never issued, so they are reported as
 * {@see \Daycry\TaxId\Enums\ViolationCode::ReservedValue} rather than as a
 * check-digit failure — a real distinction for anyone debugging why a test
 * fixture was rejected.
 */
final class BrCpfValidator extends AbstractNationalValidator implements ReservedValueDetectorInterface
{
    protected const array COUNTRIES = ['BR'];
    protected const array SCHEMES   = ['cpf'];

    private const array WEIGHTS_FIRST  = [10, 9, 8, 7, 6, 5, 4, 3, 2];
    private const array WEIGHTS_SECOND = [11, 10, 9, 8, 7, 6, 5, 4, 3, 2];

    public function verify(string $compact): bool
    {
        if (strlen($compact) !== 11 || ! ctype_digit($compact)) {
            return false;
        }

        $first = Weights::mod11RemainderDigit(
            Weights::sum(substr($compact, 0, 9), self::WEIGHTS_FIRST),
        );

        if ($first !== (int) $compact[9]) {
            return false;
        }

        $second = Weights::mod11RemainderDigit(
            Weights::sum(substr($compact, 0, 10), self::WEIGHTS_SECOND),
        );

        return $second === (int) $compact[10];
    }

    public function isReserved(string $compact): bool
    {
        return preg_match('/^(\d)\1{10}$/', $compact) === 1;
    }
}
