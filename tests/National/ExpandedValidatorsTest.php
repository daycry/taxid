<?php

declare(strict_types=1);

namespace Tests\National;

use Daycry\TaxId\TaxId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ExpandedValidatorsTest extends TestCase
{
    /** @return iterable<string, array{string, string, string, bool}> */
    public static function values(): iterable
    {
        // Synthetic fixtures calculated from the published rules, never taxpayer records.
        yield 'JP existing example' => ['JP', 'mynumber', '123456789018', true];
        yield 'JP wrong control' => ['JP', 'mynumber', '123456789019', false];
        yield 'JP remainder zero' => ['JP', 'mynumber', '000000000140', true];
        yield 'JP remainder one' => ['JP', 'mynumber', '000000000060', true];
        yield 'JP wrong folded control' => ['JP', 'mynumber', '000000000061', false];
        yield 'JP weighted position' => ['JP', 'mynumber', '000000000019', true];
        yield 'TW legacy rule' => ['TW', 'ban', '12345675', true];
        yield 'TW expanded space since 2023' => ['TW', 'ban', '12345671', true];
        yield 'TW seventh digit exception' => ['TW', 'ban', '00000079', true];
        yield 'TW expanded seventh digit exception' => ['TW', 'ban', '00000074', true];
        yield 'TW wrong control' => ['TW', 'ban', '12345672', false];
        yield 'TW exception still rejects mistakes' => ['TW', 'ban', '00000078', false];
        yield 'KZ first pass' => ['KZ', 'bin', '111111111110', true];
        yield 'KZ second pass' => ['KZ', 'bin', '123456789013', true];
        yield 'KZ second pass zero' => ['KZ', 'bin', '123456789050', true];
        yield 'KZ wrong control' => ['KZ', 'bin', '123456789012', false];
        yield 'KZ unwritable second remainder' => ['KZ', 'bin', '123456790430', false];
    }

    #[DataProvider('values')]
    public function testPublishedChecksumRules(string $country, string $scheme, string $value, bool $valid): void
    {
        $taxid = new TaxId();
        $result = $taxid->validate($value, $country, $scheme);

        self::assertSame($valid, $result->isValid(), $country . '/' . $scheme . ': ' . $value);

        if (! $valid) {
            self::assertSame('check_digit_failed', $result->errorCode());
            self::assertNull($taxid->tryParse($value, $country));
        }
    }
}
