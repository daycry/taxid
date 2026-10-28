<?php

declare(strict_types=1);

namespace Tests\Core;

use Daycry\TaxId\Contracts\CheckDigitValidatorInterface;
use Daycry\TaxId\Contracts\RegistryLoaderInterface;
use Daycry\TaxId\Registry\Registry;
use Daycry\TaxId\TaxId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ValidationAssuranceTest extends TestCase
{
    /** @return iterable<string, array{string, string, string, bool}> */
    public static function assuranceValues(): iterable
    {
        yield 'Spain checksum' => ['ES', 'nif', '12345678Z', true];
        yield 'Andorra structure' => ['AD', 'nrt', 'A123456Z', false];
        yield 'US EIN ranges' => ['US', 'ein', '123456789', false];
        yield 'US SSN ranges' => ['US', 'ssn', '123456789', false];
        yield 'US ITIN ranges' => ['US', 'itin', '970512345', false];
        yield 'France numeric TVA' => ['FR', 'tva', '96552100554', true];
        yield 'France alphabetic TVA key' => ['FR', 'tva', 'AB552100554', false];
        yield 'Monaco alphabetic TVA key' => ['MC', 'tva', 'AB552100554', false];
        yield 'GB numeric VAT' => ['GB', 'vat', '123456782', true];
        yield 'GB government VAT' => ['GB', 'vat', 'GD001', false];
        yield 'GB health VAT' => ['GB', 'vat', 'HA500', false];
        yield 'CZ old birth number' => ['CZ', 'dic', '530101123', false];
        yield 'CZ company' => ['CZ', 'ico', '00006947', true];
        yield 'SK old birth number' => ['SK', 'rodne_cislo', '530101123', false];
        yield 'SK modern birth number' => ['SK', 'rodne_cislo', '9001011239', true];
        yield 'Mexico generic RFC' => ['MX', 'rfc', 'XAXX010101000', false];
        yield 'Mexico foreign generic RFC' => ['MX', 'rfc', 'XEXX010101000', false];
        yield 'Mexico regular RFC' => ['MX', 'rfc', 'AAA010101AA1', true];
        yield 'JP checksum' => ['JP', 'mynumber', '123456789018', true];
        yield 'TW checksum' => ['TW', 'ban', '12345675', true];
        yield 'KZ checksum' => ['KZ', 'bin', '123456789013', true];
    }

    #[DataProvider('assuranceValues')]
    public function testReportsWhatWasActuallyVerified(string $country, string $scheme, string $value, bool $checksum): void
    {
        $result = (new TaxId())->validate($value, $country, $scheme);

        self::assertTrue($result->isValid());
        self::assertSame($checksum, $result->checksumVerified);
        self::assertSame($scheme, $result->scheme);
    }

    public function testUSValidatorsAreNotAdvertisedAsChecksums(): void
    {
        foreach ((new Registry())->get('US')->schemes as $scheme) {
            self::assertFalse($scheme->hasCheckDigit());
            self::assertNotNull($scheme->checkDigitClass, 'Keep the range verifier connected.');
        }
    }

    public function testExplicitSchemeCannotFallBackToAWeakerScheme(): void
    {
        $taxid = new TaxId();
        $generic = $taxid->validate('12345678904', 'DE');
        $specific = $taxid->validate('12345678904', 'DE', 'idnr');

        self::assertTrue($generic->isValid());
        self::assertSame('steuernummer', $generic->scheme);
        self::assertFalse($generic->checksumVerified);
        self::assertFalse($specific->isValid());
        self::assertSame('check_digit_failed', $specific->errorCode());
        self::assertNull($specific->checksumVerified);
        self::assertNull($specific->scheme);
    }

    public function testAnExistingCustomValidatorNeedsNoNewInterface(): void
    {
        $registry = new Registry(new class () implements RegistryLoaderInterface {
            public function load(): array
            {
                return ['ZZ' => ['name' => 'Test', 'schemes' => [[
                    'code' => 'tin', 'name' => 'TIN', 'pattern' => '/^\d{4}$/', 'lengths' => [4],
                    'check_digit' => LegacyChecksumValidator::class,
                ]]]];
            }
        });
        $result = (new TaxId($registry))->validate('1234', 'ZZ', 'tin');

        self::assertTrue($result->isValid());
        self::assertTrue($result->checksumVerified);
    }

    /** @return iterable<string, array{string, string, string|null}> */
    public static function invalidValues(): iterable
    {
        yield 'empty' => ['', 'ES', null];
        yield 'wrong checksum' => ['12345678A', 'ES', 'nif'];
        yield 'unknown country' => ['12345678', 'ZZ', null];
        yield 'reserved SSN' => ['666123456', 'US', 'ssn'];
        yield 'scheme mismatch' => ['12345678Z', 'ES', 'cif'];
    }

    #[DataProvider('invalidValues')]
    public function testInvalidResultsClaimNoSuccessfulChecksumOrScheme(string $value, string $country, ?string $scheme): void
    {
        $result = (new TaxId())->validate($value, $country, $scheme);

        self::assertFalse($result->isValid());
        self::assertNull($result->checksumVerified);
        self::assertNull($result->scheme);
    }
}

final class LegacyChecksumValidator implements CheckDigitValidatorInterface
{
    public function supports(string $countryCode, string $scheme): bool
    {
        return $countryCode === 'ZZ' && $scheme === 'tin';
    }

    public function verify(string $compact): bool
    {
        return $compact === '1234';
    }
}
