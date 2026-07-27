<?php

declare(strict_types=1);

namespace Tests\Core;

use Daycry\TaxId\Core\Validator;
use Daycry\TaxId\Enums\ViolationCode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The check-ordering pipeline.
 *
 * These tests pin the *specific* violation each kind of bad input produces, not
 * merely that it was rejected. The codes are a public contract — applications
 * branch on them to decide whether to show "check the digits" or "we don't
 * support that country yet" — so a reordering that quietly turns a `BadLength`
 * into a `MalformedStructure` is a breaking change and should fail here.
 */
final class ValidatorTest extends TestCase
{
    private Validator $validator;

    protected function setUp(): void
    {
        $this->validator = new Validator();
    }

    /**
     * @return iterable<string, array{0: string, 1: string|null, 2: ViolationCode}>
     */
    public static function violationProvider(): iterable
    {
        yield 'empty input'            => ['', 'ES', ViolationCode::Blank];
        yield 'whitespace only'        => ['   ', 'ES', ViolationCode::Blank];
        yield 'separators only'        => ['-.-', 'ES', ViolationCode::Blank];
        yield 'too short'              => ['12', 'ES', ViolationCode::TooShort];
        yield 'too long'               => [str_repeat('1', 65), 'ES', ViolationCode::TooLong];
        yield 'illegal characters'     => ['1234567@Z', 'ES', ViolationCode::IllegalCharacters];
        yield 'no country, no prefix'  => ['12345678Z', null, ViolationCode::CountryRequired];
        yield 'unknown country'        => ['12345678Z', 'ZZ', ViolationCode::UnknownCountry];
        yield 'bad length for country' => ['123456789012345', 'ES', ViolationCode::BadLength];
        yield 'malformed structure'    => ['ZZZZZZZZZ', 'ES', ViolationCode::MalformedStructure];
        yield 'bad check digit'        => ['12345678A', 'ES', ViolationCode::CheckDigitFailed];
    }

    #[DataProvider('violationProvider')]
    public function testViolationCodes(string $value, ?string $country, ViolationCode $expected): void
    {
        $result = $this->validator->validate($value, $country);

        self::assertFalse($result->isValid());
        self::assertSame($expected, $result->firstViolation()?->code, sprintf(
            'Expected %s for "%s" (%s), got %s',
            $expected->value,
            $value,
            $country ?? 'no country',
            $result->errorCode() ?? 'none',
        ));
    }

    public function testTooLongIsCheckedBeforeAnythingElse(): void
    {
        // 200 characters of separators would normalize to empty; the fail-fast
        // guard must fire first so no work is done on absurd input.
        $result = $this->validator->validate(str_repeat('-', 200), 'ES');

        self::assertSame(ViolationCode::TooLong, $result->firstViolation()?->code);
    }

    public function testEveryViolationCarriesAStableMessageKey(): void
    {
        $violation = $this->validator->validate('', 'ES')->firstViolation();

        self::assertNotNull($violation);
        self::assertSame('taxid.violation.blank', $violation->messageKey);
        self::assertNotSame('', $violation->message);
    }

    public function testAValidResultCarriesNoViolations(): void
    {
        $result = $this->validator->validate('12345678Z', 'ES');

        self::assertTrue($result->isValid());
        self::assertSame([], $result->violations());
        self::assertNull($result->error());
        self::assertNull($result->errorCode());
    }

    public function testCountryIsCaseInsensitive(): void
    {
        self::assertTrue($this->validator->isValid('12345678Z', 'es'));
    }

    // ------------------------------------------------------------------ schemes

    public function testRequestingTheWrongSchemeReportsAMismatchNotAMalformedValue(): void
    {
        // A perfectly valid NIF, asked for as a CIF.
        $result = $this->validator->validate('12345678Z', 'ES', 'cif');

        self::assertFalse($result->isValid());
        self::assertSame(ViolationCode::SchemeMismatch, $result->firstViolation()?->code);
        self::assertStringContainsString('nif', (string) $result->error());
    }

    public function testRequestingASchemeTheCountryDoesNotHaveIsAMismatch(): void
    {
        $result = $this->validator->validate('12345678Z', 'ES', 'cpf');

        self::assertSame(ViolationCode::SchemeMismatch, $result->firstViolation()?->code);
    }

    public function testRequestingTheRightSchemeSucceeds(): void
    {
        self::assertTrue($this->validator->isValid('B12345674', 'ES', 'cif'));
        self::assertTrue($this->validator->isValid('X1234567L', 'ES', 'nie'));
    }

    /**
     * Germany's IdNr and Steuernummer both accept eleven digits. A value that
     * fails the IdNr checksum is still a plausible Steuernummer, so the
     * pipeline must fall through instead of stopping at the first candidate.
     */
    public function testOverlappingSchemesFallThroughToTheNextCandidate(): void
    {
        // Fails the IdNr check digit, but is a well-formed Steuernummer.
        self::assertTrue($this->validator->isValid('12345678901', 'DE'));
        self::assertSame(
            ViolationCode::CheckDigitFailed,
            $this->validator->validate('12345678901', 'DE', 'idnr')->firstViolation()?->code,
        );
    }

    // ------------------------------------------------------------------ reserved

    public function testReservedValuesAreReportedDistinctlyFromCheckDigitFailures(): void
    {
        // Eleven identical digits satisfy the CPF arithmetic but are never issued.
        $result = $this->validator->validate('11111111111', 'BR', 'cpf');

        self::assertFalse($result->isValid());
        self::assertSame(ViolationCode::ReservedValue, $result->firstViolation()?->code);
    }

    public function testReservedUsSsnRangesAreRejected(): void
    {
        foreach (['000123456', '666123456', '123006789', '123450000'] as $ssn) {
            self::assertSame(
                ViolationCode::ReservedValue,
                $this->validator->validate($ssn, 'US', 'ssn')->firstViolation()?->code,
                $ssn . ' should be reserved',
            );
        }
    }

    public function testSchemesWithoutACheckDigitAcceptAnyWellFormedValue(): void
    {
        // A UK UTR has no published checksum: shape is all we can honestly check.
        self::assertTrue($this->validator->isValid('1234567890', 'GB', 'utr'));
    }

    // ------------------------------------------------------------------ analyze

    public function testAnalyzeExposesTheWinningScheme(): void
    {
        $analysis = $this->validator->analyze('B12345674', 'ES');

        self::assertTrue($analysis->isValid());
        self::assertSame('cif', $analysis->scheme?->code);
        self::assertSame('ES', $analysis->detection->countryCode);
        self::assertSame('B12345674', $analysis->detection->compact);
    }

    public function testAnalyzeOnGarbageStillReturnsADetection(): void
    {
        $analysis = $this->validator->analyze('!!!!', 'ES');

        self::assertFalse($analysis->isValid());
        self::assertNull($analysis->scheme);
    }
}
