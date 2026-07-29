# Adding a country

Adding a jurisdiction is a **data** edit plus, if it has a check digit, one small class. The
integrity test is your compiler: it will not let a broken country land.

## 1. Add the country to the registry

`src/Registry/data/countries.php`, in alphabetical order by ISO 3166-1 alpha-2 code:

```php
'ZZ' => [
    'name'       => 'Zedland',
    'vat_prefix' => 'ZZ',          // null where the country has no VAT prefix
    'schemes'    => [
        [
            'code'               => 'tin',
            'name'               => 'TIN — Zedland Taxpayer Number',
            'entity'             => 'both',            // natural | legal | both
            'vat'                => true,
            'pattern'            => '/^\d{8}[A-Z]$/',  // anchored, over the COMPACT form
            'lengths'            => [9],
            'mask'               => '####-####-#',     // optional
            'check_digit'        => ZzTinValidator::class,   // omit for structural-only
            'check_digit_offset' => [-1, 1],           // optional; negative counts from the end
            'examples'           => ['12345678K'],
            'notes'              => 'Anything a consumer should know.',
        ],
    ],
],
```

### Field by field

**`vat_prefix`** — the prefix used when quoting the VAT number internationally, or `null`. Usually
the country code; notable exceptions are Greece (`EL`) and Northern Ireland (`XI`). It must be unique
across the registry, or country inference would be ambiguous — the integrity test checks this.

**`pattern`** — applied to the **compact** form: uppercase, separators stripped, VAT prefix removed.
So Austria's is `/^U\d{8}$/`, not `/^ATU\d{8}$/`.

**`lengths`** — every legal compact length. Not decoration: it is what lets the Validator report
`BadLength` instead of a vague `MalformedStructure`, and what `taxid:countries` prints.

**`mask`** — the print mask. `#` consumes one character of the value; everything else is a literal.
Its placeholder count must equal one of the declared lengths, or it would never be applied — the
integrity test enforces that too.

**`check_digit`** — the validator class-string. Omit it for a structural-only scheme, but then
**`notes` is mandatory** and must say why: a silent absence is indistinguishable from an oversight.

**`examples`** — at least one, and they must be **synthetic**. Compute them with the algorithm; do
not paste a real company's number. They are checksum-valid, which exercises exactly the same code
path, and nobody's identifier ends up in a public repository. See [licensing.md](licensing.md).

### Scheme order matters

Schemes are matched in the order you write them, and the first structural match wins when the caller
names none. Declare the most specific first — Spain lists `nif`, then `nie`, then `cif`.

If two schemes genuinely overlap (Germany's IdNr and Steuernummer both accept eleven digits), that is
fine: the Validator tries each candidate and accepts the value if any of them does. Note the overlap
in `notes` so the next reader isn't surprised.

## 2. Write the validator, if there is a check digit

`src/National/<Cc><Scheme>Validator.php` — a two-letter country prefix keeps nearly eighty files
navigable and sorts them by country:

```php
<?php

declare(strict_types=1);

namespace Daycry\TaxId\National;

use Daycry\TaxId\Core\Checksum\Weights;

/**
 * Zedland — TIN, eight digits and a check letter.
 *
 * Weighted mod-11 with the key `98765432`; the remainder indexes the
 * 11-letter alphabet the tax office publishes.
 */
final class ZzTinValidator extends AbstractNationalValidator
{
    protected const array COUNTRIES = ['ZZ'];
    protected const array SCHEMES   = ['tin'];

    private const array WEIGHTS = [9, 8, 7, 6, 5, 4, 3, 2];

    public function verify(string $compact): bool
    {
        if (strlen($compact) !== 9) {
            return false;
        }

        // …
    }
}
```

Rules the base class and the contract impose:

- **Stateless.** One instance is built per class and shared.
- **Never throws.** `verify()` receives a value that already matched the pattern; anything it
  dislikes is a `false`.
- `COUNTRIES` is a list because a few algorithms serve more than one jurisdiction — the UK's VAT
  check also covers `XI`, and France's SIREN check also covers Monaco.

### Reuse the primitives

`src/Core/Checksum/` already has the five building blocks most algorithms need:

| Class | Use it for |
| --- | --- |
| `Weights::sum()` / `cyclicSum()` | Any "digit × positional weight, summed" scheme. |
| `Weights::mod11ComplementDigit()` | `11 - (sum % 11)`, with the 10/11 overflow folded how you say. |
| `Weights::mod11RemainderDigit()` | `sum % 11`, where a remainder below 2 means `0` (Brazil, Colombia). |
| `Luhn` | Mod-10 double-every-second (France, Sweden, Canada, Israel, Italy). |
| `Mod97` | Long numeric strings mod 97 (Belgium, the UK). |
| `Iso7064` | MOD 11,10 (Croatia, Serbia, Germany), MOD 11-2 (China), MOD 37,36. |
| `Verhoeff` | Catches adjacent transpositions Luhn misses (Luxembourg's matricule, paired with Luhn). |

Only hand-roll the arithmetic when the algorithm genuinely doesn't fit — Türkiye's VKN and Korea's
BRN are the two bundled cases that don't.

### One class per scheme when the value can't be told apart

`verify()` receives only the compact string, not the scheme. If two of a country's schemes share a
length, one class cannot distinguish them — split them. Sweden is the worked example: `SeOrgnrValidator`,
`SeVatValidator` and `SePersonnummerValidator`, rather than one class guessing from the length.

### Never-issued ranges

If the scheme has documented values that are structurally fine but never issued, also implement
`ReservedValueDetectorInterface`:

```php
final class ZzTinValidator extends AbstractNationalValidator implements ReservedValueDetectorInterface
{
    public function isReserved(string $compact): bool
    {
        return str_starts_with($compact, '00');
    }
}
```

Those get the `ReservedValue` code rather than `CheckDigitFailed`, which is a real distinction for
anyone debugging why their test fixture was rejected. A scheme with **no** check digit at all (the US
identifiers) can implement only the reserved rules and have `verify()` simply confirm the shape.

## 3. Run the tests

```bash
vendor/bin/phpunit tests/Registry
```

The data-integrity test will now, for your scheme:

1. compile the pattern and match it against your first example;
2. check every example's length is in `lengths`;
3. check the mask's placeholder count matches one of those lengths;
4. check the validator class exists, implements the contract, and `supports()` your country/scheme;
5. demand a `notes` entry if there is no validator;
6. **validate every example through the public facade**;
7. round-trip the printed form back through `normalize()`;
8. bump each digit of your example in turn and require at least half the mutations to be rejected.

Step 8 is the one that catches the real mistake. A checksum that accepts everything looks like
protection and provides none; if your validator passes steps 1–7 but fails 8, the algorithm is
wrong, not the test.

If your scheme genuinely has no arithmetic check digit, add it to `NO_ARITHMETIC_CHECK` in that test
file — and be sure that is true rather than convenient.

## 4. Add the behavioural tests

The integrity test proves your examples work. Add the cases it cannot know about, in
`tests/National/EuropeValidatorsTest.php` or `AmericasAndAsiaValidatorsTest.php`:

```php
yield 'ZZ TIN' => ['ZZ', 'tin', ['12345678K'], ['12345678L', '1234567K']];
```

Then a dedicated test for any branch — a legacy format, an exception for one issuer, a value that
looks valid and must not be.

## 5. Update the coverage docs

`docs/countries.md` restates the registry as a table, by hand. `CountriesDocIsInSyncTest` will fail
until your row is there and every cell of it agrees with the registry, including the count line at
the top and the order of the rows within your country — read its failure message, it names the cell.
Then mention the addition in
`CHANGELOG.md`.

## Checklist

- [ ] Country added in alphabetical order, with a `name` and a correct `vat_prefix` (or `null`).
- [ ] Schemes ordered most-specific-first.
- [ ] Pattern anchored and written against the **compact** form.
- [ ] `lengths` complete.
- [ ] Mask placeholder count matches a declared length (or no mask).
- [ ] Examples synthetic, checksum-valid, at least one per scheme.
- [ ] `notes` explains any missing checksum.
- [ ] Validator stateless, never throws, `COUNTRIES`/`SCHEMES` correct.
- [ ] `vendor/bin/phpunit`, `composer analyze` and `composer cs` all green.
- [ ] `docs/countries.md` row added and `CountriesDocIsInSyncTest` green, `CHANGELOG.md` updated.
