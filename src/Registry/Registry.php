<?php

declare(strict_types=1);

namespace Daycry\TaxId\Registry;

use Daycry\TaxId\Contracts\CheckDigitValidatorInterface;
use Daycry\TaxId\Contracts\RegistryLoaderInterface;
use Daycry\TaxId\Enums\EntityType;
use OutOfBoundsException;

/**
 * Central lookup for per-country tax-ID rules.
 *
 * Hydrates the raw data (from an injected {@see RegistryLoaderInterface},
 * defaulting to {@see PhpRegistryLoader}) into {@see CountryProfile} /
 * {@see TaxIdScheme} objects, caching the hydrated map so repeated lookups
 * don't re-hydrate. Hydration is lazy: constructing a `Registry` reads nothing.
 *
 * @see docs/superpowers/specs/2026-07-27-daycry-taxid-v1-design.md §4.3
 */
final class Registry
{
    /**
     * Registry version marker.
     *
     * The rules are independently authored from publicly documented national
     * formats — they are not copied out of, nor derived from, any third-party
     * dataset. See docs/licensing.md.
     */
    public const string VERSION = '2026-07 (independently authored from public national formats)';

    /**
     * Hydrated country map, lazily built and cached on first access.
     *
     * @var array<string, CountryProfile>|null
     */
    private ?array $cache = null;

    /**
     * VAT-prefix => country-code index, built alongside the hydrated map.
     *
     * @var array<string, string>|null
     */
    private ?array $vatPrefixes = null;

    public function __construct(
        private readonly RegistryLoaderInterface $loader = new PhpRegistryLoader(),
    ) {
    }

    /**
     * Whether the registry knows this country. Case-insensitive.
     */
    public function has(string $cc): bool
    {
        return array_key_exists(strtoupper($cc), $this->profiles());
    }

    /**
     * @throws OutOfBoundsException When the country is not registered.
     */
    public function get(string $cc): CountryProfile
    {
        $code     = strtoupper($cc);
        $profiles = $this->profiles();

        if (! array_key_exists($code, $profiles)) {
            throw new OutOfBoundsException(sprintf('No tax ID rules registered for country code "%s".', $code));
        }

        return $profiles[$code];
    }

    /**
     * Shorthand for `get($cc)->scheme($code)`.
     *
     * @throws OutOfBoundsException When the country or the scheme is unknown.
     */
    public function scheme(string $cc, string $code): TaxIdScheme
    {
        return $this->get($cc)->scheme($code);
    }

    /**
     * The full hydrated registry, keyed by ISO 3166-1 alpha-2.
     *
     * @return array<string, CountryProfile>
     */
    public function all(): array
    {
        return $this->profiles();
    }

    /**
     * Every registered country code, sorted.
     *
     * @return string[]
     */
    public function countries(): array
    {
        $codes = array_keys($this->profiles());
        sort($codes);

        return $codes;
    }

    /**
     * The country a VAT prefix belongs to, or `null` when it is not a VAT prefix.
     *
     * Almost always the identity mapping, but not for Greece (`EL` => `GR`) —
     * which is why this lookup exists instead of `has()`.
     */
    public function countryForVatPrefix(string $prefix): ?string
    {
        if ($this->vatPrefixes === null) {
            $this->profiles();
        }

        /** @var array<string, string> $index */
        $index = $this->vatPrefixes ?? [];

        return $index[strtoupper($prefix)] ?? null;
    }

    /**
     * Total number of schemes across every country — the honest measure of the
     * registry's size (a country contributes 1..N).
     */
    public function schemeCount(): int
    {
        $count = 0;

        foreach ($this->profiles() as $profile) {
            $count += count($profile->schemes);
        }

        return $count;
    }

    /**
     * @return array<string, CountryProfile>
     */
    private function profiles(): array
    {
        if ($this->cache === null) {
            $this->cache = $this->hydrate($this->loader->load());
        }

        return $this->cache;
    }

    /**
     * Turn the raw array data into {@see CountryProfile} objects, and build the
     * VAT-prefix index in the same pass.
     *
     * @param array<string, array<string, mixed>> $raw
     *
     * @return array<string, CountryProfile>
     */
    private function hydrate(array $raw): array
    {
        $profiles  = [];
        $prefixMap = [];

        foreach ($raw as $countryCode => $data) {
            $code = strtoupper((string) $countryCode);

            /** @var string|null $vatPrefix */
            $vatPrefix = $data['vat_prefix'] ?? null;

            /** @var array<int, array<string, mixed>> $rawSchemes */
            $rawSchemes = $data['schemes'] ?? [];

            $schemes = [];

            foreach ($rawSchemes as $rawScheme) {
                $scheme                       = $this->hydrateScheme($code, $rawScheme);
                $schemes[$scheme->code]       = $scheme;
            }

            $profiles[$code] = new CountryProfile(
                countryCode: $code,
                name: (string) ($data['name'] ?? $code),
                vatPrefix: $vatPrefix,
                schemes: $schemes,
            );

            if ($vatPrefix !== null) {
                $prefixMap[strtoupper($vatPrefix)] = $code;
            }
        }

        $this->vatPrefixes = $prefixMap;

        return $profiles;
    }

    /**
     * @param array<string, mixed> $raw
     */
    private function hydrateScheme(string $countryCode, array $raw): TaxIdScheme
    {
        /** @var int[] $lengths */
        $lengths = (array) ($raw['lengths'] ?? []);

        /** @var class-string<CheckDigitValidatorInterface>|null $checkDigitClass */
        $checkDigitClass = $raw['check_digit'] ?? null;

        /** @var array{0: int, 1: int}|null $offset */
        $offset = $raw['check_digit_offset'] ?? null;

        /** @var string[] $examples */
        $examples = (array) ($raw['examples'] ?? []);

        return new TaxIdScheme(
            countryCode: $countryCode,
            code: strtolower((string) $raw['code']),
            name: (string) $raw['name'],
            entityType: EntityType::from((string) ($raw['entity'] ?? 'both')),
            vat: (bool) ($raw['vat'] ?? false),
            pattern: (string) $raw['pattern'],
            lengths: array_values(array_map(intval(...), $lengths)),
            mask: isset($raw['mask']) ? (string) $raw['mask'] : null,
            checkDigitClass: $checkDigitClass,
            checkDigitOffset: $offset,
            examples: array_values(array_map(strval(...), $examples)),
            notes: isset($raw['notes']) ? (string) $raw['notes'] : null,
            checksum: (bool) ($raw['checksum'] ?? true),
        );
    }
}
