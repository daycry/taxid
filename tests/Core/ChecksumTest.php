<?php

declare(strict_types=1);

namespace Tests\Core;

use Daycry\TaxId\Core\Checksum\Iso7064;
use Daycry\TaxId\Core\Checksum\Luhn;
use Daycry\TaxId\Core\Checksum\Mod97;
use Daycry\TaxId\Core\Checksum\Weights;
use PHPUnit\Framework\TestCase;

/**
 * The shared algorithm primitives. Around sixty national validators sit on top
 * of these four classes, so a bug here is a bug in sixty countries at once —
 * which is exactly why they are tested against published reference values
 * rather than only through their callers.
 */
final class ChecksumTest extends TestCase
{
    public function testLuhnAcceptsAKnownValidSequence(): void
    {
        self::assertTrue(Luhn::isValid('79927398713'));
        self::assertTrue(Luhn::isValid('552100554'));
    }

    public function testLuhnRejectsEverySingleDigitError(): void
    {
        $valid = '79927398713';

        for ($i = 0; $i < strlen($valid); $i++) {
            $mutant     = $valid;
            $mutant[$i] = (string) (((int) $valid[$i] + 1) % 10);

            self::assertFalse(Luhn::isValid($mutant), 'Luhn accepted a single-digit error at position ' . $i);
        }
    }

    public function testLuhnRejectsNonDigits(): void
    {
        self::assertFalse(Luhn::isValid(''));
        self::assertFalse(Luhn::isValid('7992739871X'));
    }

    public function testLuhnCheckDigitCompletesAPayload(): void
    {
        self::assertSame(3, Luhn::checkDigit('7992739871'));
        self::assertTrue(Luhn::isValid('7992739871' . Luhn::checkDigit('7992739871')));
    }

    public function testMod97MatchesNativeArithmeticForShortValues(): void
    {
        self::assertSame(1234567 % 97, Mod97::remainder('1234567'));
        self::assertSame(0, Mod97::remainder('97'));
    }

    /**
     * The whole point of the digit-by-digit implementation: numbers far beyond
     * what an int can hold, with no bcmath.
     */
    public function testMod97HandlesValuesLongerThanAnInteger(): void
    {
        $long = str_repeat('9', 60);

        self::assertGreaterThanOrEqual(0, Mod97::remainder($long));
        self::assertLessThan(97, Mod97::remainder($long));
    }

    public function testMod97SkipsNonDigits(): void
    {
        self::assertSame(Mod97::remainder('1234567'), Mod97::remainder('12-34.567'));
    }

    public function testIso7064Mod1110RoundTrips(): void
    {
        foreach (['6943515153', '13669597', '1234567890'] as $payload) {
            $check = Iso7064::mod1110($payload);

            self::assertGreaterThanOrEqual(0, $check);
            self::assertLessThanOrEqual(9, $check);
            self::assertTrue(Iso7064::isValidMod1110($payload . $check));
        }
    }

    public function testIso7064Mod1110RejectsAWrongCheckDigit(): void
    {
        $payload = '6943515153';
        $wrong   = (Iso7064::mod1110($payload) + 1) % 10;

        self::assertFalse(Iso7064::isValidMod1110($payload . $wrong));
    }

    public function testIso7064Mod1110RejectsNonNumericInput(): void
    {
        self::assertFalse(Iso7064::isValidMod1110('X'));
        self::assertFalse(Iso7064::isValidMod1110('12345X'));
    }

    public function testIso7064Mod112ProducesADigitOrX(): void
    {
        $check = Iso7064::mod112('11010119900307001');

        self::assertMatchesRegularExpression('/^[0-9X]$/', $check);
        self::assertSame('1', $check);
    }

    public function testIso7064Mod3736ProducesAnAlphanumericCharacter(): void
    {
        self::assertMatchesRegularExpression('/^[0-9A-Z]$/', Iso7064::mod3736('ABC123'));
    }

    public function testWeightsSumPairsPositionally(): void
    {
        self::assertSame(1 * 3 + 2 * 2 + 3 * 1, Weights::sum('123', [3, 2, 1]));
    }

    public function testWeightsSumIgnoresSurplusWeights(): void
    {
        self::assertSame(1 * 3 + 2 * 2, Weights::sum('12', [3, 2, 1, 9, 9]));
    }

    public function testCyclicSumWalksFromTheRightByDefault(): void
    {
        // '123' right to left is 3,2,1 against weights 2,3,4.
        self::assertSame(3 * 2 + 2 * 3 + 1 * 4, Weights::cyclicSum('123', [2, 3, 4]));
    }

    public function testCyclicSumRepeatsTheWeightSequence(): void
    {
        // Five digits, two weights: 2,1,2,1,2 walking from the right.
        self::assertSame(
            5 * 2 + 4 * 1 + 3 * 2 + 2 * 1 + 1 * 2,
            Weights::cyclicSum('12345', [2, 1]),
        );
    }

    public function testCyclicSumWithNoWeightsIsZero(): void
    {
        self::assertSame(0, Weights::cyclicSum('12345', []));
    }

    public function testMod11ComplementFoldsTheOverflowCases(): void
    {
        // sum % 11 === 0 -> 11 -> folded to the elevenAs value.
        self::assertSame(0, Weights::mod11ComplementDigit(22));
        // sum % 11 === 1 -> 10 -> not writable unless the caller says otherwise.
        self::assertNull(Weights::mod11ComplementDigit(23));
        self::assertSame(1, Weights::mod11ComplementDigit(23, tenAs: 1));
        self::assertSame(9, Weights::mod11ComplementDigit(24));
    }

    public function testMod11RemainderDigitTreatsLowRemaindersAsZero(): void
    {
        self::assertSame(0, Weights::mod11RemainderDigit(22));   // remainder 0
        self::assertSame(0, Weights::mod11RemainderDigit(23));   // remainder 1
        self::assertSame(9, Weights::mod11RemainderDigit(24));   // remainder 2
    }
}
