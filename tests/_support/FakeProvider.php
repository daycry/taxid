<?php

declare(strict_types=1);

namespace Tests\Support;

use Daycry\TaxId\Contracts\LookupProviderInterface;
use Daycry\TaxId\Contracts\RegistrationCheckInterface;
use Daycry\TaxId\DTO\EntityInfo;

/**
 * A provider whose answers a test dictates outright, and which counts how often
 * it was asked.
 *
 * The call counters are the point: several behaviours in this package are about
 * *not* calling a provider — an invalid tax ID must never reach one, a cache hit
 * must not consult the inner provider, and a chain must stop at the first
 * answer. Those are only testable by counting.
 */
final class FakeProvider implements LookupProviderInterface, RegistrationCheckInterface
{
    public int $findCalls = 0;

    public int $registeredCalls = 0;

    /** @var string[] */
    public array $seen = [];

    public function __construct(
        private readonly ?EntityInfo $entity = null,
        private readonly ?bool $registered = null,
    ) {
    }

    public function findByTaxId(string $countryCode, string $taxId): ?EntityInfo
    {
        $this->findCalls++;
        $this->seen[] = $countryCode . '|' . $taxId;

        return $this->entity;
    }

    public function isRegistered(string $countryCode, string $taxId): ?bool
    {
        $this->registeredCalls++;

        return $this->registered;
    }
}
