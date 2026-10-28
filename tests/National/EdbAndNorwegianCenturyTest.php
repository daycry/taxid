<?php

declare(strict_types=1);

namespace Tests\National;

use Daycry\TaxId\TaxId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EdbAndNorwegianCenturyTest extends TestCase
{
    public function testEdbRejectsAnIncorrectControlDigit(): void
    {
        $result = (new TaxId())->validate('4000000000014', 'MK', 'edb');

        self::assertFalse($result->isValid());
        self::assertSame('check_digit_failed', $result->errorCode());
    }

    public function testEdbRejectsTheAllZeroPlaceholder(): void
    {
        $result = (new TaxId())->validate('0000000000000', 'MK', 'edb');

        self::assertFalse($result->isValid());
        self::assertSame('reserved_value', $result->errorCode());
    }

    public function testEdbChecksArithmeticAndStillInfersItsPrefix(): void
    {
        $taxid = new TaxId();

        // Synthetic values for ordinary, remainder-zero and folded-control branches.
        foreach (['4000000000013', '4000000000021', '4000000000030', '4000000000110'] as $value) {
            $result = $taxid->validate($value, 'MK', 'edb');
            self::assertTrue($result->isValid());
            self::assertTrue($result->checksumVerified);
            self::assertSame('edb', $result->scheme);
            self::assertTrue($taxid->isValid('MK' . $value));
        }

        self::assertFalse($taxid->isValid('0101990410013', 'MK'), 'A bad personal checksum must not escape through EDB.');
        self::assertFalse($taxid->isValid('4000000000013', 'MK', 'embg'));
    }

    /** @return iterable<string, array{string, bool}> */
    public static function norwegianCenturyValues(): iterable
    {
        yield '1900 non-leap day' => ['29020010027', false];
        yield '2000 leap day' => ['29020050088', true];
        yield 'pre-2000 D-number non-leap day' => ['69020010010', false];
        yield 'post-2000 D-number leap day' => ['69020050071', true];
        yield '1854 allocation' => ['01015450068', true];
        yield '1940 extended allocation' => ['01014090017', true];
        yield '2039 allocation boundary' => ['01013950187', true];
        yield 'unallocated century combination' => ['01015075097', false];
        yield 'legacy D-number' => ['41019012393', true];
    }

    #[DataProvider('norwegianCenturyValues')]
    public function testNorwegianCalendarUsesTheAllocatedCentury(string $value, bool $valid): void
    {
        self::assertSame($valid, (new TaxId())->isValid($value, 'NO', 'fodselsnummer'));
    }
}
