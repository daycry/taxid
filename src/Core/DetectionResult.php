<?php

declare(strict_types=1);

namespace Daycry\TaxId\Core;

use Daycry\TaxId\Registry\CountryProfile;
use Daycry\TaxId\Registry\TaxIdScheme;

/**
 * What {@see SchemeDetector} worked out about a value before any verdict is
 * passed on it: which country it belongs to, its compact form, and which of
 * that country's schemes its structure fits.
 *
 * Shared by {@see Validator} (which turns it into violations) and
 * {@see Parser} (which turns it into a {@see \Daycry\TaxId\DTO\ParsedTaxId}),
 * so the country/prefix logic lives in exactly one place.
 *
 * `$matches` may hold more than one scheme: several countries have schemes
 * whose patterns legitimately overlap. Registry order breaks the tie.
 */
final readonly class DetectionResult
{
    /**
     * @param string|null         $countryCode The country given or inferred; `null` when neither.
     * @param CountryProfile|null $profile     The registry entry, or `null` when the country is unknown.
     * @param string              $compact     The value after normalization and prefix stripping.
     * @param TaxIdScheme[]       $matches     Schemes whose pattern the compact form satisfies, in registry order.
     */
    public function __construct(
        public ?string $countryCode,
        public ?CountryProfile $profile,
        public string $compact,
        public array $matches,
    ) {
    }

    public function countryKnown(): bool
    {
        return $this->profile !== null;
    }

    public function hasAnyMatch(): bool
    {
        return $this->matches !== [];
    }

    /**
     * The scheme to report for this value.
     *
     * With no `$requested` scheme, the first match in registry order wins.
     * With one, only that exact scheme counts — a value that matches something
     * else is *not* silently accepted, which is what lets {@see Validator}
     * distinguish `SchemeMismatch` from `MalformedStructure`.
     */
    public function scheme(?string $requested = null): ?TaxIdScheme
    {
        if ($requested === null) {
            return $this->matches[0] ?? null;
        }

        $wanted = strtolower($requested);

        foreach ($this->matches as $match) {
            if ($match->code === $wanted) {
                return $match;
            }
        }

        return null;
    }
}
