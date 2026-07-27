<?php

declare(strict_types=1);

namespace Tests\Core;

use Daycry\TaxId\Core\Formatter;
use Daycry\TaxId\Enums\TaxIdFormat;
use PHPUnit\Framework\TestCase;

final class FormatterTest extends TestCase
{
    public function testCompactReturnsTheValueUnchanged(): void
    {
        self::assertSame(
            '12345678909',
            (new Formatter())->format('12345678909', '###.###.###-##', TaxIdFormat::Compact),
        );
    }

    public function testPrintFillsTheMask(): void
    {
        self::assertSame(
            '123.456.789-09',
            (new Formatter())->format('12345678909', '###.###.###-##', TaxIdFormat::Print),
        );
    }

    public function testPrintWithoutAMaskReturnsTheCompactForm(): void
    {
        self::assertSame('12345678909', (new Formatter())->format('12345678909', null, TaxIdFormat::Print));
    }

    /**
     * A mask that doesn't fit must be ignored rather than producing a
     * half-filled string — variable-length schemes share one mask.
     */
    public function testMismatchedMaskFallsBackToTheCompactForm(): void
    {
        self::assertSame('123', (new Formatter())->format('123', '###.###', TaxIdFormat::Print));
    }

    public function testAnonymizedKeepsOnlyTheLastTwoCharacters(): void
    {
        self::assertSame(
            '*********09',
            (new Formatter())->format('12345678909', '###.###.###-##', TaxIdFormat::Anonymized),
        );
    }

    /**
     * Anything short enough that "the last two characters" would be most of the
     * value is masked completely — there would be nothing left to hide.
     */
    public function testAnonymizedMasksVeryShortValuesEntirely(): void
    {
        $formatter = new Formatter();

        self::assertSame('**', $formatter->format('AB', null, TaxIdFormat::Anonymized));
        self::assertSame('', $formatter->format('', null, TaxIdFormat::Anonymized));
    }

    public function testMaskLiteralsCanAppearAnywhere(): void
    {
        self::assertSame(
            'CHE-116.281.839',
            (new Formatter())->format('CHE116281839', '###-###.###.###', TaxIdFormat::Print),
        );
    }
}
