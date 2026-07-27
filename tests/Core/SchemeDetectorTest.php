<?php

declare(strict_types=1);

namespace Tests\Core;

use Daycry\TaxId\Core\SchemeDetector;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Country inference and prefix stripping — the two places where being clever
 * would do real damage.
 */
final class SchemeDetectorTest extends TestCase
{
    private SchemeDetector $detector;

    protected function setUp(): void
    {
        $this->detector = new SchemeDetector();
    }

    /**
     * @return iterable<string, array{0: string, 1: string|null}>
     */
    public static function inferenceProvider(): iterable
    {
        yield 'Spanish VAT prefix'      => ['ESB12345678', 'ES'];
        yield 'German VAT prefix'       => ['DE136695976', 'DE'];
        yield 'Greek EL prefix maps to GR' => ['EL094259216', 'GR'];
        yield 'Northern Ireland XI'     => ['XI123456782', 'XI'];
        yield 'ISO code that is not a VAT prefix' => ['US123456789', 'US'];
        yield 'bare digits are not guessed' => ['123456789', null];
        yield 'unknown prefix'          => ['QQ123456789', null];
        yield 'too short to carry a prefix' => ['ES', null];
    }

    #[DataProvider('inferenceProvider')]
    public function testCountryInference(string $value, ?string $expected): void
    {
        self::assertSame($expected, $this->detector->inferCountry($value));
    }

    /**
     * Guessing a country from bare digits would be worse than useless: nine
     * digits is a plausible tax ID almost everywhere, so a confident wrong
     * answer is the one outcome to avoid.
     */
    public function testDigitsAloneNeverInferACountry(): void
    {
        $detection = $this->detector->detect('123456789');

        self::assertNull($detection->countryCode);
        self::assertFalse($detection->countryKnown());
    }

    public function testPrefixIsStrippedWhenThatIsWhatMakesTheValueMatch(): void
    {
        $detection = $this->detector->detect('ESB12345678');

        self::assertSame('ES', $detection->countryCode);
        self::assertSame('B12345678', $detection->compact);
        self::assertSame('cif', $detection->scheme()?->code);
    }

    /**
     * Austria is the case that forces "whole value first, then stripped": the
     * national number genuinely starts with U, so ATU12345678 only matches
     * once the AT is gone.
     */
    public function testAustrianNumberKeepsItsLeadingLetterAfterStripping(): void
    {
        $detection = $this->detector->detect('ATU10223006');

        self::assertSame('AT', $detection->countryCode);
        self::assertSame('U10223006', $detection->compact);
    }

    /**
     * The mirror case, and the reason the whole-value attempt comes first: an
     * Italian codice fiscale for someone whose surname starts "Ital…" begins
     * with the letters IT, and stripping them would corrupt a valid ID.
     */
    public function testALeadingCountryCodeThatIsPartOfTheValueIsNotStripped(): void
    {
        $detection = $this->detector->detect('ITLMRA85T10A562R', 'IT');

        self::assertSame('ITLMRA85T10A562R', $detection->compact, 'The IT here is part of the surname, not a prefix.');
        self::assertSame('cf', $detection->scheme()?->code);
    }

    public function testGreekNumbersAcceptBothTheIsoCodeAndTheVatPrefix(): void
    {
        self::assertSame('094259216', $this->detector->detect('EL094259216')->compact);
        self::assertSame('094259216', $this->detector->detect('GR094259216', 'GR')->compact);
    }

    public function testAnUnrecognisedValueKeepsItsNormalizedForm(): void
    {
        $detection = $this->detector->detect('ZZZZZZZZZ', 'ES');

        self::assertSame('ZZZZZZZZZ', $detection->compact);
        self::assertSame([], $detection->matches);
        self::assertFalse($detection->hasAnyMatch());
    }

    public function testDetectionReportsEveryMatchingSchemeInRegistryOrder(): void
    {
        // Nine digits in the US match the EIN and the SSN alike.
        $matches = $this->detector->detect('123456789', 'US')->matches;

        self::assertGreaterThanOrEqual(2, count($matches));
        self::assertSame('ein', $matches[0]->code, 'Registry order decides the default reading.');
    }

    public function testRequestingASchemeSelectsItFromTheMatches(): void
    {
        $detection = $this->detector->detect('123456789', 'US');

        self::assertSame('ssn', $detection->scheme('ssn')?->code);
        self::assertNull($detection->scheme('itin'), 'A value that does not match the requested scheme yields null.');
    }

    public function testUnknownCountryProducesADetectionWithoutAProfile(): void
    {
        $detection = $this->detector->detect('123456789', 'ZZ');

        self::assertSame('ZZ', $detection->countryCode);
        self::assertFalse($detection->countryKnown());
        self::assertNull($detection->profile);
    }
}
