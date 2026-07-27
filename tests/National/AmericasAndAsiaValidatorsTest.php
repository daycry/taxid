<?php

declare(strict_types=1);

namespace Tests\National;

use Daycry\TaxId\Enums\ViolationCode;
use Daycry\TaxId\TaxId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Check digits outside Europe, plus the behaviours peculiar to them: the two
 * mod-11 digits of Latin America, the base-36 Luhn of India's GSTIN, the
 * 31-symbol alphabet of China's USCC, and the United States' complete absence
 * of any checksum at all.
 */
final class AmericasAndAsiaValidatorsTest extends TestCase
{
    private TaxId $taxid;

    protected function setUp(): void
    {
        $this->taxid = new TaxId();
    }

    /**
     * @return iterable<string, array{0: string, 1: string, 2: string[], 3: string[]}>
     */
    public static function schemeProvider(): iterable
    {
        yield 'BR CPF'  => ['BR', 'cpf', ['11144477735'], ['11144477736', '11144477725']];
        yield 'BR CNPJ' => ['BR', 'cnpj', ['11222333000181'], ['11222333000182', '11222333000191']];

        yield 'MX RFC' => ['MX', 'rfc', ['AAA010101AA1', 'XAXX010101000'], ['AAA010101AA2']];

        yield 'AR CUIT' => ['AR', 'cuit', ['20123456786'], ['20123456787']];
        yield 'CL RUT'  => ['CL', 'rut', ['123456785', '12345670K'], ['123456786', '123456700']];
        yield 'CO NIT'  => ['CO', 'nit', ['8300013381'], ['8300013382']];
        yield 'PE RUC'  => ['PE', 'ruc', ['20100070970'], ['20100070971', '30100070970']];
        yield 'UY RUT'  => ['UY', 'rut', ['211003420017'], ['211003420018']];
        yield 'VE RIF'  => ['VE', 'rif', ['J305329958'], ['J305329959', 'X305329958']];

        yield 'EC RUC natural' => ['EC', 'ruc', ['1710034065001'], ['1710034066001']];
        yield 'EC RUC private' => ['EC', 'ruc', ['1790012344001'], ['1790012345001']];
        yield 'EC RUC public'  => ['EC', 'ruc', ['1760012320001'], ['1760012330001']];
        yield 'EC cédula'      => ['EC', 'cedula', ['1710034065'], ['1710034066', '9910034065']];

        yield 'DO RNC' => ['DO', 'rnc', ['101010101'], ['101010102']];
        yield 'PY RUC' => ['PY', 'ruc', ['800177320'], ['800177321']];

        yield 'CA BN'  => ['CA', 'bn', ['123456782', '123456782RT0001'], ['123456783']];
        yield 'CA SIN' => ['CA', 'sin', ['130692544'], ['130692545']];

        yield 'IN GSTIN' => ['IN', 'gstin', ['27AAPFU0939F1ZV'], ['27AAPFU0939F1ZW', '27AAPFU0939F1AV']];

        yield 'CN USCC' => ['CN', 'uscc', ['91350100M000100Y56'], ['91350100M000100Y57']];
        yield 'CN RIC'  => ['CN', 'ric', ['110101199003070011'], ['110101199003070012']];

        yield 'JP corporate number' => ['JP', 'houjinbangou', ['3700150000123'], ['4700150000123']];
        yield 'KR BRN'              => ['KR', 'brn', ['1234567891'], ['1234567892']];

        yield 'AU ABN' => ['AU', 'abn', ['51824753556'], ['51824753557']];
        yield 'AU ACN' => ['AU', 'acn', ['004085616'], ['004085617']];
        yield 'AU TFN' => ['AU', 'tfn', ['123456782'], ['123456783']];

        yield 'NZ IRD' => ['NZ', 'ird', ['490918507'], ['490918508']];

        yield 'IL company' => ['IL', 'hp', ['512345679'], ['512345678']];
        yield 'IL ID'      => ['IL', 'id', ['123456782'], ['123456783']];
    }

    /**
     * @param string[] $valid
     * @param string[] $invalid
     */
    #[DataProvider('schemeProvider')]
    public function testScheme(string $country, string $scheme, array $valid, array $invalid): void
    {
        foreach ($valid as $value) {
            self::assertTrue(
                $this->taxid->isValid($value, $country, $scheme),
                sprintf(
                    '%s/%s should accept "%s": %s',
                    $country,
                    $scheme,
                    $value,
                    (string) $this->taxid->validate($value, $country, $scheme)->error(),
                ),
            );
        }

        foreach ($invalid as $value) {
            self::assertFalse(
                $this->taxid->isValid($value, $country, $scheme),
                sprintf('%s/%s should reject "%s"', $country, $scheme, $value),
            );
        }
    }

    // ---------------------------------------------------------------- specifics

    /**
     * Repeated-digit CPFs and CNPJs satisfy the mod-11 arithmetic perfectly and
     * are never issued. They are the single most common bad value in Brazilian
     * test data, so they get their own violation code.
     */
    public function testBrazilianRepeatedDigitValuesAreReservedNotChecksumFailures(): void
    {
        foreach (['11111111111', '00000000000', '99999999999'] as $cpf) {
            self::assertSame(
                ViolationCode::ReservedValue,
                $this->taxid->validate($cpf, 'BR', 'cpf')->firstViolation()?->code,
                $cpf . ' should be reserved, not a check-digit failure',
            );
        }

        self::assertSame(
            ViolationCode::ReservedValue,
            $this->taxid->validate('11111111111111', 'BR', 'cnpj')->firstViolation()?->code,
        );
    }

    /**
     * The SAT's generic RFCs appear on a great many real invoices and do not
     * follow the check-digit algorithm.
     */
    public function testMexicanGenericRfcsAreAccepted(): void
    {
        self::assertTrue($this->taxid->isValid('XAXX010101000', 'MX'), 'Generic domestic RFC');
        self::assertTrue($this->taxid->isValid('XEXX010101000', 'MX'), 'Generic foreign RFC');
    }

    /**
     * An RFC may contain an ampersand, which the normalizer must let through
     * and the validator must fold into the checksum.
     */
    public function testMexicanRfcWithAnAmpersandIsNotRejectedAsIllegal(): void
    {
        $result = $this->taxid->validate('A&M010101AA1', 'MX', 'rfc');

        self::assertNotSame(
            ViolationCode::IllegalCharacters,
            $result->firstViolation()?->code,
            'The ampersand is part of the RFC alphabet.',
        );
    }

    /**
     * Ecuador packs three different algorithms into one format, chosen by the
     * third digit. A number valid under one must not be accepted under another.
     */
    public function testEcuadorPicksItsAlgorithmFromTheThirdDigit(): void
    {
        // Valid as a private company (third digit 9) …
        self::assertTrue($this->taxid->isValid('1790012344001', 'EC', 'ruc'));
        // … but the same digits with a natural-person marker are not.
        self::assertFalse($this->taxid->isValid('1710012344001', 'EC', 'ruc'));
    }

    public function testEcuadorRejectsAnImpossibleProvinceCode(): void
    {
        self::assertFalse($this->taxid->isValid('9910034065', 'EC', 'cedula'), 'Province 99 does not exist.');
    }

    /**
     * The United States issues no check digit on any of its three identifiers.
     * All this package can honestly do is enforce the documented never-issued
     * ranges — and it must say so through the violation code.
     */
    public function testUnitedStatesRangeRulesAreTheOnlyOfflineCheck(): void
    {
        // Structurally fine, so accepted — there is nothing else to test it against.
        self::assertTrue($this->taxid->isValid('123456789', 'US', 'ssn'));
        self::assertTrue($this->taxid->isValid('123456789', 'US', 'ein'));

        // Never-issued ranges.
        foreach (['000123456', '666123456', '900123456'] as $ssn) {
            self::assertSame(
                ViolationCode::ReservedValue,
                $this->taxid->validate($ssn, 'US', 'ssn')->firstViolation()?->code,
            );
        }

        self::assertSame(
            ViolationCode::ReservedValue,
            $this->taxid->validate('001234567', 'US', 'ein')->firstViolation()?->code,
        );
    }

    public function testItinRequiresAnAllocatedMiddleGroup(): void
    {
        self::assertTrue($this->taxid->isValid('970512345', 'US', 'itin'), 'Group 51 is allocated.');
        self::assertSame(
            ViolationCode::ReservedValue,
            $this->taxid->validate('971234567', 'US', 'itin')->firstViolation()?->code,
            'Group 12 has never been allocated.',
        );
    }

    /**
     * A nine-digit US number is read as an EIN by default because registry
     * order says so; the SSN reading has to be asked for.
     */
    public function testUnqualifiedNineDigitUsValueIsReadAsAnEin(): void
    {
        self::assertSame('ein', $this->taxid->parse('123456789', 'US')->scheme);
        self::assertTrue($this->taxid->isValid('123456789', 'US', 'ssn'));
    }

    /**
     * Canada's Business Number may carry a CRA program account suffix that
     * takes no part in the checksum.
     */
    public function testCanadianProgramAccountSuffixIsIgnoredByTheChecksum(): void
    {
        self::assertTrue($this->taxid->isValid('123456782RT0001', 'CA', 'bn'));
        self::assertFalse($this->taxid->isValid('123456783RT0001', 'CA', 'bn'));
    }

    public function testCanadianSinRejectsNeverIssuedLeadingDigits(): void
    {
        self::assertSame(
            ViolationCode::ReservedValue,
            $this->taxid->validate('046454286', 'CA', 'sin')->firstViolation()?->code,
            'A SIN never starts with 0, however Luhn-valid the rest is.',
        );
    }

    /**
     * The GSTIN embeds the holder's PAN, so corrupting the PAN changes the
     * final character — that coupling is the whole point of the format.
     */
    public function testIndianGstinIsCoupledToItsPan(): void
    {
        self::assertTrue($this->taxid->isValid('27AAPFU0939F1ZV', 'IN', 'gstin'));
        self::assertFalse($this->taxid->isValid('27AAPFU0939G1ZV', 'IN', 'gstin'));
        self::assertTrue($this->taxid->isValid('AAPFU0939F', 'IN', 'pan'));
    }

    /**
     * China's USCC alphabet deliberately omits I, O, S, V and Z.
     */
    public function testChineseUsccRejectsExcludedLetters(): void
    {
        self::assertFalse($this->taxid->isValid('91350100I000100Y56', 'CN', 'uscc'));
        self::assertFalse($this->taxid->isValid('91350100O000100Y56', 'CN', 'uscc'));
    }

    /**
     * Japan puts its check digit first, which is easy to implement backwards.
     */
    public function testJapaneseCheckDigitIsTheLeadingOne(): void
    {
        self::assertTrue($this->taxid->isValid('3700150000123', 'JP', 'houjinbangou'));

        // Same body, every other leading digit must fail.
        for ($lead = 0; $lead <= 9; $lead++) {
            if ($lead === 3) {
                continue;
            }

            self::assertFalse(
                $this->taxid->isValid($lead . '700150000123', 'JP', 'houjinbangou'),
                'Leading digit ' . $lead . ' should not check out',
            );
        }
    }

    /**
     * Structural-only schemes must still enforce their shape, or "no checksum"
     * would quietly become "no validation".
     */
    public function testStructuralOnlySchemesStillEnforceTheirShape(): void
    {
        self::assertTrue($this->taxid->isValid('4012345678', 'ZA', 'vat'));
        self::assertFalse($this->taxid->isValid('5012345678', 'ZA', 'vat'), 'A ZA VAT number always starts with 4.');

        self::assertTrue($this->taxid->isValid('300000000000003', 'SA', 'vat'));
        self::assertFalse($this->taxid->isValid('300000000000004', 'SA', 'vat'), 'A Saudi VAT number ends in 3.');
    }
}
