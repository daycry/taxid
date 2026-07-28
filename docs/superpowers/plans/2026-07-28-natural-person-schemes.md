# Natural-Person Tax ID Schemes Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add nine natural-person tax ID schemes to eight countries that currently carry only their corporate identifier, so a person's number in Austria, Finland, France, Hungary, Lithuania, Luxembourg, Malta or Norway stops being rejected.

**Architecture:** Each scheme is a data entry in `src/Registry/data/countries.php` plus, where a check digit exists, one small stateless class in `src/National/` extending `AbstractNationalValidator`. Two shared-core additions come first: a `Verhoeff` checksum primitive (Luxembourg's thirteenth digit) and one extra separator in `Normalizer` (Finland's 1800s century marker). The data-integrity test in `tests/Registry/CountriesDataIntegrityTest.php` is the gate — it runs every bundled example end to end through the public facade and mutates every digit, so no task can land a broken country.

**Tech Stack:** PHP 8.3+, PHPUnit 11, PHPStan level 8 (no baseline), PHP-CS-Fixer (PSR-12 + `strict_types`). No new dependencies.

**Spec:** [../specs/2026-07-28-natural-person-schemes-design.md](../specs/2026-07-28-natural-person-schemes-design.md)

## Global Constraints

- `declare(strict_types=1);` in every PHP file.
- PHP `^8.3`. Typed class constants (`private const array WEIGHTS = [...]`, `private const string ALPHABET = '...'`) are the house style and require 8.3.
- **Nothing in `src/Core`, `src/National` or `src/Registry` may reference `CodeIgniter\` or `codeigniter4`.** `tests/Architecture/CoreIsFrameworkFreeTest.php` scans those directories and fails on a hit.
- Every validator: `final`, extends `AbstractNationalValidator`, **stateless**, **never throws** — anything it dislikes is a `false`.
- Every `pattern` is an anchored PCRE applied to the **compact** form: uppercase, separators stripped, VAT prefix removed.
- Every scheme with no `check_digit` **must** carry a `notes` entry, or `testCheckDigitValidatorExistsAndSupportsItsScheme` fails.
- Every `mask`'s `#` count must equal one of the scheme's declared `lengths`.
- Imports in `src/Registry/data/countries.php` are **alphabetically ordered**; PHP-CS-Fixer enforces it.
- Bundled examples are **synthetic and checksum-valid**. Every value in this plan was computed with the algorithm and verified before the plan was written; do not substitute your own.
- **PHP's `%` is signed.** `(11 - $sum) % 11` yields a negative number when `$sum > 11`, where Python's yields a positive one. Reference implementations are Python. Use `(11 - $sum % 11) % 11`, or better, the existing `Weights::mod11ComplementDigit()`.
- Run `composer test`, `composer analyze` and `composer cs` before every commit. All three must be clean.

---

## File Structure

**Created (9 files):**

| File | Responsibility |
| --- | --- |
| `src/Core/Checksum/Verhoeff.php` | Verhoeff check digit over the dihedral group D₅. Fifth shared primitive. |
| `src/National/AtSteuernummerValidator.php` | Austrian Steuernummer — a length guard over `Luhn`. |
| `src/National/FiHenkilotunnusValidator.php` | Finnish henkilötunnus — mod 31 into a 31-character alphabet. |
| `src/National/FrSpiValidator.php` | French SPI — three-digit key, first ten digits mod 511. |
| `src/National/HuAdoazonositoValidator.php` | Hungarian adóazonosító jel — positional mod 11. |
| `src/National/LtAsmensKodasValidator.php` | Lithuanian asmens kodas — two-pass mod 11. |
| `src/National/LuMatriculeValidator.php` | Luxembourg matricule — Luhn **and** Verhoeff over the same eleven digits. |
| `src/National/NoFodselsnummerValidator.php` | Norwegian fødselsnummer — two mod-11 digits, D-numbers accepted. |
| `docs/superpowers/plans/2026-07-28-natural-person-schemes.md` | This plan. |

**Modified (7 files):**

| File | Change |
| --- | --- |
| `src/Core/Normalizer.php` | `+` joins the separator character class. |
| `src/Registry/data/countries.php` | Nine scheme entries, seven new imports. |
| `tests/Core/ChecksumTest.php` | Verhoeff cases; the class docblock says "four classes" and must say five. |
| `tests/Core/NormalizerTest.php` | `+` stripping. |
| `tests/National/EuropeValidatorsTest.php` | Nine `yield` rows plus four country-specific branch tests. |
| `tests/Registry/CountriesDataIntegrityTest.php` | The documented scheme floor, `130` → `145`. |
| `docs/countries.md`, `docs/algorithms.md`, `README.md`, `CLAUDE.md`, `CHANGELOG.md` | Coverage counters, provenance, changelog. |

**Not modified, deliberately:** `NO_ARITHMETIC_CHECK` in the integrity test. Malta's two schemes declare no `check_digit` class at all, and the mutation test already exempts those — verified: it short-circuits on `$scheme->checkDigitClass === null` before consulting the list.

---

### Task 1: The Verhoeff primitive

Luxembourg's thirteenth digit is a Verhoeff check digit and nothing in `src/Core/Checksum/` can produce one. `Iso7064` is a different family and cannot be bent into it.

**Files:**
- Create: `src/Core/Checksum/Verhoeff.php`
- Modify: `tests/Core/ChecksumTest.php` (add cases; fix the "four classes" docblock at line 13-18)

**Interfaces:**
- Consumes: nothing.
- Produces: `Daycry\TaxId\Core\Checksum\Verhoeff::isValid(string $digits): bool` — `$digits` **includes** its trailing check digit. `Daycry\TaxId\Core\Checksum\Verhoeff::checkDigit(string $payload): int` — `$payload` is **without** it. Deliberately the same two names and meanings as `Luhn`, so a reader who knows one needs no orientation. Task 8 consumes `checkDigit()`.

- [ ] **Step 1: Write the failing tests**

Append to `tests/Core/ChecksumTest.php`, and add `use Daycry\TaxId\Core\Checksum\Verhoeff;` to the import block (alphabetically: after `Mod97`, before `Weights`).

```php
    /**
     * The canonical worked example from the literature: the Verhoeff check digit
     * for 236 is 3. Pinning an externally-stated value matters more here than
     * anywhere else in this class — a Verhoeff implementation can be built
     * inverted and still pass every self-consistency test you throw at it.
     */
    public function testVerhoeffMatchesTheCanonicalReferenceValue(): void
    {
        self::assertSame(3, Verhoeff::checkDigit('236'));
        self::assertTrue(Verhoeff::isValid('2363'));
        self::assertFalse(Verhoeff::isValid('2364'));
    }

    public function testVerhoeffCheckDigitCompletesAnyPayload(): void
    {
        foreach (['1', '19900101123', '000', '999999999999'] as $payload) {
            self::assertTrue(
                Verhoeff::isValid($payload . Verhoeff::checkDigit($payload)),
                'Verhoeff rejected the check digit it generated for ' . $payload,
            );
        }
    }

    public function testVerhoeffRejectsEverySingleDigitError(): void
    {
        $valid = '19900101123' . Verhoeff::checkDigit('19900101123');

        for ($i = 0; $i < strlen($valid); $i++) {
            $mutant     = $valid;
            $mutant[$i] = (string) (((int) $valid[$i] + 1) % 10);

            self::assertFalse(Verhoeff::isValid($mutant), 'Verhoeff accepted a single-digit error at position ' . $i);
        }
    }

    /**
     * The property Verhoeff exists for, and the reason Luxembourg pairs it with a
     * Luhn digit rather than a second Luhn digit: Luhn misses adjacent
     * transpositions whose digits differ by 5 (09 <-> 90), Verhoeff misses none.
     * This test asserts both halves, because the contrast is the justification.
     */
    public function testVerhoeffCatchesEveryAdjacentTranspositionAndLuhnDoesNot(): void
    {
        $luhnMissed = 0;

        for ($n = 1000000; $n < 1001000; $n++) {
            $base      = (string) $n;
            $verhoeff  = $base . Verhoeff::checkDigit($base);
            $luhn      = $base . Luhn::checkDigit($base);

            for ($i = 0; $i < strlen($verhoeff) - 1; $i++) {
                if ($verhoeff[$i] === $verhoeff[$i + 1]) {
                    continue;
                }

                $swapped                          = $verhoeff;
                [$swapped[$i], $swapped[$i + 1]]  = [$swapped[$i + 1], $swapped[$i]];

                self::assertFalse(Verhoeff::isValid($swapped), 'Verhoeff missed a transposition in ' . $verhoeff);
            }

            for ($i = 0; $i < strlen($luhn) - 1; $i++) {
                if ($luhn[$i] === $luhn[$i + 1]) {
                    continue;
                }

                $swapped                         = $luhn;
                [$swapped[$i], $swapped[$i + 1]] = [$swapped[$i + 1], $swapped[$i]];

                if (Luhn::isValid($swapped)) {
                    $luhnMissed++;
                }
            }
        }

        self::assertGreaterThan(0, $luhnMissed, 'Luhn is expected to miss some transpositions; if it misses none, this comparison is broken.');
    }

    public function testVerhoeffRejectsNonDigits(): void
    {
        self::assertFalse(Verhoeff::isValid(''));
        self::assertFalse(Verhoeff::isValid('236X'));
    }
```

Also change the class docblock, currently:

```
 * Around sixty national validators sit on top
 * of these four classes, so a bug here is a bug in sixty countries at once —
```

to read `these five classes`.

- [ ] **Step 2: Run the tests to verify they fail**

```bash
vendor/bin/phpunit tests/Core/ChecksumTest.php
```

Expected: FAIL — `Error: Class "Daycry\TaxId\Core\Checksum\Verhoeff" not found`.

- [ ] **Step 3: Write the implementation**

Create `src/Core/Checksum/Verhoeff.php`:

```php
<?php

declare(strict_types=1);

namespace Daycry\TaxId\Core\Checksum;

/**
 * The Verhoeff check-digit algorithm (1969), over the dihedral group D₅.
 *
 * Luxembourg's matricule uses it for the thirteenth digit, with a {@see Luhn}
 * digit in the twelfth, and that pairing is the whole point: Luhn misses an
 * adjacent transposition whose two digits differ by five (`09` ↔ `90`,
 * `27` ↔ `72`), while Verhoeff misses none at all. Two digits from two families
 * catch strictly more than two digits from one.
 *
 * What buys that is non-commutative multiplication, which is also why the
 * tables cannot be rewritten as arithmetic: {@see Luhn} and {@see Weights} both
 * work in ℤ/10ℤ, where a transposition error can cancel itself out.
 *
 * Pure and total. A non-digit character contributes `0` rather than raising,
 * the same convention {@see Weights} documents, because callers hand over a
 * value that already matched its scheme's pattern.
 */
final class Verhoeff
{
    /**
     * Multiplication table of D₅ — five rotations then five reflections. Not
     * symmetric, and that asymmetry is the algorithm.
     *
     * @var int[][]
     */
    private const array D = [
        [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
        [1, 2, 3, 4, 0, 6, 7, 8, 9, 5],
        [2, 3, 4, 0, 1, 7, 8, 9, 5, 6],
        [3, 4, 0, 1, 2, 8, 9, 5, 6, 7],
        [4, 0, 1, 2, 3, 9, 5, 6, 7, 8],
        [5, 9, 8, 7, 6, 0, 4, 3, 2, 1],
        [6, 5, 9, 8, 7, 1, 0, 4, 3, 2],
        [7, 6, 5, 9, 8, 2, 1, 0, 4, 3],
        [8, 7, 6, 5, 9, 3, 2, 1, 0, 4],
        [9, 8, 7, 6, 5, 4, 3, 2, 1, 0],
    ];

    /**
     * The permutation applied at each position, cycling with period 8.
     *
     * @var int[][]
     */
    private const array P = [
        [0, 1, 2, 3, 4, 5, 6, 7, 8, 9],
        [1, 5, 7, 6, 2, 8, 3, 0, 9, 4],
        [5, 8, 0, 3, 7, 9, 6, 1, 4, 2],
        [8, 9, 1, 6, 0, 4, 3, 5, 2, 7],
        [9, 4, 5, 3, 1, 2, 6, 8, 7, 0],
        [4, 2, 8, 6, 5, 7, 3, 9, 0, 1],
        [2, 7, 9, 3, 8, 0, 6, 4, 1, 5],
        [7, 0, 4, 6, 9, 1, 3, 2, 5, 8],
    ];

    /**
     * Multiplicative inverses in D₅: only 1↔4 and 2↔3 move.
     *
     * @var int[]
     */
    private const array INV = [0, 4, 3, 2, 1, 5, 6, 7, 8, 9];

    /**
     * Whether `$digits` (payload **including** its trailing check digit) satisfies
     * the Verhoeff condition.
     */
    public static function isValid(string $digits): bool
    {
        if ($digits === '' || ! ctype_digit($digits)) {
            return false;
        }

        return self::interim($digits, 0) === 0;
    }

    /**
     * The check digit that makes `$payload` (given **without** it) Verhoeff-valid.
     */
    public static function checkDigit(string $payload): int
    {
        return self::INV[self::interim($payload, 1)];
    }

    /**
     * Fold the digits right to left through the permutation and then the group.
     *
     * `$offset` is what separates the two operations: `0` verifies a value whose
     * check digit is already in place, `1` shifts every permutation up one so
     * position zero stays free for the digit about to be appended.
     */
    private static function interim(string $digits, int $offset): int
    {
        $carry  = 0;
        $length = strlen($digits);

        for ($i = 0; $i < $length; $i++) {
            $digit = (int) $digits[$length - 1 - $i];
            $carry = self::D[$carry][self::P[($i + $offset) % 8][$digit]];
        }

        return $carry;
    }
}
```

- [ ] **Step 4: Run the tests to verify they pass**

```bash
vendor/bin/phpunit tests/Core/ChecksumTest.php
composer analyze
composer cs
```

Expected: PASS, PHPStan clean, no style diff.

Two failures are foreseeable, and both have a known cause:

- **`checkDigit('236')` returns something other than 3.** The `$offset` argument to `interim()` is
  reversed: verifying passes `0`, generating passes `1`. This is the single mistake the canonical
  reference value exists to catch, and it is why that test came first.
- **PHPStan reports `Offset int might not exist on array{...}`** for `self::D[$carry]` or
  `self::P[...]`. PHPStan infers a constant *shape* from a literal array and cannot prove a computed
  int lands inside it. The `@var int[][]` docblocks on `D` and `P` above are what widen those to
  `array<int, array<int, int>>` and resolve it — keep them. Do **not** reach for
  `@phpstan-ignore`: `CLAUDE.md` forbids it without a written reason, and there is a real fix here.
  Verhoeff is the first 2D lookup table in this codebase, so there is no earlier precedent to copy;
  the one-dimensional string-offset equivalent is `EsDniValidator.php:48`, which is level-8 clean.

- [ ] **Step 5: Commit**

```bash
git add src/Core/Checksum/Verhoeff.php tests/Core/ChecksumTest.php
git commit -m "feat: Verhoeff check-digit primitive"
```

---

### Task 2: `+` becomes a separator

**Files:**
- Modify: `src/Core/Normalizer.php:33` (the `SEPARATORS` constant), and its class docblock at line 10-13
- Modify: `tests/Core/NormalizerTest.php`

**Interfaces:**
- Consumes: nothing.
- Produces: no signature change. Task 4 relies on the new behaviour: a Finnish henkilötunnus written with the 1800s marker `+` now normalizes to a ten-character compact form instead of dying at `IllegalCharacters`.

- [ ] **Step 1: Write the failing tests**

In `tests/Core/NormalizerTest.php`, add one provider row after `'strips underscores'`:

```php
        yield 'strips plus signs'           => ['010101+123A', '010101123A'];
```

and add this test, plus `use Daycry\TaxId\Enums\ViolationCode;` and `use Daycry\TaxId\TaxId;` to the imports:

```php
    /**
     * `+` is a separator because Finland makes it one: the century marker for
     * someone born in the 1800s is a literal plus, and IllegalCharacters is
     * checked before any scheme is consulted, so without this the character can
     * never reach a pattern.
     *
     * This widens every country, and that is safe in one direction only: a value
     * containing `+` is IllegalCharacters today, so nothing that currently
     * validates can change meaning.
     */
    public function testPlusNoLongerTripsIllegalCharacters(): void
    {
        $result = (new TaxId())->validate('1234+5678Z', 'ES');

        self::assertNotSame(ViolationCode::IllegalCharacters, $result->errorCode());
    }
```

- [ ] **Step 2: Run the tests to verify they fail**

```bash
vendor/bin/phpunit tests/Core/NormalizerTest.php
```

Expected: FAIL twice — the provider row returns `010101+123A`, and `errorCode()` is `ViolationCode::IllegalCharacters`.

- [ ] **Step 3: Write the implementation**

In `src/Core/Normalizer.php`, add `+` to the character class:

```php
    private const SEPARATORS = '/[\s.\-+\/\\\\_()\[\]:,\']+/';
```

Inside a character class `+` needs no escaping, and it must not go immediately after `\-` in a way that could read as a range — placing it after the `\-` as written above is a literal, since a range would need a character on both sides of a hyphen.

Update the docblock's separator list in the same file, currently:

```
 * separators humans actually type into a tax ID — `.`, `-`, `/`, `\`, `_`,
 * `(`, `)`, `[`, `]`, `:`, `,`, `'`.
```

to include `+`:

```
 * separators humans actually type into a tax ID — `.`, `-`, `+`, `/`, `\`, `_`,
 * `(`, `)`, `[`, `]`, `:`, `,`, `'`. The `+` earns its place through Finland,
 * whose henkilötunnus uses it as the century marker for the 1800s.
```

- [ ] **Step 4: Run the whole suite**

```bash
composer test
composer analyze
composer cs
```

Expected: all green. This step runs the **whole** suite rather than one file on purpose: it is the only change in this plan that alters behaviour for countries it does not mention, and a surprise here is worth finding now. Watch in particular for `tests/Core/ValidatorTest.php`, which pins one violation code per input.

- [ ] **Step 5: Commit**

```bash
git add src/Core/Normalizer.php tests/Core/NormalizerTest.php
git commit -m "feat: treat + as a separator during normalization"
```

---

### Task 3: Austria — Steuernummer

**Files:**
- Create: `src/National/AtSteuernummerValidator.php`
- Modify: `src/Registry/data/countries.php` (import; the `'AT'` block at line 210-227)
- Modify: `tests/National/EuropeValidatorsTest.php`

**Interfaces:**
- Consumes: `Luhn::isValid(string): bool`, already in the codebase.
- Produces: scheme `AT/steuernummer`; class `Daycry\TaxId\National\AtSteuernummerValidator` with `verify(string $compact): bool`.

- [ ] **Step 1: Write the failing test**

In `tests/National/EuropeValidatorsTest.php::schemeProvider()`, next to the existing `'AT UID'` row (line 75):

```php
        yield 'AT Steuernummer' => ['AT', 'steuernummer', ['123456782', '591199013'], ['123456783', '12345678', '1234567820']];
```

- [ ] **Step 2: Run it to verify it fails**

```bash
vendor/bin/phpunit tests/National/EuropeValidatorsTest.php --filter "AT Steuernummer"
```

Expected: FAIL — `AT/steuernummer should accept "123456782"`, because the country has no such scheme and the violation is `scheme_mismatch`.

- [ ] **Step 3: Write the validator**

Create `src/National/AtSteuernummerValidator.php`:

```php
<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Core\Checksum\Luhn;

/**
 * Austria — Steuernummer, the nine-digit Abgabenkontonummer, written `12-345/6782`.
 *
 * Published as its own rule: map every digit in an odd position through
 * `0246813579`, sum, and the check digit is `(10 - sum) mod 10`. That table is
 * "double and subtract nine if it passes nine", and for nine digits with the
 * check last, the odd positions are exactly the ones Luhn doubles — so the
 * Austrian check *is* Luhn. Verified as identical over 100 000 consecutive
 * bodies before this class was written; it is not an approximation.
 *
 * The first two digits are the issuing Finanzamt. Austria publishes the list of
 * valid office codes, and this class deliberately does not check against it:
 * that is data that expires, and its absence costs the checksum nothing.
 */
final class AtSteuernummerValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['AT'];
    protected const array SCHEMES   = ['steuernummer'];

    public function verify(string $compact): bool
    {
        return strlen($compact) === 9 && Luhn::isValid($compact);
    }
}
```

- [ ] **Step 4: Add the registry entry**

In `src/Registry/data/countries.php`, add the import in alphabetical order — `AtSteuernummerValidator` sorts **before** `AtUidValidator`:

```php
use Daycry\TaxId\National\AtSteuernummerValidator;
use Daycry\TaxId\National\AtUidValidator;
```

Then append this scheme to the `'AT'` block, **after** the existing `uid` entry:

```php
            [
                'code'               => 'steuernummer',
                'name'               => 'Steuernummer — Abgabenkontonummer',
                'entity'             => 'both',
                'vat'                => false,
                'pattern'            => '/^\d{9}$/',
                'lengths'            => [9],
                'mask'               => '##-###/####',
                'check_digit'        => AtSteuernummerValidator::class,
                'check_digit_offset' => [-1, 1],
                'examples'           => ['123456782'],
                'notes'              => 'Issued to individuals and companies alike, which is why the entity is "both". The first two digits are the Finanzamt code and are not validated. The check digit is Luhn.',
            ],
```

`uid` stays first: it is the more specific shape (`U` then eight digits), and nothing here overlaps it, so detection order for existing values is untouched.

- [ ] **Step 5: Run the tests to verify they pass**

```bash
vendor/bin/phpunit tests/National/EuropeValidatorsTest.php --filter "AT Steuernummer"
vendor/bin/phpunit tests/Registry
```

Expected: both PASS. The integrity test now exercises `AT/steuernummer` — the example through the facade, the mask round-trip (`12-345/6782` normalizes back), and nine of nine single-digit mutations rejected.

- [ ] **Step 6: Commit**

```bash
composer test && composer analyze && composer cs
git add src/National/AtSteuernummerValidator.php src/Registry/data/countries.php tests/National/EuropeValidatorsTest.php
git commit -m "feat: Austrian Steuernummer"
```

---

### Task 4: Finland — henkilötunnus

The one scheme with two legal compact lengths. Read §4.2 of the spec before starting: the century marker is stripped by normalization when it is `-` or `+` and survives when it is a letter, so ten and eleven characters are both correct and the marker never enters the checksum.

**Files:**
- Create: `src/National/FiHenkilotunnusValidator.php`
- Modify: `src/Registry/data/countries.php` (import; the `'FI'` block at line 858-875)
- Modify: `tests/National/EuropeValidatorsTest.php`

**Interfaces:**
- Consumes: the `+` separator from Task 2.
- Produces: scheme `FI/henkilotunnus`; class `Daycry\TaxId\National\FiHenkilotunnusValidator`.

- [ ] **Step 1: Write the failing tests**

In `schemeProvider()`, next to `'FI Y-tunnus'` (line 82):

```php
        yield 'FI henkilötunnus' => [
            'FI',
            'henkilotunnus',
            ['010190123M', '010106A123Y', '290280123X'],
            ['010190123N', '010190123G', '011390123M', '01019012345', '000190123M'],
        ];
```

And a dedicated branch test, in the `country-specific branches` section of the same file:

```php
    /**
     * Finland writes the century between the date and the serial: `+` for the
     * 1800s, `-` or U-Y for the 1900s, A-F for the 2000s. Normalization strips
     * the punctuation forms and keeps the letters, so the same person's identity
     * code legitimately arrives as ten characters or eleven. The checksum never
     * sees the marker, so both must validate.
     */
    public function testFinnishCenturyMarkerMayBeStrippedOrKept(): void
    {
        // 1900s, punctuation marker: stripped, ten characters.
        self::assertTrue($this->taxid->isValid('010190-123M', 'FI', 'henkilotunnus'));
        // 1800s, punctuation marker: stripped too — the reason `+` is a separator.
        self::assertTrue($this->taxid->isValid('010190+123M', 'FI', 'henkilotunnus'));
        // 2000s, letter marker: kept, eleven characters, same arithmetic.
        self::assertTrue($this->taxid->isValid('010106A123Y', 'FI', 'henkilotunnus'));

        // The marker is not a wildcard: a letter that marks no century is refused.
        self::assertFalse($this->taxid->isValid('010106G123Y', 'FI', 'henkilotunnus'));
    }

    /**
     * The control alphabet has 31 characters and omits G, I, O, Q and Z, which
     * are the ones a reader confuses with 6, 1, 0, 0 and 2. A value ending in
     * one of them is not a mistyped check character — it is not a henkilötunnus.
     */
    public function testFinnishControlAlphabetOmitsTheConfusableLetters(): void
    {
        self::assertTrue($this->taxid->isValid('290280123X', 'FI', 'henkilotunnus'));

        foreach (['G', 'I', 'O', 'Q', 'Z'] as $absent) {
            self::assertFalse(
                $this->taxid->isValid('290280123' . $absent, 'FI', 'henkilotunnus'),
                $absent . ' is not in the control alphabet and must not be accepted.',
            );
        }
    }
```

- [ ] **Step 2: Run them to verify they fail**

```bash
vendor/bin/phpunit tests/National/EuropeValidatorsTest.php --filter "Finnish|henkilötunnus"
```

Expected: FAIL — no such scheme.

- [ ] **Step 3: Write the validator**

Create `src/National/FiHenkilotunnusValidator.php`:

```php
<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

/**
 * Finland — henkilötunnus, the personal identity code, `DDMMYYCZZZQ`.
 *
 * The nine digits of the date and the serial, read as one integer, modulo 31,
 * indexed into a 31-character alphabet. `C` is the century marker and takes no
 * part in it: `+` for the 1800s, `-` or `U`-`Y` for the 1900s, `A`-`F` for the
 * 2000s.
 *
 * Which is why this validator accepts two lengths. Normalization strips `+` and
 * `-`, leaving ten characters; a letter marker survives, leaving eleven. Both
 * are the same code and produce the same check character.
 *
 * Serials 900-999 mark temporary identifiers. They are really issued, so they
 * are accepted; rejecting them would be inventing a rule.
 */
final class FiHenkilotunnusValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['FI'];
    protected const array SCHEMES   = ['henkilotunnus'];

    /**
     * G, I, O, Q and Z are absent on purpose — they are the characters a reader
     * mistakes for 6, 1, 0, 0 and 2.
     */
    private const string ALPHABET = '0123456789ABCDEFHJKLMNPRSTUVWXY';

    public function verify(string $compact): bool
    {
        $length = strlen($compact);

        if ($length !== 10 && $length !== 11) {
            return false;
        }

        // Eleven characters means the century marker survived normalization, so
        // it sits at index 6 and is skipped; ten means it was punctuation.
        $body = $length === 11
            ? substr($compact, 0, 6) . substr($compact, 7, 3)
            : substr($compact, 0, 9);

        if (! ctype_digit($body)) {
            return false;
        }

        $day   = (int) substr($body, 0, 2);
        $month = (int) substr($body, 2, 2);

        // A range check, not a calendar: the house convention, since a wrong
        // month means the value is not a henkilötunnus rather than mistyped.
        if ($day < 1 || $day > 31 || $month < 1 || $month > 12) {
            return false;
        }

        return self::ALPHABET[((int) $body) % 31] === $compact[$length - 1];
    }
}
```

`self::ALPHABET[((int) $body) % 31]` — a computed offset into a string constant — is the same shape as `EsDniValidator.php:48` (`self::LETTERS[((int) $body) % 23]`), which is PHPStan level-8 clean, so no annotation is needed here.

- [ ] **Step 4: Add the registry entry**

Import, alphabetically **before** `FiYtunnusValidator`:

```php
use Daycry\TaxId\National\FiHenkilotunnusValidator;
use Daycry\TaxId\National\FiYtunnusValidator;
```

Append to the `'FI'` block after `ytunnus`:

```php
            [
                'code'               => 'henkilotunnus',
                'name'               => 'Henkilötunnus — personal identity code',
                'entity'             => 'natural',
                'vat'                => false,
                'pattern'            => '/^\d{6}[A-FU-Y]?\d{3}[0-9A-FHJ-NPR-Y]$/',
                'lengths'            => [10, 11],
                'mask'               => '######-####',
                'check_digit'        => FiHenkilotunnusValidator::class,
                'check_digit_offset' => [-1, 1],
                'examples'           => ['010190123M', '010106A123Y'],
                'notes'              => 'The century marker between the date and the serial is + (1800s), - or U-Y (1900s), or A-F (2000s). Normalization strips + and -, so the compact form is ten characters for those and eleven for a letter marker; the marker never enters the checksum. The control alphabet omits G, I, O, Q and Z. Temporary serials 900-999 are accepted.',
            ],
```

The ten-character example goes **first** and that is load-bearing: `testPatternCompilesAndMatchesEveryExample` and `testCheckDigitSchemesRejectMutations` only look at `examples[0]`, and the ten-character form is the one the mask covers.

- [ ] **Step 5: Run the tests to verify they pass**

```bash
vendor/bin/phpunit tests/National/EuropeValidatorsTest.php --filter "Finnish|henkilötunnus"
vendor/bin/phpunit tests/Registry
```

Expected: PASS. Nine of nine mutations rejected on `010190123M`; the mask prints `010190-123M` and normalizes back.

- [ ] **Step 6: Commit**

```bash
composer test && composer analyze && composer cs
git add src/National/FiHenkilotunnusValidator.php src/Registry/data/countries.php tests/National/EuropeValidatorsTest.php
git commit -m "feat: Finnish henkilotunnus"
```

---

### Task 5: France — SPI

**Files:**
- Create: `src/National/FrSpiValidator.php`
- Modify: `src/Registry/data/countries.php` (import; the `'FR'` block at line 877-918)
- Modify: `tests/National/EuropeValidatorsTest.php`

**Interfaces:**
- Consumes: nothing.
- Produces: scheme `FR/spi`; class `Daycry\TaxId\National\FrSpiValidator`.

- [ ] **Step 1: Write the failing test**

In `schemeProvider()`, after the `'FR TVA'` row (line 55):

```php
        yield 'FR SPI' => ['FR', 'spi', ['1234567890066', '0123456789211'], ['1234567890067', '4234567890066', '123456789006']];
```

- [ ] **Step 2: Run it to verify it fails**

```bash
vendor/bin/phpunit tests/National/EuropeValidatorsTest.php --filter "FR SPI"
```

Expected: FAIL — no such scheme.

- [ ] **Step 3: Write the validator**

Create `src/National/FrSpiValidator.php`:

```php
<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

/**
 * France — numéro fiscal de référence, the thirteen-digit SPI printed on an
 * avis d'imposition.
 *
 * The last three digits are a control key: the first ten, read as one number,
 * modulo 511. The first digit is 0, 1, 2 or 3.
 *
 * The remainder is accumulated digit by digit rather than through a cast. Ten
 * digits exceed what a 32-bit platform's integer holds, and this namespace
 * already carries {@see \Daycry\TaxId\Core\Checksum\Mod97} for exactly that
 * reason — no reason to add a second place that only works on 64-bit.
 */
final class FrSpiValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['FR'];
    protected const array SCHEMES   = ['spi'];

    private const int MODULUS = 511;

    public function verify(string $compact): bool
    {
        if (strlen($compact) !== 13 || ! ctype_digit($compact)) {
            return false;
        }

        $remainder = 0;

        for ($i = 0; $i < 10; $i++) {
            $remainder = ($remainder * 10 + (int) $compact[$i]) % self::MODULUS;
        }

        return sprintf('%03d', $remainder) === substr($compact, 10);
    }
}
```

- [ ] **Step 4: Add the registry entry**

Import, alphabetically between `FrSirenValidator` and `FrTvaValidator`:

```php
use Daycry\TaxId\National\FrSirenValidator;
use Daycry\TaxId\National\FrSpiValidator;
use Daycry\TaxId\National\FrTvaValidator;
```

Append to the `'FR'` block after the `siren` entry:

```php
            [
                'code'               => 'spi',
                'name'               => 'Numéro fiscal de référence (SPI)',
                'entity'             => 'natural',
                'vat'                => false,
                'pattern'            => '/^[0-3]\d{12}$/',
                'lengths'            => [13],
                'check_digit'        => FrSpiValidator::class,
                'check_digit_offset' => [-3, 3],
                'examples'           => ['1234567890066'],
                'notes'              => 'Also called the numéro fiscal, printed on the avis d\'imposition. The last three digits are the first ten modulo 511. No mask: the tax office prints it as one block.',
            ],
```

No mask, deliberately — France prints the SPI unspaced, and inventing a grouping no tax office uses is worse than printing the compact form.

- [ ] **Step 5: Run the tests to verify they pass**

```bash
vendor/bin/phpunit tests/National/EuropeValidatorsTest.php --filter "FR "
vendor/bin/phpunit tests/Registry
```

Expected: PASS, including the existing SIREN/SIRET/TVA rows — 13 digits collides with none of them (14, 11, 9).

- [ ] **Step 6: Commit**

```bash
composer test && composer analyze && composer cs
git add src/National/FrSpiValidator.php src/Registry/data/countries.php tests/National/EuropeValidatorsTest.php
git commit -m "feat: French SPI (numero fiscal de reference)"
```

---

### Task 6: Hungary — adóazonosító jel

**The one algorithm in this batch with no primary source** (spec §5.4): only a restatement of the NAV rule. Its `remainder === 10` branch is the part a second-hand statement most often drops, so the test row pins it.

**Files:**
- Create: `src/National/HuAdoazonositoValidator.php`
- Modify: `src/Registry/data/countries.php` (import; the `'HU'` block at line 1064-1092)
- Modify: `tests/National/EuropeValidatorsTest.php`

**Interfaces:**
- Consumes: `Weights::sum(string, int[]): int`, already in the codebase.
- Produces: scheme `HU/adoazonosito`; class `Daycry\TaxId\National\HuAdoazonositoValidator`.

- [ ] **Step 1: Write the failing tests**

In `schemeProvider()`, next to the two `'HU'` rows (lines 94-95):

```php
        yield 'HU adóazonosító' => ['HU', 'adoazonosito', ['8123456786', '8401234565'], ['8123456787', '8000000130', '7123456786', '812345678']];
```

And the branch test:

```php
    /**
     * Hungary's personal tax identifier always starts with 8, and a serial whose
     * weighted sum leaves a remainder of ten is never issued — ten cannot be
     * written in the single digit the format allows, and the office skips the
     * serial rather than folding it to zero. Folding it would validate numbers
     * that do not exist.
     */
    public function testHungarianPersonalIdRejectsTheUnwritableRemainder(): void
    {
        self::assertTrue($this->taxid->isValid('8123456786', 'HU', 'adoazonosito'));

        // 800000013 has a weighted sum leaving remainder 10, so no check digit
        // completes it — every ending must be refused.
        for ($digit = 0; $digit <= 9; $digit++) {
            self::assertFalse(
                $this->taxid->isValid('800000013' . $digit, 'HU', 'adoazonosito'),
                'A serial whose remainder is 10 was accepted with check digit ' . $digit,
            );
        }
    }
```

- [ ] **Step 2: Run them to verify they fail**

```bash
vendor/bin/phpunit tests/National/EuropeValidatorsTest.php --filter "Hungarian|adóazonosító"
```

Expected: FAIL — no such scheme.

- [ ] **Step 3: Write the validator**

Create `src/National/HuAdoazonositoValidator.php`:

```php
<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Core\Checksum\Weights;

/**
 * Hungary — adóazonosító jel, the ten-digit personal tax identifier NAV issues
 * to individuals. Not to be confused with the corporate adószám, which is
 * eleven digits and lives in this registry under its own scheme.
 *
 * Each of the first nine digits is multiplied by its own position — one, two,
 * three and so on — and the sum modulo eleven is the tenth digit. The value
 * always begins with 8, the code for a natural person.
 *
 * A remainder of ten is not folded to zero: it cannot be written in one digit,
 * so the office skips that serial entirely. Folding it here would accept
 * identifiers that were never issued, so it is rejected instead.
 *
 * Digits two to six encode the birth date as the number of days elapsed since
 * 1 January 1867. That is not decoded — nothing in the checksum depends on it,
 * and a date this validator cannot see is a date it cannot get wrong.
 */
final class HuAdoazonositoValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['HU'];
    protected const array SCHEMES   = ['adoazonosito'];

    private const array WEIGHTS = [1, 2, 3, 4, 5, 6, 7, 8, 9];

    public function verify(string $compact): bool
    {
        if (strlen($compact) !== 10 || ! ctype_digit($compact) || $compact[0] !== '8') {
            return false;
        }

        $remainder = Weights::sum(substr($compact, 0, 9), self::WEIGHTS) % 11;

        return $remainder !== 10 && $remainder === (int) $compact[9];
    }
}
```

- [ ] **Step 4: Add the registry entry**

Import, alphabetically **before** `HuVatValidator`:

```php
use Daycry\TaxId\National\HuAdoazonositoValidator;
use Daycry\TaxId\National\HuVatValidator;
```

Append to the `'HU'` block after `anum`:

```php
            [
                'code'               => 'adoazonosito',
                'name'               => 'Adóazonosító jel — personal tax identifier',
                'entity'             => 'natural',
                'vat'                => false,
                'pattern'            => '/^8\d{9}$/',
                'lengths'            => [10],
                'check_digit'        => HuAdoazonositoValidator::class,
                'check_digit_offset' => [-1, 1],
                'examples'           => ['8123456786'],
                'notes'              => 'The individual\'s identifier, distinct from the eleven-digit corporate adószám. Always begins with 8. Digits 2-6 encode the birth date as days elapsed since 1867-01-01, which is not decoded. A serial whose weighted sum leaves a remainder of 10 is never issued.',
            ],
```

- [ ] **Step 5: Run the tests to verify they pass**

```bash
vendor/bin/phpunit tests/National/EuropeValidatorsTest.php --filter "Hungarian|adóazonosító|HU "
vendor/bin/phpunit tests/Registry
```

Expected: PASS, including the existing `HU adószám` and `HU ANUM` rows — 10 digits collides with neither 11 nor 8.

- [ ] **Step 6: Commit**

```bash
composer test && composer analyze && composer cs
git add src/National/HuAdoazonositoValidator.php src/Registry/data/countries.php tests/National/EuropeValidatorsTest.php
git commit -m "feat: Hungarian adoazonosito jel"
```

---

### Task 7: Lithuania — asmens kodas

**Files:**
- Create: `src/National/LtAsmensKodasValidator.php`
- Modify: `src/Registry/data/countries.php` (import; the `'LT'` block at line 1346-1363)
- Modify: `tests/National/EuropeValidatorsTest.php`

**Interfaces:**
- Consumes: `Weights::sum(string, int[]): int`.
- Produces: scheme `LT/asmens_kodas`; class `Daycry\TaxId\National\LtAsmensKodasValidator`.

- [ ] **Step 1: Write the failing tests**

In `schemeProvider()`, next to `'LT PVM'` (line 119):

```php
        yield 'LT asmens kodas' => [
            'LT',
            'asmens_kodas',
            ['39001011237', '49001011238', '90000001235'],
            ['39001011238', '79001011237', '39013011237', '3900101123'],
        ];
```

And the branch test:

```php
    /**
     * Lithuania's leading digit encodes sex and century, and 9 marks a code
     * issued without a known birth date — for which the date fields carry no
     * meaning and must not be range-checked. `39013011237` is the case that
     * proves the check does something: its checksum is CORRECT and it is refused
     * purely because month 13 does not exist.
     */
    public function testLithuanianPersonalCodeChecksTheDateOnlyWhenThereIsOne(): void
    {
        self::assertTrue($this->taxid->isValid('39001011237', 'LT', 'asmens_kodas'));

        // Leading 9: no birth date, so nothing to validate but the checksum.
        self::assertTrue($this->taxid->isValid('90000001235', 'LT', 'asmens_kodas'));

        // Checksum correct, month impossible.
        self::assertFalse($this->taxid->isValid('39013011237', 'LT', 'asmens_kodas'));
    }
```

- [ ] **Step 2: Run them to verify they fail**

```bash
vendor/bin/phpunit tests/National/EuropeValidatorsTest.php --filter "Lithuanian|asmens"
```

Expected: FAIL — no such scheme.

- [ ] **Step 3: Write the validator**

Create `src/National/LtAsmensKodasValidator.php`:

```php
<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Core\Checksum\Weights;

/**
 * Lithuania — asmens kodas, the eleven-digit personal code, `GYYMMDDNNNC`.
 *
 * Two-pass weighted mod-11: weights 1…9,1 first and, when that produces the
 * unwritable 10, weights 3…9,1,2,3, with a second 10 folding to `0`. Identical
 * to Estonia's isikukood — the reference implementations share one function —
 * but kept as its own class rather than adding `LT` to
 * {@see EeIsikukoodValidator}: a shared class would make
 * `supports('EE', 'asmens_kodas')` answer true, and the eight lines of weights
 * are cheaper than a validator that claims schemes it has never heard of. The
 * same reason Sweden has three Luhn classes instead of one.
 *
 * `G` encodes sex and century (1/2 = 1800s, 3/4 = 1900s, 5/6 = 2000s). A
 * leading 9 marks a code issued without a known birth date, so the date fields
 * are only range-checked when there is a date to check.
 */
final class LtAsmensKodasValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['LT'];
    protected const array SCHEMES   = ['asmens_kodas'];

    private const array WEIGHTS_FIRST  = [1, 2, 3, 4, 5, 6, 7, 8, 9, 1];
    private const array WEIGHTS_SECOND = [3, 4, 5, 6, 7, 8, 9, 1, 2, 3];

    public function verify(string $compact): bool
    {
        if (strlen($compact) !== 11 || ! ctype_digit($compact)) {
            return false;
        }

        if ($compact[0] !== '9') {
            $month = (int) substr($compact, 3, 2);
            $day   = (int) substr($compact, 5, 2);

            if ($month < 1 || $month > 12 || $day < 1 || $day > 31) {
                return false;
            }
        }

        $body  = substr($compact, 0, 10);
        $check = Weights::sum($body, self::WEIGHTS_FIRST) % 11;

        if ($check === 10) {
            $check = Weights::sum($body, self::WEIGHTS_SECOND) % 11;
            $check = $check === 10 ? 0 : $check;
        }

        return $check === (int) $compact[10];
    }
}
```

- [ ] **Step 4: Add the registry entry**

Import, alphabetically **before** `LtPvmValidator`:

```php
use Daycry\TaxId\National\LtAsmensKodasValidator;
use Daycry\TaxId\National\LtPvmValidator;
```

Append to the `'LT'` block after `pvm`:

```php
            [
                'code'               => 'asmens_kodas',
                'name'               => 'Asmens kodas — personal code',
                'entity'             => 'natural',
                'vat'                => false,
                'pattern'            => '/^[1-69]\d{10}$/',
                'lengths'            => [11],
                'check_digit'        => LtAsmensKodasValidator::class,
                'check_digit_offset' => [-1, 1],
                'examples'           => ['39001011237'],
                'notes'              => 'The leading digit encodes sex and century (1/2 = 1800s, 3/4 = 1900s, 5/6 = 2000s); 9 marks a code issued without a known birth date, for which the date fields are not checked. Same two-pass mod-11 as Estonia\'s isikukood. Distinct from the 12-digit natural-person PVM code.',
            ],
```

- [ ] **Step 5: Run the tests to verify they pass**

```bash
vendor/bin/phpunit tests/National/EuropeValidatorsTest.php --filter "Lithuanian|asmens|LT "
vendor/bin/phpunit tests/Registry
```

Expected: PASS, including the existing `LT PVM` row — 11 digits collides with neither 9 nor 12.

- [ ] **Step 6: Commit**

```bash
composer test && composer analyze && composer cs
git add src/National/LtAsmensKodasValidator.php src/Registry/data/countries.php tests/National/EuropeValidatorsTest.php
git commit -m "feat: Lithuanian asmens kodas"
```

---

### Task 8: Luxembourg — matricule

The only consumer of Task 1. Two check digits from two different families over the **same** eleven-digit base — not eleven then twelve.

**Files:**
- Create: `src/National/LuMatriculeValidator.php`
- Modify: `src/Registry/data/countries.php` (import; the `'LU'` block at line 1365-1381)
- Modify: `tests/National/EuropeValidatorsTest.php`

**Interfaces:**
- Consumes: `Luhn::isValid(string): bool`; `Verhoeff::checkDigit(string): int` from Task 1.
- Produces: scheme `LU/matricule`; class `Daycry\TaxId\National\LuMatriculeValidator`.

- [ ] **Step 1: Write the failing tests**

In `schemeProvider()`, next to `'LU VAT'` (line 74):

```php
        yield 'LU matricule' => [
            'LU',
            'matricule',
            ['1990010112385', '1985031200757'],
            ['1990010112386', '1990010112395', '199001011238'],
        ];
```

And the branch test — the one that proves both digits are actually consulted:

```php
    /**
     * Luxembourg's matricule carries two check digits over the same eleven-digit
     * base: a Luhn digit in the twelfth position and a Verhoeff digit in the
     * thirteenth. Each invalid value here breaks exactly one of them, so an
     * implementation that checks only one digit — or that checks the same digit
     * twice — fails this test and passes everything else.
     */
    public function testLuxembourgMatriculeChecksBothDigitsIndependently(): void
    {
        self::assertTrue($this->taxid->isValid('1990010112385', 'LU', 'matricule'));

        // Luhn digit right, Verhoeff digit wrong.
        self::assertFalse($this->taxid->isValid('1990010112386', 'LU', 'matricule'));

        // Verhoeff digit right, Luhn digit wrong.
        self::assertFalse($this->taxid->isValid('1990010112395', 'LU', 'matricule'));
    }
```

- [ ] **Step 2: Run them to verify they fail**

```bash
vendor/bin/phpunit tests/National/EuropeValidatorsTest.php --filter "Luxembourg|matricule"
```

Expected: FAIL — no such scheme.

- [ ] **Step 3: Write the validator**

Create `src/National/LuMatriculeValidator.php`:

```php
<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Core\Checksum\Luhn;
use Daycry\TaxId\Core\Checksum\Verhoeff;

/**
 * Luxembourg — matricule, the thirteen-digit numéro d'identification national,
 * which is also an individual's TIN. Thirteen digits since 1 July 2014; the
 * older eleven-digit form is not this scheme.
 *
 * Structure is the birth date, a three-digit serial, then two check digits —
 * and both are computed over the **same** first eleven digits, not one over
 * eleven and the next over twelve. The twelfth is Luhn, the thirteenth is
 * Verhoeff. Two families rather than two of the same: Luhn is blind to an
 * adjacent transposition whose digits differ by five, and Verhoeff is not.
 *
 * The date is not validated. The two check digits already protect the value
 * well, and whether matricules are ever issued with a zero month or day is
 * unconfirmed — the same reasoning {@see LvPersonalCodeValidator} records for
 * Latvia's undated modern codes.
 */
final class LuMatriculeValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['LU'];
    protected const array SCHEMES   = ['matricule'];

    public function verify(string $compact): bool
    {
        if (strlen($compact) !== 13 || ! ctype_digit($compact)) {
            return false;
        }

        $base = substr($compact, 0, 11);

        return Luhn::isValid($base . $compact[11])
            && Verhoeff::checkDigit($base) === (int) $compact[12];
    }
}
```

- [ ] **Step 4: Add the registry entry**

Import, alphabetically **before** `LuVatValidator`:

```php
use Daycry\TaxId\National\LuMatriculeValidator;
use Daycry\TaxId\National\LuVatValidator;
```

Append to the `'LU'` block after `vat`:

```php
            [
                'code'               => 'matricule',
                'name'               => 'Matricule — numéro d\'identification national',
                'entity'             => 'natural',
                'vat'                => false,
                'pattern'            => '/^\d{13}$/',
                'lengths'            => [13],
                'mask'               => '#### ## ## ### ##',
                'check_digit'        => LuMatriculeValidator::class,
                'check_digit_offset' => [-2, 2],
                'examples'           => ['1990010112385'],
                'notes'              => 'Thirteen digits since 1 July 2014: the birth date, a three-digit serial, then two check digits — Luhn over the first eleven and Verhoeff over the same eleven. The older eleven-digit form is not accepted. The date is not validated.',
            ],
```

- [ ] **Step 5: Run the tests to verify they pass**

```bash
vendor/bin/phpunit tests/National/EuropeValidatorsTest.php --filter "Luxembourg|matricule|LU "
vendor/bin/phpunit tests/Registry
```

Expected: PASS. Thirteen of thirteen mutations rejected; the mask prints `1990 01 01 123 85` and normalizes back.

- [ ] **Step 6: Commit**

```bash
composer test && composer analyze && composer cs
git add src/National/LuMatriculeValidator.php src/Registry/data/countries.php tests/National/EuropeValidatorsTest.php
git commit -m "feat: Luxembourg matricule"
```

---

### Task 9: Malta — identity card number and non-national TIN

Two schemes, **no validator classes**: neither carries a checksum. `notes` is therefore mandatory on both, and the mutation test exempts them automatically because `checkDigitClass` is `null` — `NO_ARITHMETIC_CHECK` needs no entry.

**Files:**
- Modify: `src/Registry/data/countries.php` (the `'MT'` block at line 1512-1528; **no new imports**)
- Modify: `tests/National/EuropeValidatorsTest.php`

**Interfaces:**
- Consumes: nothing.
- Produces: schemes `MT/id_card` and `MT/tin`. No classes.

- [ ] **Step 1: Write the failing tests**

In `schemeProvider()`, next to `'MT VAT'` (line 120):

```php
        yield 'MT ID card' => ['MT', 'id_card', ['0123456M', '0000123G'], ['0123456K', '01234567', '123456M']];
        yield 'MT TIN'     => ['MT', 'tin', ['123456789'], ['12345678', '1234567890']];
```

And the branch test:

```php
    /**
     * Malta issues two different TINs to individuals, and both are structure
     * only. A national's is their identity-card number, seven digits and a
     * category letter — the letter is a category, not a check character, so it
     * must be one of the eight that exist and nothing more can be verified. A
     * non-national gets nine digits with nothing checkable at all.
     *
     * Leading zeros are part of the identity-card number. The short form 999L is
     * the same value unpadded, and the padded form is what any system must store.
     */
    public function testMalteseIdentifiersAreStructureOnly(): void
    {
        self::assertTrue($this->taxid->isValid('0000123G', 'MT', 'id_card'));
        self::assertFalse($this->taxid->isValid('123G', 'MT', 'id_card'), 'The unpadded short form is not the storable form.');

        foreach (['A', 'B', 'G', 'H', 'L', 'M', 'P', 'Z'] as $category) {
            self::assertTrue(
                $this->taxid->isValid('0123456' . $category, 'MT', 'id_card'),
                $category . ' is a real category letter and must be accepted.',
            );
        }

        foreach (['C', 'D', 'K', 'X'] as $absent) {
            self::assertFalse(
                $this->taxid->isValid('0123456' . $absent, 'MT', 'id_card'),
                $absent . ' is not a category letter and must be refused.',
            );
        }

        // Nine digits is the non-national TIN and must not be read as an id_card.
        self::assertTrue($this->taxid->isValid('123456789', 'MT', 'tin'));
        self::assertFalse($this->taxid->isValid('123456789', 'MT', 'id_card'));
    }
```

- [ ] **Step 2: Run them to verify they fail**

```bash
vendor/bin/phpunit tests/National/EuropeValidatorsTest.php --filter "Maltese|MT "
```

Expected: FAIL — neither scheme exists.

- [ ] **Step 3: Add both registry entries**

Append to the `'MT'` block after `vat`:

```php
            [
                'code'     => 'id_card',
                'name'     => 'Identity card number',
                'entity'   => 'natural',
                'vat'      => false,
                'pattern'  => '/^\d{7}[ABGHLMPZ]$/',
                'lengths'  => [8],
                'examples' => ['0123456M'],
                'notes'    => 'A Maltese national\'s TIN is their identity-card number: seven digits and a category letter (A, B, G, H, L, M, P or Z). The letter is a category, not a check character, so there is nothing to verify beyond the shape — hence no check_digit and no check_digit_offset. Leading zeros are part of the number: the short form 999L is the same value unpadded, and only the eight-character form is accepted.',
            ],
            [
                'code'     => 'tin',
                'name'     => 'TIN — tax identification number (non-nationals)',
                'entity'   => 'natural',
                'vat'      => false,
                'pattern'  => '/^\d{9}$/',
                'lengths'  => [9],
                'examples' => ['123456789'],
                'notes'    => 'Issued to individuals who are not Maltese nationals. Nine digits with no checksum and no internal structure, so validation can only confirm the length and the character set. Accepting it is still better than rejecting a real TIN.',
            ],
```

- [ ] **Step 4: Run the tests to verify they pass**

```bash
vendor/bin/phpunit tests/National/EuropeValidatorsTest.php --filter "Maltese|MT "
vendor/bin/phpunit tests/Registry
```

Expected: PASS. Confirm from the output that `MT/id_card` and `MT/tin` do **not** fail `testCheckDigitSchemesRejectMutations` — if either does, the `notes` is missing or a `check_digit` key was added by mistake.

- [ ] **Step 5: Commit**

```bash
composer test && composer analyze && composer cs
git add src/Registry/data/countries.php tests/National/EuropeValidatorsTest.php
git commit -m "feat: Maltese identity card number and non-national TIN"
```

---

### Task 10: Norway — fødselsnummer

**Files:**
- Create: `src/National/NoFodselsnummerValidator.php`
- Modify: `src/Registry/data/countries.php` (import; the `'NO'` block at line 1629-1657)
- Modify: `tests/National/EuropeValidatorsTest.php`

**Interfaces:**
- Consumes: `Weights::sum(string, int[]): int` and `Weights::mod11ComplementDigit(int, ?int, ?int): ?int`. The defaults of the second are exactly right here: it returns `null` when the digit would be 10 and `0` when the sum is a multiple of 11.
- Produces: scheme `NO/fodselsnummer`; class `Daycry\TaxId\National\NoFodselsnummerValidator`.

- [ ] **Step 1: Write the failing tests**

In `schemeProvider()`, next to the two `'NO'` rows (lines 123-124):

```php
        yield 'NO fødselsnummer' => [
            'NO',
            'fodselsnummer',
            ['01019012480', '41019012393'],
            ['01419012382', '01019012481', '0101901248'],
        ];
```

And the branch test:

```php
    /**
     * Norway offsets a field by 40 for two different purposes, and only one of
     * them is a tax identifier. A D-number, issued to non-residents, adds 40 to
     * the DAY and is a real fødselsnummer. An H-number adds 40 to the MONTH and
     * exists only inside the health service.
     *
     * `01419012382` is the case that matters: its checksum is entirely valid, so
     * it can only be refused by looking at the month. A validator that checks
     * arithmetic alone accepts it.
     */
    public function testNorwegianDNumbersAreAcceptedAndHNumbersAreNot(): void
    {
        self::assertTrue($this->taxid->isValid('01019012480', 'NO', 'fodselsnummer'));

        // D-number: day 01 + 40 = 41.
        self::assertTrue($this->taxid->isValid('41019012393', 'NO', 'fodselsnummer'));

        // H-number: month 01 + 40 = 41. Checksum valid, still not a tax ID.
        self::assertFalse($this->taxid->isValid('01419012382', 'NO', 'fodselsnummer'));
    }
```

- [ ] **Step 2: Run them to verify they fail**

```bash
vendor/bin/phpunit tests/National/EuropeValidatorsTest.php --filter "Norwegian|fødselsnummer"
```

Expected: FAIL — no such scheme.

- [ ] **Step 3: Write the validator**

Create `src/National/NoFodselsnummerValidator.php`:

```php
<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Core\Checksum\Weights;

/**
 * Norway — fødselsnummer, the eleven-digit national identity number,
 * `DDMMYYNNNKK`.
 *
 * Two mod-11 check digits, each the complement of a weighted sum: the first
 * over the nine digits before it, the second over all ten. A digit that
 * computes to 10 cannot be written, so that serial is simply never issued.
 *
 * Two variants offset a field by 40 and only one of them belongs here:
 *
 * - a **D-number**, issued to non-residents, adds 40 to the day. It is a real
 *   tax identifier and shares this checksum, so it is accepted.
 * - an **H-number** adds 40 to the month instead. It exists only inside the
 *   health service, and its checksum is perfectly valid — which is exactly why
 *   the month has to be range-checked rather than trusted to arithmetic.
 */
final class NoFodselsnummerValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['NO'];
    protected const array SCHEMES   = ['fodselsnummer'];

    private const array WEIGHTS_FIRST  = [3, 7, 6, 1, 8, 9, 4, 5, 2];
    private const array WEIGHTS_SECOND = [5, 4, 3, 2, 7, 6, 5, 4, 3, 2];

    public function verify(string $compact): bool
    {
        if (strlen($compact) !== 11 || ! ctype_digit($compact)) {
            return false;
        }

        $day   = (int) substr($compact, 0, 2);
        $month = (int) substr($compact, 2, 2);

        if ($day > 40) {
            $day -= 40;   // a D-number, not an error
        }

        if ($day < 1 || $day > 31 || $month < 1 || $month > 12) {
            return false;
        }

        $first = Weights::mod11ComplementDigit(Weights::sum(substr($compact, 0, 9), self::WEIGHTS_FIRST));

        if ($first === null || $first !== (int) $compact[9]) {
            return false;
        }

        $second = Weights::mod11ComplementDigit(Weights::sum(substr($compact, 0, 10), self::WEIGHTS_SECOND));

        return $second !== null && $second === (int) $compact[10];
    }
}
```

`Weights::mod11ComplementDigit()` is used with its defaults on purpose: `tenAs: null` reports the never-issued case and `elevenAs: 0` handles a sum that is a multiple of 11. Writing `(11 - $sum) % 11` by hand is the trap — PHP's `%` is signed and would hand back a negative digit.

- [ ] **Step 4: Add the registry entry**

Import, alphabetically **before** `NoOrgnrValidator`:

```php
use Daycry\TaxId\National\NoFodselsnummerValidator;
use Daycry\TaxId\National\NoOrgnrValidator;
```

Append to the `'NO'` block after `orgnr`:

```php
            [
                'code'               => 'fodselsnummer',
                'name'               => 'Fødselsnummer — national identity number',
                'entity'             => 'natural',
                'vat'                => false,
                'pattern'            => '/^\d{11}$/',
                'lengths'            => [11],
                'mask'               => '###### #####',
                'check_digit'        => NoFodselsnummerValidator::class,
                'check_digit_offset' => [-2, 2],
                'examples'           => ['01019012480'],
                'notes'              => 'Also covers the D-number issued to non-residents, whose day field is offset by 40. The H-number, which offsets the month instead and is used only inside the health service, is refused even though its checksum is valid.',
            ],
```

- [ ] **Step 5: Run the tests to verify they pass**

```bash
vendor/bin/phpunit tests/National/EuropeValidatorsTest.php --filter "Norwegian|fødselsnummer|NO "
vendor/bin/phpunit tests/Registry
```

Expected: PASS, including the existing `NO orgnr` and `NO MVA` rows — 11 digits collides with neither 9 nor 12.

- [ ] **Step 6: Commit**

```bash
composer test && composer analyze && composer cs
git add src/National/NoFodselsnummerValidator.php src/Registry/data/countries.php tests/National/EuropeValidatorsTest.php
git commit -m "feat: Norwegian fodselsnummer"
```

---

### Task 11: Documentation, changelog and the coverage floor

**Files:**
- Modify: `tests/Registry/CountriesDataIntegrityTest.php:324`
- Modify: `docs/countries.md` (header count line 3; nine table rows)
- Modify: `docs/algorithms.md` (the "four primitives" section; nine provenance entries)
- Modify: `README.md`, `CLAUDE.md` (coverage counters)
- Modify: `CHANGELOG.md` (a `[1.1.0]` section)

**Interfaces:**
- Consumes: every scheme from Tasks 3-10.
- Produces: nothing consumed by code.

**Before starting:** `docs/algorithms.md` carries uncommitted line-reflow changes that break the file's 100-column convention. Decide with the author whether to discard them (`git checkout -- docs/algorithms.md`) or keep them. Do not silently commit them alongside this work.

- [ ] **Step 1: Raise the documented coverage floor**

In `tests/Registry/CountriesDataIntegrityTest.php`, in `testRegistryCoversTheDocumentedBreadth()`:

```php
        self::assertGreaterThanOrEqual(90, count($registry->countries()));
        self::assertGreaterThanOrEqual(145, $registry->schemeCount());
```

- [ ] **Step 2: Run it and read the real numbers off the suite**

```bash
vendor/bin/phpunit tests/Registry
composer test
```

Expected: PASS. Then get the authoritative counts rather than trusting this plan's arithmetic — the registry is the only source of truth:

```bash
php -r 'require "vendor/autoload.php"; $r = new Daycry\TaxId\Registry\Registry(); $checksummed = 0; $classes = []; foreach ($r->all() as $p) { foreach ($p->schemes as $s) { if ($s->checkDigitClass !== null) { $checksummed++; $classes[$s->checkDigitClass] = true; } } } printf("%d countries, %d schemes, %d checksummed, %d validator classes\n", count($r->countries()), $r->schemeCount(), $checksummed, count($classes));'
```

Expected: `94 countries, 145 schemes, 95 checksummed, 84 validator classes`. **If these differ, believe the command and use its numbers in every document below.**

- [ ] **Step 3: Update `docs/countries.md`**

Change the header line 3 to:

```markdown
**94 jurisdictions, 145 schemes, 95 of them with a verified check digit.**
```

Insert nine rows, each in its country's existing block and in registry order — new schemes go **after** the country's existing ones:

```markdown
| `AT` | Austria | `steuernummer` | Steuernummer — Abgabenkontonummer | both | — | 9 | ✅ | `123456782` |
| `FI` | Finland | `henkilotunnus` | Henkilötunnus — personal identity code | natural | — | 10, 11 | ✅ | `010190123M` |
| `FR` | France | `spi` | Numéro fiscal de référence (SPI) | natural | — | 13 | ✅ | `1234567890066` |
| `HU` | Hungary | `adoazonosito` | Adóazonosító jel — personal tax identifier | natural | — | 10 | ✅ | `8123456786` |
| `LT` | Lithuania | `asmens_kodas` | Asmens kodas — personal code | natural | — | 11 | ✅ | `39001011237` |
| `LU` | Luxembourg | `matricule` | Matricule — numéro d'identification national | natural | — | 13 | ✅ | `1990010112385` |
| `MT` | Malta | `id_card` | Identity card number | natural | — | 8 | — | `0123456M` |
| `MT` | Malta | `tin` | TIN — tax identification number (non-nationals) | natural | — | 9 | — | `123456789` |
| `NO` | Norway | `fodselsnummer` | Fødselsnummer — national identity number | natural | — | 11 | ✅ | `01019012480` |
```

- [ ] **Step 4: Update `docs/algorithms.md`**

The file opens by saying nearly every national algorithm is "one of four ideas". It is five now. Change that sentence and add a row to the primitives table:

```markdown
| `Verhoeff` | Dihedral-group D₅ folding; catches every adjacent transposition, which Luhn does not | Luxembourg |
```

Then add nine rows to the `## Europe` table, each in its country's alphabetical position:

```markdown
| AT | `steuernummer` | Luhn over all nine digits — the published odd-position table `0246813579` *is* "double, subtract 9 if over 9", verified identical across 100 000 bodies. The Finanzamt code is not checked. |
| FI | `henkilotunnus` | The nine digits of date and serial read as one integer, mod 31, indexing `0123456789ABCDEFHJKLMNPRSTUVWXY` (no G, I, O, Q, Z). The century marker takes no part, which is why ten- and eleven-character forms both validate. |
| FR | `spi` | Last three digits = first ten mod 511. Accumulated digit by digit, since ten digits overflow a 32-bit integer. |
| HU | `adoazonosito` | Each of the first nine digits times its own position, mod 11; a remainder of 10 is unissuable rather than folded. Always begins with 8. |
| LT | `asmens_kodas` | Two-pass mod-11, weights 1…9,1 then 3…9,1,2,3 — identical to Estonia's isikukood. The date is checked unless the leading digit is 9. |
| LU | `matricule` | Two check digits over the **same** first eleven digits: Luhn in the twelfth, Verhoeff in the thirteenth. |
| MT | `id_card` | **No checksum.** Seven digits and a category letter (A, B, G, H, L, M, P, Z); the letter is a category, not a verifier. |
| MT | `tin` | **No checksum and no structure** — the nine-digit TIN of a non-national. Length and character set are all there is. |
| NO | `fodselsnummer` | Two mod-11 complements, weights `376189452` then `5432765432`; a digit computing to 10 is unissuable. Day +40 is a D-number (accepted); month +40 is an H-number (refused despite a valid checksum). |
```

Then add this subsection, because the file's whole premise is that a wrong algorithm is worse than a missing one and a reader deserves to know which rules rest on what:

```markdown
### Sources for the 1.1.0 natural-person schemes

Seven of the nine were verified against a reference implementation read directly: Austria, Finland,
France, Lithuania and Norway against `python-stdnum`, and Luxembourg against a published
implementation of the CCSS rule, whose own page blocks automated fetching and was read only as a
quotation.

Two rest on secondary sources, and that is worth knowing before debugging a rejected identifier:

- **Malta** — the OECD and EU TIN country sheets, read as published summaries rather than from the
  PDFs. Neither Maltese scheme carries a checksum, so a misreading here can only mis-shape a pattern.
- **Hungary** — a restatement of the NAV rule rather than the rule itself. It is the one algorithm in
  the batch with a real check digit and no primary source, so it is the first place to look if a valid
  Hungarian identifier is ever refused. Its unissuable-remainder branch is pinned by
  `testHungarianPersonalIdRejectsTheUnwritableRemainder`.
```

- [ ] **Step 5: Update the counters in `README.md` and `CLAUDE.md`**

Find every place stating the coverage and update it with the numbers from Step 2. In `CLAUDE.md` this is the "Current status" bullet, which currently reads `94 jurisdictions, 136 schemes, 77 national check-digit validators covering 88 of those schemes`, and the `src/` tree listing, which says `National/ 77 check-digit validators` and `Core/ … Checksum/ (Luhn, Mod97, Iso7064, Weights)`. Add `Verhoeff` to the latter.

```bash
grep -rn "136 schemes\|77 national\|77 check-digit\|88 of those\|Luhn, Mod97, Iso7064, Weights" README.md CLAUDE.md docs/
```

- [ ] **Step 6: Write the changelog entry**

In `CHANGELOG.md`, replace the empty `## [Unreleased]` with:

```markdown
## [Unreleased]

## [1.1.0] - 2026-07-28

Natural-person tax identifiers for eight countries that were covered only by their corporate
identifier. A person's number in Austria, Finland, France, Hungary, Lithuania, Luxembourg, Malta or
Norway previously failed with `bad_length` or `malformed_structure`.

### Added

- **Nine schemes**, taking the registry to 145 schemes across the same 94 jurisdictions, 95 of them
  with a verified check digit: `AT/steuernummer`, `FI/henkilotunnus`, `FR/spi`, `HU/adoazonosito`,
  `LT/asmens_kodas`, `LU/matricule`, `MT/id_card`, `MT/tin`, `NO/fodselsnummer`.
- **Seven check-digit validators** in `src/National/`, taking the count to 84. `MT/id_card` and
  `MT/tin` are structural only — Malta publishes no checksum for either — and say so in their
  registry `notes`.
- **`Core\Checksum\Verhoeff`**, a fifth shared primitive. Luxembourg's matricule pairs a Luhn digit
  with a Verhoeff one over the same eleven-digit base, because Luhn is blind to an adjacent
  transposition whose digits differ by five and Verhoeff is not.
- `AT/steuernummer` is `entity: both`: the Abgabenkontonummer is issued to individuals and companies
  alike.

### Changed

- **`Normalizer` now treats `+` as a separator**, in every country. Finland's henkilötunnus uses it
  as the century marker for the 1800s, and `IllegalCharacters` is checked before any scheme is
  consulted, so the character could never reach a pattern. This only widens what validates: a value
  containing `+` returned `illegal_characters` before, so nothing that already validated changes
  meaning. One consequence worth knowing: a Finnish identity code written with `+` or `-` normalizes
  to **ten** characters, while one written with a letter marker keeps **eleven**. Both are correct and
  both validate; the marker never enters the checksum.

### Notes

- The nine algorithms are independently authored from published national rules and verified against
  reference implementations, not against issued identifiers. Hungary's is the one with no primary
  source available; see `docs/algorithms.md` for the per-algorithm provenance.
- Bundled examples remain synthetic and checksum-valid.
```

- [ ] **Step 7: Final verification**

```bash
composer test
composer analyze
composer cs
```

Expected: all three clean. Confirm the suite grew — v1.0 shipped 1,284 tests and 3,322 assertions, so a lower number means tests were lost rather than added.

- [ ] **Step 8: Commit**

```bash
git add tests/Registry/CountriesDataIntegrityTest.php docs/countries.md docs/algorithms.md README.md CLAUDE.md CHANGELOG.md
git commit -m "docs: coverage for the natural-person schemes, and a 1.1.0 changelog"
```

---

## Verification checklist

Run before opening the pull request:

- [ ] `composer test` — green, and the test count is higher than v1.0's 1,284.
- [ ] `composer analyze` — PHPStan level 8 clean, no new baseline and no `@phpstan-ignore`.
- [ ] `composer cs` — no diff.
- [ ] `vendor/bin/phpunit tests/Architecture` — green: nothing added to `src/Core`, `src/National` or `src/Registry` imports CodeIgniter.
- [ ] `php spark taxid:countries` output agrees with `docs/countries.md` (that file claims the command is authoritative).
- [ ] `git log --oneline` shows eleven commits, each self-contained.
- [ ] No reference-implementation value is bundled as a registry example.
