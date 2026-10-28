<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Contracts\ChecksumAwareValidatorInterface;

/**
 * Mexico — RFC (Registro Federal de Contribuyentes): twelve characters for a
 * company, thirteen for an individual.
 *
 * The last character is the check digit of the *homoclave*. The SAT algorithm
 * left-pads a company's twelve-character RFC to thirteen with a space, maps
 * every character through the fixed alphabet `0-9 A-N & O-Z ␠ Ñ`, weights the
 * first twelve by 13 down to 2, and closes with `11 - (sum mod 11)`, writing 11
 * as `0` and 10 as `A`.
 *
 * The two **generic** RFCs the SAT publishes for unidentified domestic
 * (`XAXX010101000`) and foreign (`XEXX010101000`) counterparties do not follow
 * the algorithm and are accepted explicitly — they appear on a great many real
 * invoices.
 */
final class MxRfcValidator extends AbstractNationalValidator implements ChecksumAwareValidatorInterface
{
    protected const array COUNTRIES = ['MX'];
    protected const array SCHEMES   = ['rfc'];

    private const string ALPHABET = '0123456789ABCDEFGHIJKLMN&OPQRSTUVWXYZ ';

    /** SAT-published RFCs for unidentified counterparties. */
    private const array GENERIC = ['XAXX010101000', 'XEXX010101000'];

    public function hasChecksum(string $compact): bool
    {
        return ! in_array($compact, self::GENERIC, true);
    }

    public function verify(string $compact): bool
    {
        $length = strlen($compact);

        if ($length !== 12 && $length !== 13) {
            return false;
        }

        if (in_array($compact, self::GENERIC, true)) {
            return true;
        }

        $padded = str_pad($compact, 13, ' ', STR_PAD_LEFT);
        $sum    = 0;

        for ($i = 0; $i < 12; $i++) {
            $value = strpos(self::ALPHABET, $padded[$i]);

            if ($value === false) {
                return false;
            }

            $sum += $value * (13 - $i);
        }

        $check = 11 - $sum % 11;

        $expected = match ($check) {
            11      => '0',
            10      => 'A',
            default => (string) $check,
        };

        return $expected === $compact[$length - 1];
    }
}
