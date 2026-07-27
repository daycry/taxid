<?php

declare(strict_types=1);

namespace Tests\Config;

use CodeIgniter\Config\Factories;
use CodeIgniter\Test\CIUnitTestCase;
use Daycry\TaxId\Config\Services as TaxIdServices;
use Daycry\TaxId\Config\TaxId as TaxIdConfig;
use Daycry\TaxId\Providers\CachedProvider;
use Daycry\TaxId\Providers\ChainProvider;
use Daycry\TaxId\Providers\DatabaseProvider;
use Daycry\TaxId\Providers\NullProvider;
use Daycry\TaxId\Providers\ViesProvider;
use Daycry\TaxId\TaxId as TaxIdService;
use InvalidArgumentException;

/**
 * `service('taxid')` through CI4's real discovery path, plus the
 * `Config\TaxId::$provider` → provider-class wiring.
 */
final class ServicesTest extends CIUnitTestCase
{
    protected function tearDown(): void
    {
        parent::tearDown();

        // These tests mutate the shared config singleton; put the documented
        // defaults back so later tests don't inherit a database or a network
        // provider.
        $config                 = config(TaxIdConfig::class);
        $config->provider       = 'null';
        $config->cacheTtl       = null;
        $config->defaultCountry = null;
        $config->defaultFormat  = 'print';
        $config->table          = 'tax_entities';
        $config->dbGroup        = null;

        $this->resetServices();
    }

    public function testTheServiceIsDiscoverableWithoutAnyWiring(): void
    {
        $taxid = service('taxid');

        self::assertInstanceOf(TaxIdService::class, $taxid);
        self::assertTrue($taxid->isValid('12345678Z', 'ES'));
    }

    public function testTheServiceIsSharedByDefault(): void
    {
        self::assertSame(service('taxid'), service('taxid'));
        self::assertNotSame(TaxIdServices::taxid(false), TaxIdServices::taxid(false));
    }

    public function testTheDefaultProviderIsInert(): void
    {
        self::assertInstanceOf(NullProvider::class, TaxIdServices::taxid(false)->resolver()->provider());
    }

    public function testTheDatabaseProviderIsWiredFromConfig(): void
    {
        config(TaxIdConfig::class)->provider = 'database';

        self::assertInstanceOf(DatabaseProvider::class, TaxIdServices::taxid(false)->resolver()->provider());
    }

    public function testTheViesProviderIsWiredFromConfig(): void
    {
        config(TaxIdConfig::class)->provider = 'vies';

        self::assertInstanceOf(ViesProvider::class, TaxIdServices::taxid(false)->resolver()->provider());
    }

    /**
     * The production-shaped arrangement: local table first, VIES only for what
     * it doesn't know.
     */
    public function testTheChainPutsTheDatabaseBeforeVies(): void
    {
        config(TaxIdConfig::class)->provider = 'chain';

        $provider = TaxIdServices::taxid(false)->resolver()->provider();

        self::assertInstanceOf(ChainProvider::class, $provider);

        $members = $provider->providers();

        self::assertInstanceOf(DatabaseProvider::class, $members[0]);
        self::assertInstanceOf(ViesProvider::class, $members[1]);
    }

    public function testACustomProviderClassIsAccepted(): void
    {
        config(TaxIdConfig::class)->provider = NullProvider::class;

        self::assertInstanceOf(NullProvider::class, TaxIdServices::taxid(false)->resolver()->provider());
    }

    public function testAMissingProviderClassIsRejectedLoudly(): void
    {
        config(TaxIdConfig::class)->provider = 'Nope\\NoSuchProvider';

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/not a valid class/');

        TaxIdServices::taxid(false);
    }

    public function testAProviderThatDoesNotImplementTheContractIsRejected(): void
    {
        config(TaxIdConfig::class)->provider = TaxIdConfig::class;

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/must implement/');

        TaxIdServices::taxid(false);
    }

    // -------------------------------------------------------------- caching

    public function testCachingIsOffByDefault(): void
    {
        config(TaxIdConfig::class)->provider = 'database';

        self::assertNotInstanceOf(CachedProvider::class, TaxIdServices::taxid(false)->resolver()->provider());
    }

    public function testATtlWrapsTheProvider(): void
    {
        $config           = config(TaxIdConfig::class);
        $config->provider = 'database';
        $config->cacheTtl = 3600;

        self::assertInstanceOf(CachedProvider::class, TaxIdServices::taxid(false)->resolver()->provider());
    }

    /**
     * Zero means "never expires" to CI4's cache handlers, so it must still
     * wrap — `null` is the off switch, not `0`.
     */
    public function testATtlOfZeroStillEnablesCaching(): void
    {
        $config           = config(TaxIdConfig::class);
        $config->provider = 'database';
        $config->cacheTtl = 0;

        self::assertInstanceOf(CachedProvider::class, TaxIdServices::taxid(false)->resolver()->provider());
    }

    /**
     * Wrapping the null provider would add a cache round-trip per call to
     * memoize a permanent "nothing".
     */
    public function testTheNullProviderIsNeverWrapped(): void
    {
        $config           = config(TaxIdConfig::class);
        $config->provider = 'null';
        $config->cacheTtl = 3600;

        self::assertInstanceOf(NullProvider::class, TaxIdServices::taxid(false)->resolver()->provider());
    }

    // --------------------------------------------------------------- config

    public function testConfigResolvesToThePackageDefaults(): void
    {
        $config = TaxIdServices::config();

        self::assertInstanceOf(TaxIdConfig::class, $config);
        self::assertSame('null', $config->provider);
        self::assertSame('print', $config->defaultFormat);
        self::assertNull($config->defaultCountry);
        self::assertNull($config->cacheTtl);
        self::assertNull($config->dbGroup);
        self::assertSame('tax_entities', $config->table);
        self::assertSame(10, $config->viesTimeout);
        self::assertStringContainsString('ec.europa.eu', $config->viesEndpoint);
    }

    /**
     * The config must resolve by SHORT name, so an app's published
     * `app/Config/TaxId.php` wins over the package's own copy.
     */
    public function testConfigIsResolvedByShortNameSoAnAppOverrideWouldWin(): void
    {
        self::assertSame(Factories::get('config', 'TaxId'), TaxIdServices::config());
    }
}
