<?php

declare(strict_types=1);

namespace Tests\Providers;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\Mock\MockCache;
use Daycry\TaxId\DTO\EntityInfo;
use Daycry\TaxId\Providers\CachedProvider;
use Tests\Support\FakeProvider;
use Tests\Support\SilentProvider;

/**
 * The caching decorator. Its job is to reduce load on VIES without ever
 * remembering the wrong thing.
 */
final class CachedProviderTest extends CIUnitTestCase
{
    private MockCache $cache;

    protected function setUp(): void
    {
        parent::setUp();
        $this->cache = new MockCache();
    }

    public function testASecondLookupComesFromTheCache(): void
    {
        $inner  = new FakeProvider(new EntityInfo('ES', 'B12345674', 'Acme SL', null, null, 'vies'));
        $cached = new CachedProvider($inner, $this->cache);

        $cached->findByTaxId('ES', 'B12345674');
        $second = $cached->findByTaxId('ES', 'B12345674');

        self::assertSame(1, $inner->findCalls);
        self::assertSame('Acme SL', $second?->name);
    }

    /**
     * A cached answer must say so. Otherwise an operator debugging a stale name
     * has no way to tell a live VIES response from a day-old one.
     */
    public function testACacheHitIsMarkedInTheProvenance(): void
    {
        $inner  = new FakeProvider(new EntityInfo('ES', 'B12345674', 'Acme SL', null, null, 'vies'));
        $cached = new CachedProvider($inner, $this->cache);

        $live = $cached->findByTaxId('ES', 'B12345674');
        $hit  = $cached->findByTaxId('ES', 'B12345674');

        self::assertNotNull($live);
        self::assertNotNull($hit);
        self::assertSame('vies', $live->resolvedBy);
        self::assertSame('cache:vies', $hit->resolvedBy);
    }

    /**
     * Caching a miss would turn a transient VIES outage into an hour of false
     * negatives, and would hide a row imported thirty seconds ago.
     */
    public function testMissesAreNeverCached(): void
    {
        $inner  = new FakeProvider();
        $cached = new CachedProvider($inner, $this->cache);

        $cached->findByTaxId('ES', 'B12345674');
        $cached->findByTaxId('ES', 'B12345674');

        self::assertSame(2, $inner->findCalls, 'A miss must be retried, not remembered.');
    }

    public function testDefiniteRegistrationAnswersAreCached(): void
    {
        $inner  = new FakeProvider(null, false);
        $cached = new CachedProvider($inner, $this->cache);

        self::assertFalse($cached->isRegistered('ES', 'B99999999'));
        self::assertFalse($cached->isRegistered('ES', 'B99999999'));
        self::assertSame(1, $inner->registeredCalls);
    }

    public function testUnknownRegistrationAnswersAreNotCached(): void
    {
        $inner  = new FakeProvider(null, null);
        $cached = new CachedProvider($inner, $this->cache);

        $cached->isRegistered('ES', 'B12345674');
        $cached->isRegistered('ES', 'B12345674');

        self::assertSame(2, $inner->registeredCalls, '"Do not know" is not an answer worth remembering.');
    }

    public function testWrappingAProviderWithNoRegistrationCheckStaysUnknown(): void
    {
        $cached = new CachedProvider(new SilentProvider(), $this->cache);

        self::assertNull($cached->isRegistered('ES', 'B12345674'));
    }

    /**
     * Entity and registration answers use separate key namespaces, so a `false`
     * registration can never be mistaken for a cached entity or vice versa.
     */
    public function testEntityAndRegistrationCachesDoNotCollide(): void
    {
        $inner  = new FakeProvider(new EntityInfo('ES', 'B12345674', 'Acme SL'), true);
        $cached = new CachedProvider($inner, $this->cache);

        $cached->findByTaxId('ES', 'B12345674');
        $cached->isRegistered('ES', 'B12345674');

        self::assertNotNull($cached->findByTaxId('ES', 'B12345674'));
        self::assertTrue($cached->isRegistered('ES', 'B12345674'));
    }

    /**
     * A Mexican RFC can contain an ampersand, which several CI4 cache handlers
     * reject outright in a key.
     */
    public function testKeysAreScrubbedOfCharactersCacheHandlersReject(): void
    {
        $inner  = new FakeProvider(new EntityInfo('MX', 'A&M010101AA1', 'Acme SA'));
        $cached = new CachedProvider($inner, $this->cache);

        self::assertNotNull($cached->findByTaxId('MX', 'A&M010101AA1'));
        self::assertNotNull($cached->findByTaxId('MX', 'A&M010101AA1'));
        self::assertSame(1, $inner->findCalls);
    }

    public function testCountryAndIdAreBothPartOfTheKey(): void
    {
        $inner  = new FakeProvider(new EntityInfo('ES', 'B12345674', 'Acme SL'));
        $cached = new CachedProvider($inner, $this->cache);

        $cached->findByTaxId('ES', 'B12345674');
        $cached->findByTaxId('PT', 'B12345674');

        self::assertSame(2, $inner->findCalls, 'Same digits, different country: different cache entry.');
    }
}
