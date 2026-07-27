# Formatting

Three shapes, one tiny mask language, and one rule: formatting never fails.

## The three formats

```php
use Daycry\TaxId\Enums\TaxIdFormat;

$parsed = $taxid->parse('11144477735', 'BR');

$parsed->format(TaxIdFormat::Compact);      // '11144477735'
$parsed->format(TaxIdFormat::Print);        // '111.444.777-35'   (the default)
$parsed->format(TaxIdFormat::Anonymized);   // '*********35'
```

| Format | What it is | Use it for |
| --- | --- | --- |
| `Compact` | Uppercase, no separators, no VAT prefix. The canonical form. | Storage, comparison, API payloads, cache keys. |
| `Print` | The country's conventional mask. | Anything a human reads. |
| `Anonymized` | Everything but the last two characters replaced by `*`. | Logs, error reports, support tickets. |

**Store the compact form.** It is what `normalize()` produces, what a provider is queried with, and
the only form two records can be reliably compared in. Render `Print` at the edge.

## Anonymized, and why two characters

Two trailing characters are enough to correlate a value across log lines — "is this the same customer
as the error five minutes ago?" — and not nearly enough to reconstruct it. Values of two characters
or fewer are masked entirely, since there would be nothing left to hide.

```php
$taxid->format('11144477735', TaxIdFormat::Anonymized, 'BR');   // '*********35'
$taxid->format('12345678Z', TaxIdFormat::Anonymized, 'ES');     // '*******8Z'
```

Some identifiers are legally sensitive well beyond ordinary personal data — an Australian TFN may not
be used as a general identifier, and Japan's My Number and Korea's resident number are similarly
restricted. If one of those has to appear in a log at all, this is the form it should take.

## The mask language

A scheme's `mask` is a template. `#` consumes one character of the value; every other character is a
literal copied through.

| Scheme | Mask | Result |
| --- | --- | --- |
| BR CPF | `###.###.###-##` | `111.444.777-35` |
| BR CNPJ | `##.###.###/####-##` | `11.222.333/0001-81` |
| ES NIF | `########-#` | `12345678-Z` |
| ES CIF | `#-#######-#` | `B-1234567-4` |
| AR CUIT | `##-########-#` | `20-12345678-6` |
| CL RUT | `##.###.###-#` | `12.345.678-5` |
| PL NIP | `###-###-##-##` | `123-456-32-18` |
| CH UID | `###-###.###.###` | `CHE-116.281.839` |

Switzerland is the neat case: the first three `#` consume `C`, `H` and `E`, so a literal prefix needs
no special support in the language.

A mask is optional. Without one, `Print` returns the compact form — which is correct for the many
schemes nobody writes with separators.

## Formatting never fails

Two deliberate fallbacks:

**A mask that doesn't fit is ignored.** If the placeholder count differs from the value's length —
a variable-length scheme, or a value of the wrong size — the compact form is returned rather than a
half-filled template. The data-integrity test asserts every bundled mask matches one of its scheme's
declared lengths, so this only fires on unexpected input.

**Unparseable input is normalized, not rejected.** Templates format before they validate, and a view
should not throw because a user typed rubbish:

```php
$taxid->format('not-a-tax-id', TaxIdFormat::Print, 'ES');   // 'NOTATAXID'
```

## Round-tripping

The printed form always normalizes back to the original. The integrity test asserts it for every
bundled example, so a value can go through a UI and come back unchanged:

```php
$printed = $taxid->format($compact, TaxIdFormat::Print, $country);

$taxid->normalize($printed, $country) === $compact;   // always true
```

## The default format under CodeIgniter

The facade's own default is `Print`. The helper defers to `Config\TaxId::$defaultFormat`
(`'compact'`, `'print'` or `'anonymized'`) when you pass `null`:

```php
// Config\TaxId::$defaultFormat = 'anonymized'
taxid_format('11144477735', null, 'BR');   // '*********35'
```

An application that logs a lot of identifiers can set `anonymized` as its default and opt into the
readable form only where it is actually shown to the taxpayer themselves.
