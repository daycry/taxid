<?php

declare(strict_types=1);

namespace Tests\National;

use Daycry\TaxId\National\MeJmbgValidator;
use Daycry\TaxId\TaxId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class MontenegroJmbgTest extends TestCase
{
    /** @return iterable<string, array{string, bool}> */
    public static function checksumValues(): iterable
    {
        // Synthetic bodies, controls calculated independently with weights 765432765432.
        yield 'ordinary control, sum 74' => ['0101990210013', true];
        yield 'control one, sum 76' => ['0101990210021', true];
        yield 'remainder zero, sum 99' => ['0101990260010', true];
        yield 'mutated ordinary control' => ['0101990210014', false];
        yield 'mutated zero control' => ['0101990260011', false];
        yield 'remainder one cannot be folded, sum 78' => ['0101990210030', false];
    }

    #[DataProvider('checksumValues')]
    public function testChecksumAndAssurance(string $value, bool $valid): void
    {
        $taxid = new TaxId();
        $result = $taxid->validate($value, 'ME', 'jmbg');

        self::assertSame($valid, $result->isValid());
        self::assertSame($valid ? true : null, $result->checksumVerified);
        self::assertSame($valid ? 'jmbg' : null, $result->scheme);
        self::assertSame($valid ? null : 'check_digit_failed', $result->errorCode());
        self::assertSame($valid, $taxid->isValid($value, 'ME'));
    }

    public function testImpossibleDateWithCorrectChecksumIsRejected(): void
    {
        // February 31; sum 99, remainder zero, correct control zero.
        $result = (new TaxId())->validate('3102990210010', 'ME', 'jmbg');

        self::assertFalse($result->isValid());
        self::assertSame('check_digit_failed', $result->errorCode());
    }

    public function testRegionOutsideMontenegroIsRejected(): void
    {
        // Region 30, otherwise a valid date and checksum (sum 75, control 2).
        self::assertFalse((new TaxId())->isValid('0101990300012', 'ME', 'jmbg'));
    }

    /** @return iterable<string, array{string, bool}> */
    public static function calendarValues(): iterable
    {
        // Even invalid dates have independently calculated, otherwise valid controls.
        yield 'leap day in 2000' => ['2902000210014', true];
        yield '1900 is not a leap year' => ['2902900210028', false];
        yield 'nineteenth century' => ['0101800210012', true];
        yield 'zero day' => ['0001990210019', false];
        yield 'zero month' => ['0100990210017', false];
        yield 'month thirteen' => ['0113990210011', false];
        yield 'April has thirty days' => ['3104990210013', false];
    }

    #[DataProvider('calendarValues')]
    public function testCalendarDate(string $value, bool $valid): void
    {
        self::assertSame($valid, (new MeJmbgValidator())->verify($value));
        self::assertSame($valid, (new TaxId())->isValid($value, 'ME', 'jmbg'));
    }

    public function testEveryDomesticRegionAndBothBoundaries(): void
    {
        $taxid = new TaxId();
        $verifier = new MeJmbgValidator();

        foreach ([
            '0101990210013', '0101990220019', '0101990230014',
            '0101990240028', '0101990250015', '0101990260010',
            '0101990270016', '0101990280011', '0101990290017',
        ] as $valid) {
            self::assertTrue($verifier->verify($valid));
            self::assertTrue($taxid->isValid($valid, 'ME', 'jmbg'));
        }

        // Correct checksums, but outside the documented domestic region range.
        foreach (['0101990200018', '0101990300012', '0101990020028'] as $invalid) {
            self::assertFalse($verifier->verify($invalid));
            self::assertFalse($taxid->isValid($invalid, 'ME', 'jmbg'));
        }
    }

    public function testDirectVerifierRequiresCompactAsciiDigits(): void
    {
        $verifier = new MeJmbgValidator();

        self::assertTrue($verifier->supports('me', 'JMBG'));
        self::assertFalse($verifier->supports('MK', 'jmbg'));
        self::assertFalse($verifier->supports('ME', 'pib'));

        foreach (['', '010199021001', '01019902100130', "0101990210013\n", '0101990 21 0013', 'A101990210013'] as $invalid) {
            self::assertFalse($verifier->verify($invalid));
        }
    }

    public function testParsingAndNormalizationPreserveThePersonalScheme(): void
    {
        $taxid = new TaxId();
        $parsed = $taxid->parse('0101990 21 0013', 'ME');

        self::assertSame('jmbg', $parsed->scheme);
        self::assertSame('natural', $parsed->entityType->value);
        self::assertSame('3', $parsed->checkDigit);
        self::assertSame('0101990210013', $parsed->format());
        self::assertNull($parsed->vatNumber());
        self::assertFalse($taxid->isValid('0101990210013', 'ME', 'pib'));
    }

    public function testExistingPibRemainsAvailableWithoutClaimingAChecksum(): void
    {
        $taxid = new TaxId();
        $result = $taxid->validate('12345678', 'ME');

        self::assertTrue($result->isValid());
        self::assertSame('pib', $result->scheme);
        self::assertFalse($result->checksumVerified);
        self::assertTrue($taxid->isValid('12345678', 'ME', 'pib'));
        self::assertFalse($taxid->isValid('12345678', 'ME', 'jmbg'));
    }
}
