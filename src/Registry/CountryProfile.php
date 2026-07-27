<?php

declare(strict_types=1);

namespace Daycry\TaxId\Registry;

use OutOfBoundsException;

/**
 * Everything the registry knows about one jurisdiction: its name, its VAT
 * prefix (if any) and its ordered list of tax-ID schemes.
 *
 * @see docs/superpowers/specs/2026-07-27-daycry-taxid-v1-design.md §4.3
 */
final readonly class CountryProfile
{
    /**
     * @param string                     $countryCode ISO 3166-1 alpha-2, uppercase.
     * @param string                     $name        English country name.
     * @param string|null                $vatPrefix   The prefix used when quoting the VAT number
     *                                                internationally. Usually the country code, but
     *                                                notably `EL` for Greece and `XI` for Northern
     *                                                Ireland; `null` where the country has no VAT.
     * @param array<string, TaxIdScheme> $schemes     Keyed by scheme code, in registry order.
     */
    public function __construct(
        public string $countryCode,
        public string $name,
        public ?string $vatPrefix,
        public array $schemes,
    ) {
    }

    public function hasScheme(string $code): bool
    {
        return array_key_exists(strtolower($code), $this->schemes);
    }

    /**
     * @throws OutOfBoundsException When the country has no such scheme.
     */
    public function scheme(string $code): TaxIdScheme
    {
        $key = strtolower($code);

        if (! array_key_exists($key, $this->schemes)) {
            throw new OutOfBoundsException(sprintf(
                'Country "%s" has no tax ID scheme "%s". Available: %s.',
                $this->countryCode,
                $code,
                implode(', ', array_keys($this->schemes)),
            ));
        }

        return $this->schemes[$key];
    }

    /**
     * The schemes usable as a VAT number.
     *
     * @return TaxIdScheme[]
     */
    public function vatSchemes(): array
    {
        return array_values(array_filter(
            $this->schemes,
            static fn (TaxIdScheme $s): bool => $s->vat,
        ));
    }

    /**
     * Every compact length any of this country's schemes accepts.
     *
     * Used by {@see \Daycry\TaxId\Core\Validator} to tell a length problem
     * (BadLength) from a shape problem (MalformedStructure).
     *
     * @return int[]
     */
    public function lengths(): array
    {
        $lengths = [];

        foreach ($this->schemes as $scheme) {
            foreach ($scheme->lengths as $length) {
                $lengths[$length] = true;
            }
        }

        $unique = array_keys($lengths);
        sort($unique);

        return $unique;
    }
}
