<?php

declare(strict_types=1);

namespace Tests\Helpers;

use CodeIgniter\Test\CIUnitTestCase;
use Daycry\TaxId\Config\TaxId as TaxIdConfig;
use Daycry\TaxId\Enums\TaxIdFormat;

/**
 * The procedural helper.
 *
 * Its one behavioural difference from the facade is the country/format fallback
 * to `Config\TaxId`, so that is what most of these tests are about.
 */
final class TaxIdHelperTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        helper('taxid');
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        $config                 = config(TaxIdConfig::class);
        $config->defaultCountry = null;
        $config->defaultFormat  = 'print';

        $this->resetServices();
    }

    public function testTheHelperLoadsThroughCodeIgniterAlone(): void
    {
        self::assertTrue(function_exists('taxid_validate'));
        self::assertTrue(function_exists('vat_is_registered'));
    }

    public function testValidation(): void
    {
        self::assertTrue(taxid_is_valid('12345678Z', 'ES'));
        self::assertFalse(taxid_is_valid('12345678A', 'ES'));
        self::assertTrue(taxid_validate('12345678Z', 'ES')->isValid());
        self::assertSame('check_digit_failed', taxid_validate('12345678A', 'ES')->errorCode());
    }

    public function testSchemeCanBeDemanded(): void
    {
        self::assertTrue(taxid_is_valid('B12345674', 'ES', 'cif'));
        self::assertFalse(taxid_is_valid('B12345674', 'ES', 'nif'));
    }

    public function testParsingNeverThrows(): void
    {
        self::assertSame('cif', taxid_parse('B12345674', 'ES')?->scheme);
        self::assertNull(taxid_parse('nonsense', 'ES'));
    }

    public function testNormalizeAndFormat(): void
    {
        self::assertSame('11144477735', taxid_normalize('111.444.777-35', 'BR'));
        self::assertSame('111.444.777-35', taxid_format('11144477735', null, 'BR'));
        self::assertSame('*********35', taxid_format('11144477735', TaxIdFormat::Anonymized, 'BR'));
    }

    public function testTheDefaultFormatComesFromConfig(): void
    {
        config(TaxIdConfig::class)->defaultFormat = 'anonymized';

        self::assertSame('*********35', taxid_format('11144477735', null, 'BR'));

        config(TaxIdConfig::class)->defaultFormat = 'compact';

        self::assertSame('11144477735', taxid_format('11144477735', null, 'BR'));
    }

    /**
     * The reason the helper exists: a single-country application configures
     * its country once instead of repeating it at every call site.
     */
    public function testTheDefaultCountryComesFromConfig(): void
    {
        self::assertFalse(taxid_is_valid('12345678Z'), 'Without a default there is nothing to infer from.');

        config(TaxIdConfig::class)->defaultCountry = 'ES';
        $this->resetServices();

        self::assertTrue(taxid_is_valid('12345678Z'));
        self::assertSame('nif', taxid_scheme('12345678Z'));
        self::assertSame('ES', taxid_country('12345678Z'));
    }

    public function testAnExplicitCountryBeatsTheConfiguredDefault(): void
    {
        config(TaxIdConfig::class)->defaultCountry = 'ES';
        $this->resetServices();

        self::assertTrue(taxid_is_valid('11144477735', 'BR'));
        self::assertFalse(taxid_is_valid('11144477735'), 'A Brazilian CPF is not a Spanish tax ID.');
    }

    public function testCountryAndSchemeAccessorsReturnNullForRubbish(): void
    {
        self::assertNull(taxid_country('nonsense', 'ES'));
        self::assertNull(taxid_scheme('nonsense', 'ES'));
    }

    public function testVatNumberHelper(): void
    {
        self::assertSame('ESB12345674', vat_number('B12345674', 'ES'));
        self::assertNull(vat_number('11144477735', 'BR'), 'A CPF is not a VAT number.');
        self::assertNull(vat_number('nonsense', 'ES'));
    }

    /**
     * With no provider configured the answer is "unknown". An application that
     * treats that as `false` would reject every customer.
     */
    public function testRegistrationChecksAreUnknownWithoutAProvider(): void
    {
        self::assertNull(vat_is_registered('B12345674', 'ES'));
        self::assertNull(taxid_name('B12345674', 'ES'));
    }

    public function testResolveReturnsAFullResultEvenWithoutAProvider(): void
    {
        $result = taxid_resolve('B12345674', 'ES');

        self::assertTrue($result->isValid());
        self::assertNull($result->isRegistered());
        self::assertSame('B12345674', $result->taxId()?->compact);
    }
}
