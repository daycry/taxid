<?php

declare(strict_types=1);

namespace Tests\Resolver;

use Daycry\TaxId\Core\Parser;
use Daycry\TaxId\DTO\EntityInfo;
use Daycry\TaxId\Enums\EntityType;
use Daycry\TaxId\Providers\ChainProvider;
use Daycry\TaxId\Providers\NullProvider;
use Daycry\TaxId\Resolver\Resolver;
use Daycry\TaxId\TaxId;
use PHPUnit\Framework\TestCase;
use Tests\Support\FakeProvider;
use Tests\Support\SilentProvider;

/**
 * Resolution semantics — mostly about what must NOT happen.
 */
final class ResolverTest extends TestCase
{
    public function testAnInvalidValueNeverReachesTheProvider(): void
    {
        $provider = new FakeProvider(new EntityInfo('ES', 'B12345674', 'Acme SL'));
        $resolver = new Resolver(new Parser(), $provider);

        $result = $resolver->resolve('12345678A', 'ES');

        self::assertFalse($result->isValid());
        self::assertSame(0, $provider->findCalls, 'Garbage must not be sent to a remote tax authority.');
        self::assertSame(0, $provider->registeredCalls);
        self::assertNull($result->isRegistered());
        self::assertSame('check_digit_failed', $result->validation()->errorCode());
    }

    public function testTheProviderReceivesTheCompactFormAndTheResolvedCountry(): void
    {
        $provider = new FakeProvider();
        $resolver = new Resolver(new Parser(), $provider);

        // Prefixed and separator-laden on the way in.
        $resolver->resolve('ES B-12345674');

        self::assertSame(['ES|B12345674'], $provider->seen);
    }

    public function testAHitMakesTheTaxpayerRegistered(): void
    {
        $entity   = new EntityInfo('ES', 'B12345674', 'Acme SL', 'Calle Mayor 1', EntityType::Legal, 'test');
        $resolver = new Resolver(new Parser(), new FakeProvider($entity));

        $result = $resolver->resolve('ESB12345674');

        self::assertTrue($result->isValid());
        self::assertTrue($result->isRegistered());
        self::assertSame('Acme SL', $result->name());
        self::assertSame('test', $result->entity()?->resolvedBy);
    }

    /**
     * The rule the whole three-state design exists for.
     */
    public function testAProviderThatCannotTellReportsUnknownNotUnregistered(): void
    {
        $resolver = new Resolver(new Parser(), new SilentProvider());

        self::assertNull(
            $resolver->resolve('ESB12345674')->isRegistered(),
            'A plain provider returning null means "we do not know", never "not registered".',
        );
    }

    public function testOnlyAnExplicitRegistrationCheckCanProduceFalse(): void
    {
        $resolver = new Resolver(new Parser(), new FakeProvider(null, false));

        self::assertFalse($resolver->resolve('ESB12345674')->isRegistered());
    }

    public function testTheRegistrationCheckIsSkippedWhenAnEntityWasAlreadyFound(): void
    {
        $provider = new FakeProvider(new EntityInfo('ES', 'B12345674', 'Acme SL'), false);
        $resolver = new Resolver(new Parser(), $provider);

        self::assertTrue($resolver->resolve('ESB12345674')->isRegistered());
        self::assertSame(0, $provider->registeredCalls, 'Finding the entity already answers the question.');
    }

    public function testTheDefaultProviderIsInert(): void
    {
        $resolver = new Resolver();

        $result = $resolver->resolve('ESB12345674');

        self::assertInstanceOf(NullProvider::class, $resolver->provider());
        self::assertTrue($result->isValid());
        self::assertNull($result->isRegistered());
    }

    public function testAnAlreadyParsedValueSkipsReparsing(): void
    {
        $taxid    = new TaxId();
        $parsed   = $taxid->parse('B12345674', 'ES');
        $provider = new FakeProvider();
        $resolver = new Resolver(new Parser(), $provider);

        $result = $resolver->resolve($parsed);

        self::assertTrue($result->isValid());
        self::assertSame($parsed, $result->taxId());
        self::assertSame(['ES|B12345674'], $provider->seen);
    }

    // ---------------------------------------------------------------- chaining

    public function testTheChainStopsAtTheFirstHit(): void
    {
        $first  = new FakeProvider(new EntityInfo('ES', 'B12345674', 'From the database'));
        $second = new FakeProvider(new EntityInfo('ES', 'B12345674', 'From VIES'));

        $resolver = new Resolver(new Parser(), new ChainProvider([$first, $second]));
        $result   = $resolver->resolve('ESB12345674');

        self::assertSame('From the database', $result->name());
        self::assertSame(1, $first->findCalls);
        self::assertSame(0, $second->findCalls, 'The expensive remote provider must not be consulted after a local hit.');
    }

    public function testTheChainFallsThroughAMiss(): void
    {
        $first  = new FakeProvider();
        $second = new FakeProvider(new EntityInfo('ES', 'B12345674', 'From VIES'));

        $resolver = new Resolver(new Parser(), new ChainProvider([$first, $second]));

        self::assertSame('From VIES', $resolver->resolve('ESB12345674')->name());
        self::assertSame(1, $second->findCalls);
    }

    /**
     * A member that says "don't know" must not veto one that has an answer.
     */
    public function testTheChainKeepsAskingUntilSomeoneGivesADefinitiveAnswer(): void
    {
        $unknowing = new FakeProvider(null, null);
        $knowing   = new FakeProvider(null, false);

        $chain = new ChainProvider([$unknowing, $knowing]);

        self::assertFalse($chain->isRegistered('ES', 'B12345674'));
        self::assertSame(1, $unknowing->registeredCalls);
        self::assertSame(1, $knowing->registeredCalls);
    }

    public function testTheChainIgnoresMembersWithNoRegistrationCheck(): void
    {
        $chain = new ChainProvider([new SilentProvider(), new FakeProvider(null, true)]);

        self::assertTrue($chain->isRegistered('ES', 'B12345674'));
    }

    public function testAnEmptyChainAnswersNothing(): void
    {
        $chain = new ChainProvider([]);

        self::assertNull($chain->findByTaxId('ES', 'B12345674'));
        self::assertNull($chain->isRegistered('ES', 'B12345674'));
        self::assertSame([], $chain->providers());
    }

    public function testTheNullProviderAlwaysReturnsNothing(): void
    {
        self::assertNull((new NullProvider())->findByTaxId('ES', 'B12345674'));
    }
}
