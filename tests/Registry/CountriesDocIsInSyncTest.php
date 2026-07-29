<?php

declare(strict_types=1);

namespace Tests\Registry;

use Daycry\TaxId\Registry\Registry;
use Daycry\TaxId\Registry\TaxIdScheme;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * `docs/countries.md` restates all 146 schemes as a table, and a hand-maintained
 * copy of the registry is a copy that rots. Its header used to claim the file was
 * generated; no generator ever existed, and three consecutive releases each needed
 * a correction round for numbers that had drifted.
 *
 * So the claim is enforced here instead of asserted in prose. Every field a reader
 * can see in that table is compared against the live registry, in both directions,
 * and the header's own counts are checked too. A drifted table now fails CI rather
 * than quietly misinforming whoever reads it.
 *
 * Regenerating by hand is fine — this test tells you exactly which cell is wrong.
 */
final class CountriesDocIsInSyncTest extends TestCase
{
    private const string DOC = __DIR__ . '/../../docs/countries.md';

    /**
     * One parsed table row: the seven fields a reader can see.
     *
     * @var array<string, array{country: string, name: string, entity: string, vat: string, lengths: string, checksum: string, example: string}>|null
     */
    private static ?array $rows = null;

    /**
     * @return iterable<string, array{0: string, 1: TaxIdScheme}>
     */
    public static function schemeProvider(): iterable
    {
        foreach ((new Registry())->all() as $code => $profile) {
            foreach ($profile->schemes as $scheme) {
                yield $code . '/' . $scheme->code => [$code, $scheme];
            }
        }
    }

    #[DataProvider('schemeProvider')]
    public function testEverySchemeHasARowThatAgreesWithTheRegistry(string $country, TaxIdScheme $scheme): void
    {
        $key  = $country . '/' . $scheme->code;
        $rows = self::rows();

        self::assertArrayHasKey($key, $rows, sprintf(
            '%s is in the registry but has no row in docs/countries.md. Add it in %s\'s block, '
                . 'after that country\'s existing rows.',
            $key,
            $country,
        ));

        $expected = [
            'country'  => (new Registry())->get($country)->name,
            'name'     => $scheme->name,
            'entity'   => $scheme->entityType->value,
            'vat'      => $scheme->vat ? '✅' : '—',
            'lengths'  => implode(', ', $scheme->lengths),
            'checksum' => $scheme->hasCheckDigit() ? '✅' : '—',
            'example'  => $scheme->examples[0] ?? '',
        ];

        foreach ($expected as $field => $value) {
            self::assertSame($value, $rows[$key][$field], sprintf(
                'docs/countries.md disagrees with the registry about %s\'s %s.',
                $key,
                $field,
            ));
        }
    }

    /**
     * The other direction: a row for a scheme that no longer exists is just as
     * misleading as a missing one, and nothing else would catch it.
     */
    public function testNoRowDescribesASchemeTheRegistryDoesNotHave(): void
    {
        $registry = new Registry();

        foreach (array_keys(self::rows()) as $key) {
            [$country, $code] = explode('/', $key, 2);

            self::assertTrue($registry->has($country), $key . ': docs/countries.md has a row for an unknown country.');
            self::assertTrue(
                $registry->get($country)->hasScheme($code),
                $key . ': docs/countries.md has a row for a scheme that is not in the registry.',
            );
        }
    }

    /**
     * The header states the coverage in prose, which is the number people quote.
     */
    public function testTheHeaderCountsMatchTheRegistry(): void
    {
        $registry    = new Registry();
        $checksummed = 0;

        foreach ($registry->all() as $profile) {
            foreach ($profile->schemes as $scheme) {
                $checksummed += $scheme->hasCheckDigit() ? 1 : 0;
            }
        }

        $expected = sprintf(
            '**%d jurisdictions, %d schemes, %d of them with a verified check digit.**',
            count($registry->countries()),
            $registry->schemeCount(),
            $checksummed,
        );

        self::assertStringContainsString($expected, (string) file_get_contents(self::DOC), 'The header count line is stale.');
    }

    /**
     * The file tells readers the rows are in registry order, because that is also
     * detection order — so the order is documentation, not decoration.
     */
    public function testRowsFollowRegistryOrderWithinEachCountry(): void
    {
        $documented = [];

        foreach (array_keys(self::rows()) as $key) {
            [$country, $code]      = explode('/', $key, 2);
            $documented[$country][] = $code;
        }

        foreach ((new Registry())->all() as $country => $profile) {
            self::assertSame(
                array_keys($profile->schemes),
                $documented[$country] ?? [],
                $country . ': docs/countries.md lists the schemes in a different order than the registry.',
            );
        }
    }

    /**
     * @return array<string, array{country: string, name: string, entity: string, vat: string, lengths: string, checksum: string, example: string}>
     */
    private static function rows(): array
    {
        if (self::$rows !== null) {
            return self::$rows;
        }

        $pattern = '/^\| `([A-Z]{2})` \| (.+?) \| `([a-z0-9_]+)` \| (.+?) \| (\S+) \| (\S+) \| (.+?) \| (\S+) \| `(.+?)` \|$/u';
        $rows    = [];

        foreach ((array) file(self::DOC) as $line) {
            if (preg_match($pattern, trim((string) $line), $m) !== 1) {
                continue;
            }

            $rows[$m[1] . '/' . $m[3]] = [
                'country'  => $m[2],
                'name'     => $m[4],
                'entity'   => $m[5],
                'vat'      => $m[6],
                'lengths'  => $m[7],
                'checksum' => $m[8],
                'example'  => $m[9],
            ];
        }

        self::assertNotSame([], $rows, 'No table rows were parsed from docs/countries.md — has its format changed?');

        return self::$rows = $rows;
    }
}
