<?php

declare(strict_types=1);

namespace Daycry\TaxId\Providers;

use CodeIgniter\Cache\CacheInterface;
use Daycry\TaxId\Contracts\LookupProviderInterface;
use Daycry\TaxId\Contracts\RegistrationCheckInterface;
use Daycry\TaxId\DTO\EntityInfo;

/**
 * Caches successful lookups behind CI4's cache, so a repeated `resolve()` for
 * the same tax ID doesn't hit the database — or, far more importantly, VIES —
 * again.
 *
 * Two rules keep the cache honest:
 *
 * - **Only hits are stored.** A `null` from the inner provider is returned
 *   uncached, so a VIES outage or a not-yet-imported row can be retried
 *   immediately instead of being remembered as a miss for an hour.
 * - **`false` registrations are cached, `null` ones are not.** "Not registered"
 *   is a real answer worth remembering; "couldn't tell" is not.
 *
 * A `$ttl` of `0` means *never expires* — that is CI4's own convention for
 * `CacheInterface::save()`, not this package's. The "caching off" case is
 * handled a layer up: {@see \Daycry\TaxId\Config\Services::taxid()} simply does
 * not construct this decorator when
 * {@see \Daycry\TaxId\Config\TaxId::$cacheTtl} is `null`.
 */
final class CachedProvider implements LookupProviderInterface, RegistrationCheckInterface
{
    public function __construct(
        private readonly LookupProviderInterface $inner,
        private readonly CacheInterface $cache,
        private readonly int $ttl = 3600,
        private readonly string $prefix = 'taxid_entity_',
        private readonly string $registeredPrefix = 'taxid_registered_',
    ) {
    }

    public function findByTaxId(string $countryCode, string $taxId): ?EntityInfo
    {
        $key    = $this->key($this->prefix, $countryCode, $taxId);
        $cached = $this->cache->get($key);

        if ($cached instanceof EntityInfo) {
            return $cached->withResolvedBy('cache:' . ($cached->resolvedBy ?? 'unknown'));
        }

        $entity = $this->inner->findByTaxId($countryCode, $taxId);

        if ($entity !== null) {
            $this->cache->save($key, $entity, $this->ttl);
        }

        return $entity;
    }

    public function isRegistered(string $countryCode, string $taxId): ?bool
    {
        if (! $this->inner instanceof RegistrationCheckInterface) {
            return null;
        }

        $key    = $this->key($this->registeredPrefix, $countryCode, $taxId);
        $cached = $this->cache->get($key);

        if (is_bool($cached)) {
            return $cached;
        }

        $registered = $this->inner->isRegistered($countryCode, $taxId);

        // Only a definitive answer is worth remembering.
        if ($registered !== null) {
            $this->cache->save($key, $registered, $this->ttl);
        }

        return $registered;
    }

    /**
     * Build a cache key that no CI4 handler will reject.
     *
     * Compact tax IDs are `[A-Z0-9&]` by the time they reach a provider, but the
     * `&` alone is enough to upset a handler's reserved-character list, so the
     * key is scrubbed defensively.
     */
    private function key(string $prefix, string $countryCode, string $taxId): string
    {
        $raw = $prefix . strtoupper($countryCode) . '_' . strtoupper($taxId);

        return preg_replace('/[^A-Za-z0-9_]/', '_', $raw) ?? $raw;
    }
}
