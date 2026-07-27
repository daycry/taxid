<?php

declare(strict_types=1);

namespace Daycry\TaxId\DTO;

use Daycry\TaxId\Enums\EntityType;

/**
 * What a {@see \Daycry\TaxId\Contracts\LookupProviderInterface} knows about the
 * taxpayer behind a tax ID.
 *
 * Every field but the country and the ID itself is nullable: a provider that
 * can only confirm existence (VIES for a trader who opted out of name
 * disclosure, for instance) still returns a meaningful `EntityInfo`.
 *
 * `$resolvedBy` carries provenance — `'vies'`, `'database'`, `'cache:vies'` —
 * so an application can tell a live answer from a cached or local one.
 */
final readonly class EntityInfo
{
    public function __construct(
        public string $countryCode,
        public string $taxId,
        public ?string $name = null,
        public ?string $address = null,
        public ?EntityType $entityType = null,
        public ?string $resolvedBy = null,
    ) {
    }

    /**
     * A copy of this info tagged with a different provenance.
     *
     * Used by the decorating providers ({@see \Daycry\TaxId\Providers\CachedProvider})
     * to mark where an answer actually came from without mutating a readonly DTO.
     */
    public function withResolvedBy(string $resolvedBy): self
    {
        return new self(
            $this->countryCode,
            $this->taxId,
            $this->name,
            $this->address,
            $this->entityType,
            $resolvedBy,
        );
    }
}
