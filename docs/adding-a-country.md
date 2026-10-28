# Adding a Country

[Documentation Index](README.md) | [Package README](../README.md)

Extend the registry with a jurisdiction or identifier scheme, implement its supported checks, and verify the result through the public API.

- **Registry:** Format, entity type, lengths, optional masks and worked examples.
- **Validator:** Arithmetic or reserved-value checks supported by the source evidence.
- **Verification:** Focused regression tests, registry integrity and synchronized coverage documentation.

---

## 1. Add the Country to the Registry

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

### Field by Field

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

### Scheme Order Matters

Detection collects structural matches in registry order. Validation tries those candidates in order
and accepts the first one that passes its configured checks. Declare the most specific first — Spain lists `nif`, then `nie`, then `cif`.

If two schemes genuinely overlap (Germany's IdNr and Steuernummer both accept eleven digits), that is
fine: the Validator tries each candidate and accepts the value if any of them does. Note the overlap
in `notes` so the next reader isn't surprised.

---

## 2. Write the Validator, If There Is a Check Digit

`src/National/<Cc><Scheme>Validator.php` — a two-letter country prefix keeps national validators
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

### Reuse the Primitives

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

### One Class Per Scheme When the Value Can't Be Told Apart

`verify()` receives only the compact string, not the scheme. If two of a country's schemes share a
length, one class cannot distinguish them — split them. Sweden is the worked example: `SeOrgnrValidator`,
`SeVatValidator` and `SePersonnummerValidator`, rather than one class guessing from the length.

### Never-issued Ranges

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

### Per-Value Checksum Exceptions

A scheme can support a checksum while accepting some variants without a complete control.
Implement the optional `ChecksumAwareValidatorInterface` and its
`hasChecksum(string $compact): bool` method when this applies. The engine calls it after
successful verification to populate `ValidationResult::checksumVerified`.

- **Complete checksum checked:** Return `true`.
- **Exceptional format accepted:** Return `false`; retain the applicable structure/component checks in `verify()`.
- **Existing custom validator:** The extra interface is optional; a normal arithmetic verifier needs no change.

See `FrTvaValidator`, `GbVatValidator` and `MxRfcValidator` for existing implementations.

---

## 3. Run the Tests

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

For a verifier that checks structure or reserved ranges without arithmetic, set
`checksum => false` in its registry entry. Keep `check_digit` connected so the range
checks still run. `hasCheckDigit()` then reports false and the mutation test uses that
capability flag; no separate exception list is maintained. Document the limitation in `notes`.

---

## 4. Add the Behavioural Tests

The integrity test proves your examples work. Add the cases it cannot know about, in
`tests/National/EuropeValidatorsTest.php` or `AmericasAndAsiaValidatorsTest.php`:

```php
yield 'ZZ TIN' => ['ZZ', 'tin', ['12345678K'], ['12345678L', '1234567K']];
```

Then a dedicated test for any branch — a legacy format, an exception for one issuer, a value that
looks valid and must not be.

---

## 5. Update the Coverage Docs

`docs/countries.md` restates the registry as a table, by hand. `CountriesDocIsInSyncTest` will fail
until your row is there and every cell of it agrees with the registry, including the count line at
the top and the order of the rows within your country — read its failure message, it names the cell.
Then mention the addition in
`CHANGELOG.md`.

---

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
