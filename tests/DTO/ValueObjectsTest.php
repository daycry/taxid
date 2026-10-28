<?php

declare(strict_types=1);

namespace Tests\DTO;

use Daycry\TaxId\DTO\EntityInfo;
use Daycry\TaxId\DTO\LookupResult;
use Daycry\TaxId\DTO\ParsedTaxId;
use Daycry\TaxId\DTO\ValidationResult;
use Daycry\TaxId\DTO\Violation;
use Daycry\TaxId\Enums\EntityType;
use Daycry\TaxId\Enums\TaxIdFormat;
use Daycry\TaxId\Enums\ViolationCode;
use Daycry\TaxId\Exceptions\InvalidTaxIdException;
use PHPUnit\Framework\TestCase;

final class ValueObjectsTest extends TestCase
{
    public function testValidationResultAccessors(): void
    {
        $violation = new Violation(ViolationCode::CheckDigitFailed, 'taxid.violation.check_digit_failed', 'Nope.');
        $result    = new ValidationResult(false, [$violation]);

        self::assertFalse($result->isValid());
        self::assertSame([$violation], $result->violations());
        self::assertSame($violation, $result->firstViolation());
        self::assertSame('Nope.', $result->error());
        self::assertSame('check_digit_failed', $result->errorCode());
    }

    public function testAValidResultHasNoErrorToReport(): void
    {
        $result = new ValidationResult(true, []);

        self::assertTrue($result->isValid());
        self::assertNull($result->checksumVerified);
        self::assertNull($result->scheme);
        self::assertNull($result->firstViolation());
        self::assertNull($result->error());
        self::assertNull($result->errorCode());
    }

    public function testParsedTaxIdFormatsThroughItsOwnMask(): void
    {
        $parsed = new ParsedTaxId(
            countryCode: 'BR',
            scheme: 'cpf',
            schemeName: 'CPF',
            entityType: EntityType::Natural,
            vat: false,
            compact: '11144477735',
            input: '111.444.777-35',
            checkDigit: '35',
            mask: '###.###.###-##',
        );

        self::assertSame('111.444.777-35', $parsed->format());
        self::assertSame('11144477735', $parsed->format(TaxIdFormat::Compact));
        self::assertSame('*********35', $parsed->format(TaxIdFormat::Anonymized));
        self::assertSame('11144477735', (string) $parsed);
    }

    public function testVatNumberRequiresBothTheFlagAndAPrefix(): void
    {
        $base = static fn (bool $vat, ?string $prefix): ParsedTaxId => new ParsedTaxId(
            countryCode: 'ES',
            scheme: 'cif',
            schemeName: 'CIF',
            entityType: EntityType::Legal,
            vat: $vat,
            compact: 'B12345674',
            input: 'B12345674',
            vatPrefix: $prefix,
        );

        self::assertSame('ESB12345674', $base(true, 'ES')->vatNumber());
        self::assertNull($base(false, 'ES')->vatNumber(), 'Not a VAT scheme.');
        self::assertNull($base(true, null)->vatNumber(), 'Country has no VAT prefix.');
    }

    public function testEntityInfoProvenanceIsRewrittenWithoutMutating(): void
    {
        $original = new EntityInfo('ES', 'B12345674', 'Acme SL', 'Calle Mayor 1', EntityType::Legal, 'vies');
        $rewrapped = $original->withResolvedBy('cache:vies');

        self::assertSame('vies', $original->resolvedBy, 'The original must be untouched.');
        self::assertSame('cache:vies', $rewrapped->resolvedBy);
        self::assertSame('Acme SL', $rewrapped->name);
        self::assertSame(EntityType::Legal, $rewrapped->entityType);
    }

    public function testLookupResultExposesTheEntityFieldsItWraps(): void
    {
        $parsed = new ParsedTaxId(
            countryCode: 'ES',
            scheme: 'cif',
            schemeName: 'CIF',
            entityType: EntityType::Legal,
            vat: true,
            compact: 'B12345674',
            input: 'B12345674',
            vatPrefix: 'ES',
        );

        $entity = new EntityInfo('ES', 'B12345674', 'Acme SL', 'Calle Mayor 1', EntityType::Legal, 'vies');
        $result = new LookupResult($parsed, true, true, $entity, new ValidationResult(true, []));

        self::assertTrue($result->isValid());
        self::assertTrue($result->isRegistered());
        self::assertSame('Acme SL', $result->name());
        self::assertSame('Calle Mayor 1', $result->address());
        self::assertSame($entity, $result->entity());
        self::assertSame($parsed, $result->taxId());
    }

    public function testLookupResultWithoutAnEntityReportsNullsRatherThanFailing(): void
    {
        $result = new LookupResult(null, false, null, null, new ValidationResult(false, []));

        self::assertNull($result->name());
        self::assertNull($result->address());
        self::assertNull($result->entity());
        self::assertNull($result->taxId());
        self::assertNull($result->isRegistered());
    }

    public function testEntityTypeCompatibility(): void
    {
        self::assertTrue(EntityType::Both->accepts(EntityType::Natural));
        self::assertTrue(EntityType::Natural->accepts(EntityType::Both));
        self::assertTrue(EntityType::Legal->accepts(EntityType::Legal));
        self::assertFalse(EntityType::Legal->accepts(EntityType::Natural));
        self::assertFalse(EntityType::Natural->accepts(EntityType::Legal));
    }

    public function testViolationCodesAreStableStrings(): void
    {
        self::assertSame('check_digit_failed', ViolationCode::CheckDigitFailed->value);
        self::assertSame('reserved_value', ViolationCode::ReservedValue->value);
        self::assertSame(ViolationCode::Blank, ViolationCode::from('blank'));
    }

    public function testInvalidTaxIdExceptionCarriesTheResult(): void
    {
        $result    = new ValidationResult(false, [
            new Violation(ViolationCode::BadLength, 'taxid.violation.bad_length', 'Wrong length.'),
        ]);
        $exception = new InvalidTaxIdException($result);

        self::assertSame($result, $exception->result());
        self::assertSame('Wrong length.', $exception->getMessage(), 'Defaults to the first violation message.');
    }

    public function testInvalidTaxIdExceptionFallsBackWhenThereIsNoViolation(): void
    {
        $exception = new InvalidTaxIdException(new ValidationResult(false, []));

        self::assertSame('Invalid tax ID', $exception->getMessage());
    }

    public function testInvalidTaxIdExceptionKeepsAnExplicitMessage(): void
    {
        $exception = new InvalidTaxIdException(new ValidationResult(false, []), 'Custom.');

        self::assertSame('Custom.', $exception->getMessage());
    }
}
