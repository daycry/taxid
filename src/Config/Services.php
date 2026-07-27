<?php

declare(strict_types=1);

namespace Daycry\TaxId\Config;

use CodeIgniter\Config\BaseService;
use CodeIgniter\Config\Factories;
use Daycry\TaxId\Config\TaxId as TaxIdConfig;
use Daycry\TaxId\Contracts\LookupProviderInterface;
use Daycry\TaxId\Models\TaxEntityModel;
use Daycry\TaxId\Providers\CachedProvider;
use Daycry\TaxId\Providers\ChainProvider;
use Daycry\TaxId\Providers\DatabaseProvider;
use Daycry\TaxId\Providers\NullProvider;
use Daycry\TaxId\Providers\ViesProvider;
use Daycry\TaxId\Registry\Registry;
use Daycry\TaxId\TaxId as TaxIdService;
use InvalidArgumentException;

/**
 * CI4 service factory for `daycry/taxid`.
 *
 * Auto-discovered through the `Daycry\TaxId\` Composer namespace (CI4's
 * `Config\Modules::$discoverInComposer` scans every registered namespace for a
 * `Config/Services.php`), so `service('taxid')` resolves with no wiring in the
 * consuming application.
 *
 * `Daycry\TaxId\TaxId` (the facade) and `Daycry\TaxId\Config\TaxId` (the config)
 * share a short name; both are imported under aliases so the two are never
 * ambiguous.
 *
 * @see \Daycry\TaxId\Config\TaxId
 */
class Services extends BaseService
{
    /**
     * Build the facade, wiring the provider described by
     * {@see TaxIdConfig::$provider} and, if a TTL is configured, the cache
     * decorator around it.
     *
     * With an unconfigured `Config\TaxId` this returns a fully functional facade
     * backed by {@see NullProvider} — no database, no network, no setup.
     */
    public static function taxid(bool $getShared = true): TaxIdService
    {
        if ($getShared) {
            /** @var TaxIdService $instance */
            $instance = static::getSharedInstance('taxid');

            return $instance;
        }

        $config   = self::config();
        $provider = self::buildProvider($config);

        // Opt-in caching. The check is `!== null`, not `> 0`: a TTL of 0 means
        // "never expires" to CI4's cache handlers, so it must still wrap.
        // Wrapping NullProvider is skipped — there is nothing to memoize.
        if ($config->cacheTtl !== null && ! $provider instanceof NullProvider) {
            $provider = new CachedProvider($provider, service('cache'), $config->cacheTtl);
        }

        return new TaxIdService(new Registry(), $provider);
    }

    /**
     * Resolve the effective {@see TaxIdConfig}, honouring a published
     * `app/Config/TaxId.php`.
     *
     * IMPORTANT — this resolves by the SHORT name `'TaxId'`, not the FQCN.
     * {@see Factories::locateClass()} only prefers the app's `Config\` namespace
     * for a non-namespaced alias; asking for `TaxIdConfig::class` always returns
     * this package's own copy and silently ignores an app's override. The FQCN
     * lookup below is a defensive fallback for any unexpected resolution.
     */
    public static function config(): TaxIdConfig
    {
        $config = Factories::get('config', 'TaxId');

        if ($config instanceof TaxIdConfig) {
            return $config;
        }

        $config = Factories::get('config', TaxIdConfig::class);

        return $config instanceof TaxIdConfig ? $config : new TaxIdConfig();
    }

    private static function buildProvider(TaxIdConfig $config): LookupProviderInterface
    {
        return match ($config->provider) {
            'null'     => new NullProvider(),
            'database' => new DatabaseProvider(new TaxEntityModel($config->table, $config->dbGroup)),
            'vies'     => self::vies($config),
            'chain'    => new ChainProvider([
                new DatabaseProvider(new TaxEntityModel($config->table, $config->dbGroup)),
                self::vies($config),
            ]),
            default => self::instantiateProvider($config->provider),
        };
    }

    private static function vies(TaxIdConfig $config): ViesProvider
    {
        return new ViesProvider(service('curlrequest'), $config->viesEndpoint, $config->viesTimeout);
    }

    /**
     * Instantiate a custom provider from its fully qualified class name — the
     * `Config\TaxId::$provider` value when it isn't one of the shortcuts.
     *
     * @throws InvalidArgumentException When the class is missing or doesn't
     *                                  implement the contract.
     */
    private static function instantiateProvider(string $fqcn): LookupProviderInterface
    {
        if (! class_exists($fqcn)) {
            throw new InvalidArgumentException(sprintf(
                'Config\TaxId::$provider "%s" is not a valid class.',
                $fqcn,
            ));
        }

        $provider = new $fqcn();

        if (! $provider instanceof LookupProviderInterface) {
            throw new InvalidArgumentException(sprintf(
                'Config\TaxId::$provider "%s" must implement %s.',
                $fqcn,
                LookupProviderInterface::class,
            ));
        }

        return $provider;
    }
}
