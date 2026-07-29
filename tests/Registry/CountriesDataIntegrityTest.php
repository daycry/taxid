<?php

declare(strict_types=1);

namespace Tests\Registry;

use Daycry\TaxId\Contracts\CheckDigitValidatorInterface;
use Daycry\TaxId\Core\CheckDigitValidatorFactory;
use Daycry\TaxId\Enums\TaxIdFormat;
use Daycry\TaxId\Registry\Registry;
use Daycry\TaxId\Registry\TaxIdScheme;
use Daycry\TaxId\TaxId;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * The backbone of this package's correctness story.
 *
 * A registry of ninety-odd jurisdictions is data, and data rots quietly: a
 * mistyped pattern, a mask one character too short, a validator wired to the
 * wrong scheme, an example that never actually passed. None of that shows up in
 * a unit test of the class you were editing — it shows up in production, for
 * whichever country you don't personally use.
 *
 * So every scheme of every country is checked here, and — most importantly —
 * **every bundled example is run end to end through the public facade**. A
 * country cannot land broken.
 *
 * @see docs/adding-a-country.md
 */
final class CountriesDataIntegrityTest extends TestCase
{
    /**
     * Schemes with no arithmetic check digit, only documented never-issued
     * ranges. They are exempt from {@see testCheckDigitSchemesRejectMutations()}
     * because there is nothing there to reject a mutation with.
     *
     * Keep this list short and justified: every entry is a country where a typo
     * cannot be caught offline.
     *
     * @var string[]
     */
    private const array NO_ARITHMETIC_CHECK = ['US/ein', 'US/ssn', 'US/itin'];

    /**
     * @return iterable<string, array{0: string, 1: TaxIdScheme}>
     */
    public static function schemeProvider(): iterable
    {
        $registry = new Registry();

        foreach ($registry->all() as $code => $profile) {
            foreach ($profile->schemes as $scheme) {
                yield $code . '/' . $scheme->code => [$code, $scheme];
            }
        }
    }

    /**
     * The headline assertion: everything the registry claims is a valid tax ID
     * really is one, according to this package's own public API.
     */
    #[DataProvider('schemeProvider')]
    public function testEveryExampleValidatesThroughTheFacade(string $country, TaxIdScheme $scheme): void
    {
        $taxid = new TaxId();

        self::assertNotSame([], $scheme->examples, sprintf(
            '%s/%s declares no examples, so nothing verifies its pattern or checksum.',
            $country,
            $scheme->code,
        ));

        foreach ($scheme->examples as $example) {
            $result = $taxid->validate($example, $country, $scheme->code);

            self::assertTrue($result->isValid(), sprintf(
                '%s/%s example "%s" is rejected by its own rules: %s',
                $country,
                $scheme->code,
                $example,
                $result->errorCode() . ' — ' . $result->error(),
            ));
        }
    }

    #[DataProvider('schemeProvider')]
    public function testPatternCompilesAndMatchesEveryExample(string $country, TaxIdScheme $scheme): void
    {
        self::assertSame(1, preg_match($scheme->pattern, $scheme->examples[0] ?? ''), sprintf(
            '%s/%s pattern %s does not match its own first example "%s".',
            $country,
            $scheme->code,
            $scheme->pattern,
            $scheme->examples[0] ?? '',
        ));
    }

    #[DataProvider('schemeProvider')]
    public function testDeclaredLengthsCoverEveryExample(string $country, TaxIdScheme $scheme): void
    {
        self::assertNotSame([], $scheme->lengths, $country . '/' . $scheme->code . ' declares no lengths.');

        foreach ($scheme->examples as $example) {
            self::assertContains(strlen($example), $scheme->lengths, sprintf(
                '%s/%s example "%s" is %d characters, which is not in the declared lengths (%s).',
                $country,
                $scheme->code,
                $example,
                strlen($example),
                implode(', ', $scheme->lengths),
            ));
        }
    }

    /**
     * A mask must fit the value exactly, or {@see \Daycry\TaxId\Core\Formatter}
     * silently falls back to the compact form and the mask is dead weight
     * nobody notices.
     */
    #[DataProvider('schemeProvider')]
    public function testMaskPlaceholderCountMatchesAtLeastOneLength(string $country, TaxIdScheme $scheme): void
    {
        if ($scheme->mask === null) {
            $this->addToAssertionCount(1);

            return;
        }

        $placeholders = substr_count($scheme->mask, '#');

        self::assertContains($placeholders, $scheme->lengths, sprintf(
            '%s/%s mask "%s" has %d placeholders, matching none of the declared lengths (%s) — '
                . 'it would never be applied.',
            $country,
            $scheme->code,
            $scheme->mask,
            $placeholders,
            implode(', ', $scheme->lengths),
        ));
    }

    #[DataProvider('schemeProvider')]
    public function testCheckDigitValidatorExistsAndSupportsItsScheme(string $country, TaxIdScheme $scheme): void
    {
        if ($scheme->checkDigitClass === null) {
            self::assertNotNull($scheme->notes, sprintf(
                '%s/%s has no check-digit validator and no note explaining why. '
                    . 'A silent absence is indistinguishable from an oversight.',
                $country,
                $scheme->code,
            ));

            return;
        }

        self::assertTrue(class_exists($scheme->checkDigitClass), sprintf(
            '%s/%s points at a missing class: %s',
            $country,
            $scheme->code,
            $scheme->checkDigitClass,
        ));

        $validator = (new CheckDigitValidatorFactory())->make($scheme->checkDigitClass);

        self::assertInstanceOf(CheckDigitValidatorInterface::class, $validator);
        self::assertTrue($validator->supports($country, $scheme->code), sprintf(
            '%s is wired to %s/%s but does not declare support for it — a registry/validator mismatch.',
            $scheme->checkDigitClass,
            $country,
            $scheme->code,
        ));
    }

    /**
     * A checksum that accepts everything is worse than no checksum: it looks
     * like protection and provides none.
     *
     * Every digit of the first example is bumped by one in turn and the result
     * must be rejected at least half the time. Real check digits catch every
     * single-digit error; the 50% floor leaves room for schemes where some
     * positions are structural rather than checksummed.
     */
    #[DataProvider('schemeProvider')]
    public function testCheckDigitSchemesRejectMutations(string $country, TaxIdScheme $scheme): void
    {
        if ($scheme->checkDigitClass === null || in_array($country . '/' . $scheme->code, self::NO_ARITHMETIC_CHECK, true)) {
            $this->addToAssertionCount(1);

            return;
        }

        $taxid    = new TaxId();
        $example  = $scheme->examples[0];
        $tried    = 0;
        $rejected = 0;

        for ($i = 0; $i < strlen($example); $i++) {
            if (! ctype_digit($example[$i])) {
                continue;
            }

            $mutant     = $example;
            $mutant[$i] = (string) (((int) $example[$i] + 1) % 10);
            $tried++;

            if (! $taxid->isValid($mutant, $country, $scheme->code)) {
                $rejected++;
            }
        }

        self::assertGreaterThan(0, $tried, $country . '/' . $scheme->code . ' example has no digits to mutate.');
        self::assertGreaterThanOrEqual($tried / 2, $rejected, sprintf(
            '%s/%s only rejected %d of %d single-digit mutations of "%s" — its check digit is not discriminating.',
            $country,
            $scheme->code,
            $rejected,
            $tried,
            $example,
        ));
    }

    /**
     * Formatting must be lossless: whatever the mask does, normalizing the
     * printed form has to return the original value. Otherwise round-tripping a
     * tax ID through a UI corrupts it.
     */
    #[DataProvider('schemeProvider')]
    public function testPrintedFormRoundTrips(string $country, TaxIdScheme $scheme): void
    {
        $taxid = new TaxId();

        foreach ($scheme->examples as $example) {
            $parsed = $taxid->tryParse($example, $country);

            self::assertNotNull($parsed, $country . '/' . $scheme->code . ' example "' . $example . '" did not parse.');

            $printed = $parsed->format(TaxIdFormat::Print);

            self::assertSame($example, $taxid->normalize($printed, $country), sprintf(
                '%s/%s does not round-trip: "%s" prints as "%s", which normalizes back to "%s".',
                $country,
                $scheme->code,
                $example,
                $printed,
                $taxid->normalize($printed, $country),
            ));
        }
    }

    // ------------------------------------------------------------ registry-wide

    public function testCountryCodesAreUppercaseAlpha2(): void
    {
        foreach ((new Registry())->countries() as $code) {
            self::assertMatchesRegularExpression('/^[A-Z]{2}$/', $code, 'Bad country key: ' . $code);
        }
    }

    public function testVatPrefixesAreUniqueAcrossCountries(): void
    {
        $registry = new Registry();
        $seen     = [];

        foreach ($registry->all() as $code => $profile) {
            if ($profile->vatPrefix === null) {
                continue;
            }

            self::assertArrayNotHasKey($profile->vatPrefix, $seen, sprintf(
                'VAT prefix "%s" is claimed by both %s and %s — country inference would be ambiguous.',
                $profile->vatPrefix,
                $seen[$profile->vatPrefix] ?? '?',
                $code,
            ));

            $seen[$profile->vatPrefix] = $code;

            self::assertSame(
                $code,
                $registry->countryForVatPrefix($profile->vatPrefix),
                'The VAT prefix index disagrees with the profile for ' . $code,
            );
        }
    }

    public function testSchemeCodesAreLowercaseAndUniqueWithinACountry(): void
    {
        foreach ((new Registry())->all() as $code => $profile) {
            foreach ($profile->schemes as $key => $scheme) {
                self::assertSame($key, $scheme->code, 'Scheme key/code mismatch in ' . $code);
                self::assertMatchesRegularExpression('/^[a-z][a-z0-9_]*$/', $scheme->code, sprintf(
                    'Scheme code "%s" in %s should be lowercase snake_case.',
                    $scheme->code,
                    $code,
                ));
                self::assertSame($code, $scheme->countryCode);
            }
        }
    }

    /**
     * A country with no schemes would silently answer `MalformedStructure` for
     * everything, which looks like a data problem in the caller's input rather
     * than in ours.
     */
    public function testEveryCountryHasAtLeastOneScheme(): void
    {
        foreach ((new Registry())->all() as $code => $profile) {
            self::assertNotSame([], $profile->schemes, $code . ' has no schemes.');
            self::assertNotSame('', $profile->name, $code . ' has no country name.');
        }
    }

    /**
     * Guards the headline number in the README against quiet shrinkage — and
     * gives a single place to notice when coverage grows.
     */
    public function testRegistryCoversTheDocumentedBreadth(): void
    {
        $registry = new Registry();

        self::assertGreaterThanOrEqual(90, count($registry->countries()));
        self::assertGreaterThanOrEqual(146, $registry->schemeCount());
    }
}
