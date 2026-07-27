<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Core\Checksum\Weights;

/**
 * Switzerland — UID (Unternehmens-Identifikationsnummer), written `CHE` plus
 * nine digits, and reused as the VAT number with an `MWST`/`TVA`/`IVA` suffix.
 *
 * The `CHE` is part of the number itself, not a country prefix, so the compact
 * form keeps it.
 *
 * Weighted mod-11 with the key `54327654` from the Federal Statistical Office.
 * A computed check of 10 is unusable and those numbers are never issued.
 */
final class ChUidValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['CH'];
    protected const array SCHEMES   = ['uid', 'mwst'];

    private const array WEIGHTS = [5, 4, 3, 2, 7, 6, 5, 4];

    public function verify(string $compact): bool
    {
        // Strip the VAT suffix: CHE-123.456.789 MWST / TVA / IVA.
        foreach (['MWST', 'TVA', 'IVA'] as $suffix) {
            if (str_ends_with($compact, $suffix)) {
                $compact = substr($compact, 0, -strlen($suffix));

                break;
            }
        }

        if (strlen($compact) !== 12 || ! str_starts_with($compact, 'CHE')) {
            return false;
        }

        $digits = substr($compact, 3);

        if (! ctype_digit($digits)) {
            return false;
        }

        $remainder = Weights::sum(substr($digits, 0, 8), self::WEIGHTS) % 11;

        $check = match ($remainder) {
            0       => 0,
            1       => null,   // would need a check digit of 10
            default => 11 - $remainder,
        };

        return $check !== null && $check === (int) $digits[8];
    }
}
