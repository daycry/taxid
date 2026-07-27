<?php

declare(strict_types=1);

namespace Tests\Registry;

use Daycry\TaxId\Contracts\RegistryLoaderInterface;
use Daycry\TaxId\Core\CheckDigitValidatorFactory;
use Daycry\TaxId\Enums\EntityType;
use Daycry\TaxId\Exceptions\TaxIdException;
use Daycry\TaxId\National\EsDniValidator;
use Daycry\TaxId\Registry\PhpRegistryLoader;
use Daycry\TaxId\Registry\Registry;
use OutOfBoundsException;
use PHPUnit\Framework\TestCase;

final class RegistryTest extends TestCase
{
    public function testLookupsAreCaseInsensitive(): void
    {
        $registry = new Registry();

        self::assertTrue($registry->has('es'));
        self::assertSame('ES', $registry->get('es')->countryCode);
        self::assertSame('cif', $registry->scheme('es', 'CIF')->code);
    }

    public function testUnknownCountryThrows(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionMessageMatches('/ZZ/');

        (new Registry())->get('ZZ');
    }

    public function testUnknownSchemeThrowsAndListsWhatIsAvailable(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $this->expectExceptionMessageMatches('/nif/');

        (new Registry())->scheme('ES', 'nope');
    }

    public function testVatPrefixIndexMapsGreeceToEl(): void
    {
        $registry = new Registry();

        self::assertSame('GR', $registry->countryForVatPrefix('EL'));
        self::assertSame('GR', $registry->countryForVatPrefix('el'));
        self::assertNull($registry->countryForVatPrefix('GR'), 'GR is the ISO code, not the VAT prefix.');
        self::assertNull($registry->countryForVatPrefix('QQ'));
    }

    public function testCountriesAreSorted(): void
    {
        $countries = (new Registry())->countries();
        $sorted    = $countries;
        sort($sorted);

        self::assertSame($sorted, $countries);
    }

    public function testSchemeCountMatchesTheSumOfPerCountrySchemes(): void
    {
        $registry = new Registry();
        $manual   = 0;

        foreach ($registry->all() as $profile) {
            $manual += count($profile->schemes);
        }

        self::assertSame($manual, $registry->schemeCount());
    }

    /**
     * Hydration is lazy and memoized: two calls must return the very same
     * objects, or every lookup would re-parse ninety countries.
     */
    public function testHydrationHappensOnceAndOnDemand(): void
    {
        $loader   = new CountingLoader();
        $registry = new Registry($loader);

        self::assertSame(0, $loader->calls, 'Constructing a Registry must not read anything.');

        $first = $registry->get('ES');
        $registry->get('ES');
        $registry->all();

        self::assertSame(1, $loader->calls);
        self::assertSame($first, $registry->get('ES'));
    }

    public function testACustomLoaderReplacesTheBundledData(): void
    {
        $registry = new Registry(new class () implements RegistryLoaderInterface {
            public function load(): array
            {
                return [
                    'ZZ' => [
                        'name'       => 'Zedland',
                        'vat_prefix' => 'ZZ',
                        'schemes'    => [
                            [
                                'code'     => 'tin',
                                'name'     => 'Zedland TIN',
                                'entity'   => 'both',
                                'vat'      => true,
                                'pattern'  => '/^\d{6}$/',
                                'lengths'  => [6],
                                'examples' => ['123456'],
                            ],
                        ],
                    ],
                ];
            }
        });

        self::assertSame(['ZZ'], $registry->countries());
        self::assertFalse($registry->has('ES'), 'The custom loader replaces the bundled data outright.');
        self::assertSame(EntityType::Both, $registry->scheme('ZZ', 'tin')->entityType);
        self::assertSame('ZZ', $registry->countryForVatPrefix('ZZ'));
    }

    public function testPhpLoaderResolvesItsPathIndependentlyOfTheWorkingDirectory(): void
    {
        $before = getcwd();

        self::assertIsString($before);
        chdir(sys_get_temp_dir());

        try {
            self::assertNotSame([], (new PhpRegistryLoader())->load());
        } finally {
            chdir($before);
        }
    }

    public function testCountryProfileHelpers(): void
    {
        $profile = (new Registry())->get('ES');

        self::assertTrue($profile->hasScheme('nif'));
        self::assertTrue($profile->hasScheme('NIF'));
        self::assertFalse($profile->hasScheme('cpf'));
        self::assertSame([9], $profile->lengths());
        self::assertCount(3, $profile->vatSchemes());
    }

    public function testSchemeExtractsItsCheckDigit(): void
    {
        $registry = new Registry();

        self::assertSame('Z', $registry->scheme('ES', 'nif')->extractCheckDigit('12345678Z'));
        self::assertSame('35', $registry->scheme('BR', 'cpf')->extractCheckDigit('11144477735'));
        self::assertNull(
            $registry->scheme('GB', 'utr')->extractCheckDigit('1234567890'),
            'A scheme with no declared offset extracts nothing.',
        );
    }

    // ------------------------------------------------- check-digit factory

    public function testValidatorFactoryReusesOneInstancePerClass(): void
    {
        $factory = new CheckDigitValidatorFactory();

        self::assertSame($factory->make(EsDniValidator::class), $factory->make(EsDniValidator::class));
    }

    public function testValidatorFactoryRejectsAMissingClass(): void
    {
        $this->expectException(TaxIdException::class);
        $this->expectExceptionMessageMatches('/does not exist/');

        /** @phpstan-ignore argument.type (deliberately bad input) */
        (new CheckDigitValidatorFactory())->make('Daycry\TaxId\National\NoSuchValidator');
    }

    public function testValidatorFactoryRejectsAClassThatDoesNotImplementTheContract(): void
    {
        $this->expectException(TaxIdException::class);
        $this->expectExceptionMessageMatches('/must implement/');

        /** @phpstan-ignore argument.type (deliberately bad input) */
        (new CheckDigitValidatorFactory())->make(Registry::class);
    }
}

/**
 * Counts how many times the registry actually reads its source.
 */
final class CountingLoader implements RegistryLoaderInterface
{
    public int $calls = 0;

    public function load(): array
    {
        $this->calls++;

        return (new PhpRegistryLoader())->load();
    }
}
