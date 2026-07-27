<?php

declare(strict_types=1);

namespace Tests\Support;

use Daycry\TaxId\Contracts\LookupProviderInterface;
use Daycry\TaxId\DTO\EntityInfo;

/**
 * A provider that implements ONLY {@see LookupProviderInterface} — no
 * registration check.
 *
 * Exists to pin the contract's most important rule: a provider that cannot tell
 * "not found" from "couldn't look" must never cause `registered = false`.
 */
final class SilentProvider implements LookupProviderInterface
{
    public int $calls = 0;

    public function __construct(private readonly ?EntityInfo $entity = null)
    {
    }

    public function findByTaxId(string $countryCode, string $taxId): ?EntityInfo
    {
        $this->calls++;

        return $this->entity;
    }
}
