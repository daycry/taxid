<?php

declare(strict_types=1);

namespace Tests;

use Daycry\TaxId\Core\Parser;
use Daycry\TaxId\Core\Validator;
use Daycry\TaxId\Enums\EntityType;
use Daycry\TaxId\Enums\TaxIdFormat;
use Daycry\TaxId\Exceptions\InvalidTaxIdException;
use Daycry\TaxId\Registry\Registry;
use Daycry\TaxId\Resolver\Resolver;
use Daycry\TaxId\TaxId;
use OutOfBoundsException;
use PHPUnit\Framework\TestCase;

/**
 * The public API, exercised the way a consumer would — including the promise
 * that all of it works with `new TaxId()` and no framework in sight.
 */
final class TaxIdFacadeTest extends TestCase
{
    private TaxId $taxid;

    protected function setUp(): void
    {
        $this->taxid = new TaxId();
    }

    public function testItIsUsableWithNoConfigurationAtAll(): void
    {
        self::assertTrue((new TaxId())->isValid('12345678Z', 'ES'));
    }

    public function testValidateWithAnExplicitCountry(): void
    {
        self::assertTrue($this->taxid->isValid('12345678Z', 'ES'));
        self::assertFalse($this->taxid->isValid('12345678A', 'ES'));
    }

    public function testCountryIsInferredFromAVatPrefix(): void
    {
        self::assertTrue($this->taxid->isValid('ESB12345674'));
        self::assertTrue($this->taxid->isValid('DE136695976'));
        self::assertTrue($this->taxid->isValid('EL094259216'));
    }

    public function testSeparatorsAreIrrelevant(): void
    {
        self::assertTrue($this->taxid->isValid('12.345.678-Z', 'ES'));
        self::assertTrue($this->taxid->isValid('111.444.777-35', 'BR'));
        self::assertTrue($this->taxid->isValid('11.222.333/0001-81', 'BR'));
    }

    public function testNormalizeStripsSeparatorsAndPrefix(): void
    {
        self::assertSame('B12345678', $this->taxid->normalize('ES B-12345678'));
        self::assertSame('11144477735', $this->taxid->normalize('111.444.777-35', 'BR'));
    }

    public function testParseDescribesTheValue(): void
    {
        $parsed = $this->taxid->parse('111.444.777-35', 'BR');

        self::assertSame('BR', $parsed->countryCode);
        self::assertSame('cpf', $parsed->scheme);
        self::assertSame(EntityType::Natural, $parsed->entityType);
        self::assertSame('11144477735', $parsed->compact);
        self::assertSame('111.444.777-35', $parsed->input);
        self::assertSame('35', $parsed->checkDigit);
        self::assertFalse($parsed->vat);
        self::assertSame('11144477735', (string) $parsed);
    }

    public function testParseThrowsWithTheFullResultAttached(): void
    {
        try {
            $this->taxid->parse('12345678A', 'ES');
            self::fail('Expected InvalidTaxIdException');
        } catch (InvalidTaxIdException $e) {
            self::assertSame('check_digit_failed', $e->result()->errorCode());
            self::assertNotSame('', $e->getMessage());
        }
    }

    public function testTryParseReturnsNullInsteadOfThrowing(): void
    {
        self::assertNull($this->taxid->tryParse('12345678A', 'ES'));
        self::assertNull($this->taxid->tryParse('', 'ES'));
    }

    public function testFormatting(): void
    {
        $value = '11144477735';

        self::assertSame('111.444.777-35', $this->taxid->format($value, TaxIdFormat::Print, 'BR'));
        self::assertSame('11144477735', $this->taxid->format($value, TaxIdFormat::Compact, 'BR'));
        self::assertSame('*********35', $this->taxid->format($value, TaxIdFormat::Anonymized, 'BR'));
    }

    public function testFormattingAnUnparseableValueDoesNotThrow(): void
    {
        self::assertSame('NOTATAXID', $this->taxid->format('not-a-tax-id', TaxIdFormat::Print, 'ES'));
    }

    public function testVatNumberIsRebuiltWithTheCountryPrefix(): void
    {
        self::assertSame('ESB12345674', $this->taxid->parse('B12345674', 'ES')->vatNumber());
        self::assertSame('EL094259216', $this->taxid->parse('094259216', 'GR')->vatNumber(), 'Greece quotes EL, not GR.');
    }

    public function testANonVatSchemeHasNoVatNumber(): void
    {
        self::assertNull($this->taxid->parse('11144477735', 'BR')->vatNumber(), 'A CPF is not a VAT number.');
    }

    public function testACountryWithoutAVatPrefixHasNoVatNumber(): void
    {
        self::assertNull($this->taxid->parse('51824753556', 'AU')->vatNumber());
    }

    // ------------------------------------------------------------- resolution

    public function testResolveWithoutAProviderReportsUnknownNotUnregistered(): void
    {
        $result = $this->taxid->resolve('ESB12345674');

        self::assertTrue($result->isValid());
        self::assertNull($result->isRegistered(), 'No provider means unknown — never false.');
        self::assertNull($result->entity());
        self::assertSame('cif', $result->taxId()?->scheme);
    }

    public function testResolvingAnInvalidValueNeverReachesAProvider(): void
    {
        $result = $this->taxid->resolve('12345678A', 'ES');

        self::assertFalse($result->isValid());
        self::assertNull($result->taxId());
        self::assertNull($result->isRegistered());
        self::assertSame('check_digit_failed', $result->validation()->errorCode());
    }

    // ---------------------------------------------------------- introspection

    public function testRegistryIntrospection(): void
    {
        self::assertTrue($this->taxid->supports('ES'));
        self::assertTrue($this->taxid->supports('es'));
        self::assertFalse($this->taxid->supports('ZZ'));

        self::assertContains('ES', $this->taxid->countries());
        self::assertGreaterThanOrEqual(90, count($this->taxid->countries()));

        $schemes = $this->taxid->schemes('ES');
        self::assertSame(['nif', 'nie', 'cif'], array_map(static fn ($s): string => $s->code, $schemes));

        self::assertSame('cif', $this->taxid->scheme('ES', 'cif')->code);
        self::assertSame('Spain', $this->taxid->country('ES')->name);
    }

    public function testAskingForAnUnknownCountryThrows(): void
    {
        $this->expectException(OutOfBoundsException::class);
        $this->taxid->country('ZZ');
    }

    public function testSubServiceAccessors(): void
    {
        self::assertInstanceOf(Validator::class, $this->taxid->validator());
        self::assertInstanceOf(Parser::class, $this->taxid->parser());
        self::assertInstanceOf(Resolver::class, $this->taxid->resolver());
        self::assertInstanceOf(Registry::class, $this->taxid->registry());
    }

    /**
     * A ParsedTaxId must be accepted wherever a string is, and must not need
     * the country repeated — it already knows.
     */
    public function testAParsedValueCanBeFedBackIn(): void
    {
        $parsed = $this->taxid->parse('12345678Z', 'ES');

        self::assertTrue($this->taxid->isValid($parsed));
        self::assertTrue($this->taxid->validate($parsed)->isValid());
        self::assertSame('12345678-Z', $this->taxid->format($parsed));
    }
}
