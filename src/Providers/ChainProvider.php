<?php

declare(strict_types=1);

namespace Daycry\TaxId\Providers;

use Daycry\TaxId\Contracts\LookupProviderInterface;
use Daycry\TaxId\Contracts\RegistrationCheckInterface;
use Daycry\TaxId\DTO\EntityInfo;

/**
 * Tries several providers in order; the first hit wins.
 *
 * The canonical arrangement is local-then-remote — a `tax_entities` table first,
 * VIES only for what the table doesn't know — so the expensive, rate-limited
 * remote call is a fallback rather than the default path.
 *
 * For {@see RegistrationCheckInterface} the chain asks only the members that
 * implement it, and stops at the first definitive answer; a member returning
 * `null` ("don't know") does not veto the ones behind it.
 *
 * Framework-free: it only composes other providers.
 */
final class ChainProvider implements LookupProviderInterface, RegistrationCheckInterface
{
    /** @var LookupProviderInterface[] */
    private readonly array $providers;

    /**
     * @param LookupProviderInterface[] $providers In priority order.
     */
    public function __construct(array $providers)
    {
        $this->providers = array_values($providers);
    }

    public function findByTaxId(string $countryCode, string $taxId): ?EntityInfo
    {
        foreach ($this->providers as $provider) {
            $entity = $provider->findByTaxId($countryCode, $taxId);

            if ($entity !== null) {
                return $entity;
            }
        }

        return null;
    }

    public function isRegistered(string $countryCode, string $taxId): ?bool
    {
        foreach ($this->providers as $provider) {
            if (! $provider instanceof RegistrationCheckInterface) {
                continue;
            }

            $registered = $provider->isRegistered($countryCode, $taxId);

            if ($registered !== null) {
                return $registered;
            }
        }

        return null;
    }

    /**
     * The chained providers, in priority order. Exposed for introspection and
     * tests; the array is a copy, so mutating it changes nothing.
     *
     * @return LookupProviderInterface[]
     */
    public function providers(): array
    {
        return $this->providers;
    }
}
