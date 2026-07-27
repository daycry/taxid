<?php

declare(strict_types=1);

namespace Daycry\TaxId\Core;

use Daycry\TaxId\Enums\TaxIdFormat;

/**
 * Renders a compact value in one of the three {@see TaxIdFormat} shapes.
 *
 * The print mask is a tiny template language: every `#` consumes one character
 * of the value, everything else is copied through as a literal. Brazil's CPF
 * mask `###.###.###-##` turns `12345678909` into `123.456.789-09`.
 *
 * Formatting is a display concern and never fails: if the mask doesn't fit the
 * value — the scheme has variable length, or the caller passed a value of the
 * wrong size — the compact form is returned unchanged rather than producing a
 * half-filled mask.
 */
final class Formatter
{
    public function format(string $compact, ?string $mask = null, TaxIdFormat $format = TaxIdFormat::Print): string
    {
        return match ($format) {
            TaxIdFormat::Compact    => $compact,
            TaxIdFormat::Print      => $this->applyMask($compact, $mask),
            TaxIdFormat::Anonymized => $this->anonymize($compact),
        };
    }

    /**
     * Fill a mask's `#` placeholders left to right.
     */
    private function applyMask(string $compact, ?string $mask): string
    {
        if ($mask === null || $mask === '') {
            return $compact;
        }

        if (substr_count($mask, '#') !== strlen($compact)) {
            return $compact;
        }

        $out    = '';
        $cursor = 0;
        $length = strlen($mask);

        for ($i = 0; $i < $length; $i++) {
            $out .= $mask[$i] === '#' ? $compact[$cursor++] : $mask[$i];
        }

        return $out;
    }

    /**
     * Keep the last two characters, mask everything before them.
     *
     * Enough to correlate a value across log lines without the log itself
     * becoming a list of taxpayer identifiers. Values of two characters or
     * fewer are masked completely — there would be nothing left to hide.
     */
    private function anonymize(string $compact): string
    {
        $length = strlen($compact);

        if ($length <= 2) {
            return str_repeat('*', $length);
        }

        return str_repeat('*', $length - 2) . substr($compact, -2);
    }
}
