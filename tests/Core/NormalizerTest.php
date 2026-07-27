<?php

declare(strict_types=1);

namespace Tests\Core;

use Daycry\TaxId\Core\Normalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class NormalizerTest extends TestCase
{
    /**
     * @return iterable<string, array{0: string, 1: string}>
     */
    public static function normalizationProvider(): iterable
    {
        yield 'uppercases'                 => ['12345678z', '12345678Z'];
        yield 'strips spaces'              => ['12 345 678 Z', '12345678Z'];
        yield 'strips dots and dashes'     => ['12.345.678-Z', '12345678Z'];
        yield 'strips slashes'             => ['11.222.333/0001-81', '11222333000181'];
        yield 'strips parentheses'         => ['(12345678)Z', '12345678Z'];
        yield 'strips tabs and newlines'   => ["12345678\tZ\n", '12345678Z'];
        yield 'strips underscores'         => ['1234_5678_Z', '12345678Z'];
        yield 'leaves an empty string'     => ['', ''];
        yield 'separators only'            => ['---', ''];
    }

    #[DataProvider('normalizationProvider')]
    public function testNormalize(string $input, string $expected): void
    {
        self::assertSame($expected, (new Normalizer())->normalize($input));
    }

    /**
     * Unexpected characters must SURVIVE normalization. Stripping them here
     * would turn "user typed an email in the tax ID field" into a confusing
     * length error instead of a clear IllegalCharacters violation.
     */
    public function testUnexpectedCharactersAreNotStripped(): void
    {
        // The dot IS a separator and goes; the @ is not and must survive, so the
        // Validator can report IllegalCharacters instead of a baffling length error.
        self::assertSame('ME@EXAMPLECOM', (new Normalizer())->normalize('me@example.com'));
    }

    /**
     * The ampersand is legal inside a Mexican RFC, so it must pass through.
     */
    public function testAmpersandSurvives(): void
    {
        self::assertSame('A&M010101AA1', (new Normalizer())->normalize('a&m 010101 aa1'));
    }

    public function testNormalizationIsIdempotent(): void
    {
        $normalizer = new Normalizer();
        $once       = $normalizer->normalize('11.222.333/0001-81');

        self::assertSame($once, $normalizer->normalize($once));
    }
}
