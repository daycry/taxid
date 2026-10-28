<?php

declare(strict_types=1);

namespace Tests\National;

use Daycry\TaxId\Core\CheckDigitValidatorFactory;
use Daycry\TaxId\Registry\Registry;
use Daycry\TaxId\TaxId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EuropeanCoverageTest extends TestCase
{
    /** @return iterable<string, array{string, string, string}> */
    public static function newVerifiers(): iterable
    {
        yield 'CH AHV' => ['CH', 'ahv', '7560000000019'];
        yield 'MD IDNO' => ['MD', 'idno', '1000000000018'];
        yield 'MD IDNP' => ['MD', 'idnp', '2000000000015'];
        yield 'MK EMBG' => ['MK', 'embg', '0101990410012'];
        yield 'MK EDB' => ['MK', 'edb', '4000000000013'];
    }

    #[DataProvider('newVerifiers')]
    public function testNationalVerifiersRejectUnnormalizedInput(string $country, string $scheme, string $valid): void
    {
        $class = (new Registry())->scheme($country, $scheme)->checkDigitClass;
        self::assertNotNull($class);
        $verifier = (new CheckDigitValidatorFactory())->make($class);

        self::assertTrue($verifier->supports(strtolower($country), strtoupper($scheme)));
        self::assertTrue($verifier->verify($valid));

        foreach ([$valid . "\n", substr($valid, 1), $valid . '0', 'not-a-number', ''] as $invalid) {
            self::assertFalse($verifier->verify($invalid));
        }
    }

    public function testSwissVatLengthsIncludeEveryLanguageSuffix(): void
    {
        $scheme = (new Registry())->scheme('CH', 'mwst');

        self::assertSame([15, 16], $scheme->lengths);

        foreach (['MWST', 'TVA', 'IVA'] as $suffix) {
            self::assertTrue((new TaxId())->isValid('CHE116281839' . $suffix, 'CH', 'mwst'));
        }
    }

    public function testSwissPersonalAhvUsesEan13AndPreservesFormatting(): void
    {
        $taxid = new TaxId();
        // Synthetic random component, calculated independently using EAN-13.
        $result = $taxid->validate('756.0000.0000.19', 'CH', 'ahv');

        self::assertTrue($result->isValid());
        self::assertTrue($result->checksumVerified);
        self::assertSame('ahv', $result->scheme);
        self::assertFalse($taxid->isValid('7560000000018', 'CH', 'ahv'));
        self::assertFalse($taxid->isValid('7550000000010', 'CH', 'ahv'));
        self::assertFalse($taxid->isValid('7560000000019', 'CH', 'uid'));
        $parsed = $taxid->parse('7560000000019', 'CH');
        self::assertSame('ahv', $parsed->scheme);
        self::assertSame('756.0000.0000.19', $parsed->format());
        self::assertNull($parsed->vatNumber());
    }

    /** @return iterable<string, array{string, bool}> */
    public static function icelandicDates(): iterable
    {
        yield 'invalid century' => ['1203894597', false];
        yield 'valid nineteenth century' => ['1203894598', true];
        yield 'valid twentieth century' => ['1203894599', true];
        yield 'leap year 2000' => ['2902002020', true];
        yield '1900 is not a leap year' => ['2902002029', false];
        yield 'leap year 2004' => ['2902042080', true];
        yield 'February has no day 31' => ['3102992009', false];
        yield 'company day offset' => ['5203894589', true];
    }

    #[DataProvider('icelandicDates')]
    public function testIcelandicCalendarAndCentury(string $value, bool $valid): void
    {
        self::assertSame($valid, (new TaxId())->isValid($value, 'IS', 'kennitala'));
    }

    public function testNorwegianCalendarRejectsImpossibleDatesEvenWithCorrectChecksums(): void
    {
        $taxid = new TaxId();

        self::assertFalse($taxid->isValid('31029012302', 'NO', 'fodselsnummer'));
        self::assertFalse($taxid->isValid('71029012487', 'NO', 'fodselsnummer'));
        self::assertTrue($taxid->isValid('29020050088', 'NO', 'fodselsnummer'));
        self::assertTrue($taxid->isValid('29020412354', 'NO', 'fodselsnummer'));
        self::assertTrue($taxid->isValid('41019012393', 'NO', 'fodselsnummer'));
    }

    public function testMoldovanOrganizationChecksumRejectsAMutatedDigit(): void
    {
        $taxid = new TaxId();

        self::assertFalse($taxid->isValid('1000000000019', 'MD', 'idno'));
        self::assertTrue($taxid->isValid('1000000000018', 'MD', 'idno'));
        self::assertFalse($taxid->isValid('0000000000000', 'MD'));
        self::assertTrue($taxid->validate('1000000000018', 'MD')->checksumVerified);
    }

    public function testMoldovanPersonalIdsAreDistinctFromOrganizationIds(): void
    {
        $taxid = new TaxId();

        foreach (['2000000000015', '0900000000018'] as $value) {
            $result = $taxid->validate($value, 'MD', 'idnp');
            self::assertTrue($result->isValid());
            self::assertTrue($result->checksumVerified);
            self::assertSame('idnp', $taxid->parse($value, 'MD')->scheme);
            self::assertFalse($taxid->isValid($value, 'MD', 'idno'));
        }

        self::assertFalse($taxid->isValid('2000000000016', 'MD', 'idnp'));
        self::assertFalse($taxid->isValid('0900000000019', 'MD', 'idnp'));
        self::assertFalse($taxid->isValid('1000000000018', 'MD', 'idnp'));
        self::assertNull($taxid->parse('2000000000015', 'MD')->vatNumber());
    }

    public function testMacedonianPersonalIdsCheckDateRegionAndControl(): void
    {
        $taxid = new TaxId();

        foreach (['0101990410012', '2902000410013', '0101990040010', '0101990410080'] as $value) {
            $result = $taxid->validate($value, 'MK', 'embg');
            self::assertTrue($result->isValid());
            self::assertTrue($result->checksumVerified);
            self::assertSame('embg', $taxid->parse($value, 'MK')->scheme);
            self::assertNull($taxid->parse($value, 'MK')->vatNumber());
        }

        foreach (['0101990410013', '3102990410028', '2902900410019', '0101990400017', '0000000000000'] as $value) {
            self::assertFalse($taxid->isValid($value, 'MK', 'embg'));
        }

        self::assertTrue($taxid->validate('4030000375897', 'MK', 'edb')->checksumVerified);
    }
}
