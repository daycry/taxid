<?php

declare(strict_types=1);

namespace Daycry\TaxId\Core;

use Daycry\TaxId\Registry\CountryProfile;
use Daycry\TaxId\Registry\Registry;
use Daycry\TaxId\Registry\TaxIdScheme;

/**
 * Works out *which country and which scheme* a normalized value belongs to.
 *
 * Two jobs, both subtle enough to deserve their own class:
 *
 * **1. Finding the country when the caller didn't say.** Only a leading
 * two-letter prefix that the registry recognises — either as a VAT prefix
 * (`EL` for Greece, `XI` for Northern Ireland) or as a country code — counts.
 * Nothing is ever guessed from the digits themselves: a nine-digit number is a
 * plausible tax ID in dozens of jurisdictions, so guessing would be worse than
 * useless. No prefix, no country, and the caller gets `CountryRequired`.
 *
 * **2. Deciding whether a prefix is really a prefix.** The value is matched
 * **whole first**, and only if nothing matches is the prefix stripped and the
 * remainder retried. That ordering matters: an Italian codice fiscale for
 * someone whose surname starts "Ital…" legitimately begins with the letters
 * `IT`, and blindly stripping them would corrupt a perfectly valid ID. Austria
 * is the mirror case — `ATU12345678` only matches once `AT` is gone, because
 * the Austrian national number genuinely starts with `U`.
 *
 * @see docs/superpowers/specs/2026-07-27-daycry-taxid-v1-design.md §5
 */
final class SchemeDetector
{
    public function __construct(
        private readonly Registry $registry = new Registry(),
    ) {
    }

    /**
     * Resolve a normalized value to a country, a compact form and its matching
     * schemes.
     *
     * @param string      $normalized Output of {@see Normalizer::normalize()}.
     * @param string|null $country    ISO 3166-1 alpha-2, or `null` to infer.
     */
    public function detect(string $normalized, ?string $country = null): DetectionResult
    {
        $countryCode = $country !== null && $country !== ''
            ? strtoupper($country)
            : $this->inferCountry($normalized);

        if ($countryCode === null) {
            return new DetectionResult(null, null, $normalized, []);
        }

        if (! $this->registry->has($countryCode)) {
            return new DetectionResult($countryCode, null, $normalized, []);
        }

        $profile = $this->registry->get($countryCode);

        [$compact, $matches] = $this->matchAgainst($profile, $normalized);

        return new DetectionResult($profile->countryCode, $profile, $compact, $matches);
    }

    /**
     * The country a value's leading two letters point at, or `null`.
     *
     * Requires the prefix to be two letters **and** the rest to be non-empty,
     * so a bare `ES` is not read as a Spanish tax ID with an empty body.
     */
    public function inferCountry(string $normalized): ?string
    {
        if (strlen($normalized) < 3) {
            return null;
        }

        $prefix = substr($normalized, 0, 2);

        if (! ctype_alpha($prefix)) {
            return null;
        }

        $fromVat = $this->registry->countryForVatPrefix($prefix);

        if ($fromVat !== null) {
            return $fromVat;
        }

        return $this->registry->has($prefix) ? strtoupper($prefix) : null;
    }

    /**
     * Every scheme of `$profile` whose pattern the value satisfies, in registry
     * order.
     *
     * @return TaxIdScheme[]
     */
    public function schemesMatching(CountryProfile $profile, string $compact): array
    {
        $matches = [];

        foreach ($profile->schemes as $scheme) {
            if ($scheme->matches($compact)) {
                $matches[] = $scheme;
            }
        }

        return $matches;
    }

    /**
     * Match whole-value-first, then prefix-stripped.
     *
     * When nothing matches either way the *stripped* form is still returned
     * whenever the value carried a recognised prefix, so the resulting
     * length/structure violation talks about the national number the user
     * actually mistyped rather than about the prefix they got right.
     *
     * @return array{0: string, 1: TaxIdScheme[]}
     */
    private function matchAgainst(CountryProfile $profile, string $normalized): array
    {
        $matches = $this->schemesMatching($profile, $normalized);

        if ($matches !== []) {
            return [$normalized, $matches];
        }

        $fallback = $normalized;

        foreach ($this->prefixesFor($profile) as $prefix) {
            if (! str_starts_with($normalized, $prefix)) {
                continue;
            }

            $stripped = substr($normalized, strlen($prefix));

            if ($stripped === '') {
                continue;
            }

            $matches = $this->schemesMatching($profile, $stripped);

            if ($matches !== []) {
                return [$stripped, $matches];
            }

            if ($fallback === $normalized) {
                $fallback = $stripped;
            }
        }

        return [$fallback, []];
    }

    /**
     * The prefixes worth stripping for a country: its VAT prefix first (the one
     * actually printed on invoices), then its ISO code when that differs — so
     * `GR123456789` works even though Greece quotes VAT as `EL`.
     *
     * @return string[]
     */
    private function prefixesFor(CountryProfile $profile): array
    {
        $prefixes = [];

        if ($profile->vatPrefix !== null) {
            $prefixes[] = strtoupper($profile->vatPrefix);
        }

        if (! in_array($profile->countryCode, $prefixes, true)) {
            $prefixes[] = $profile->countryCode;
        }

        return $prefixes;
    }
}
