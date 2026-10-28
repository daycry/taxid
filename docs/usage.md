# Usage

[Documentation Index](README.md) | [Package README](../README.md)

Use the facade, CodeIgniter service, helper or CLI to validate and inspect identifiers.

- **Validate:** Choose the country and identifier type, then inspect the result.
- **Parse and format:** Read scheme metadata and produce compact, printable or masked output.
- **Resolve:** Configure an optional provider when taxpayer information is needed.

---

## Contents

- [The facade](#the-facade)
- [Naming the country — or not](#naming-the-country--or-not)
- [The twelve violation codes](#the-twelve-violation-codes)
- [Countries with more than one scheme](#countries-with-more-than-one-scheme)
- [Parsing](#parsing)
- [Formatting](#formatting)
- [Resolving a taxpayer](#resolving-a-taxpayer)
- [Providers](#providers)
- [`Config\TaxId`](#configtaxid)
- [The helper](#the-helper)
- [spark commands](#spark-commands)
- [Validating a form field](#validating-a-form-field)

---

## The Facade

`Daycry\TaxId\TaxId` is the whole public API. It needs no container and no framework:

```php
use Daycry\TaxId\TaxId;

$taxid = new TaxId();
```

Under CodeIgniter, `service('taxid')` returns the same class, already wired to whatever provider
`Config\TaxId` names.

```php
// Validation — never throws, for any input at all.
$taxid->validate('12345678Z', 'ES');                 // ValidationResult
$taxid->isValid('12345678Z', 'ES');                  // bool
$taxid->isValid('B12345674', 'ES', 'cif');           // demand one specific scheme

// Parsing.
$taxid->normalize('ES B-12345674');                  // 'B12345674'
$taxid->parse('12345678Z', 'ES');                    // ParsedTaxId — throws if invalid
$taxid->tryParse('12345678Z', 'ES');                 // ParsedTaxId|null — never throws

// Formatting.
$taxid->format('11144477735', TaxIdFormat::Print, 'BR');   // '111.444.777-35'

// Resolution.
$taxid->resolve('ESB12345674');                      // LookupResult

// Introspection.
$taxid->supports('ES');                              // true
$taxid->countries();                                 // ['AD', 'AE', 'AL', …]
$taxid->schemes('ES');                               // TaxIdScheme[]
$taxid->scheme('ES', 'cif');                         // TaxIdScheme
$taxid->country('ES');                               // CountryProfile
```

---

## Naming the Country — or Not

### Knowing What Was Checked

```php
$result = $taxid->validate('12345678Z', 'ES', 'nif');
$result->scheme;             // 'nif'
$result->checksumVerified;   // true

$result = $taxid->validate('123456789', 'US', 'ein');
$result->isValid();          // true: format/ranges accepted
$result->checksumVerified;   // false: EIN has no checksum

$result = $taxid->validate('12345678904', 'DE', 'idnr');
$result->isValid();          // false: cannot fall back to Steuernummer
$result->checksumVerified;   // null: validation failed
```

Always pass the third argument when the field requires one identifier type. Omitting
it accepts any matching scheme, including those that check only structure. A true
`checksumVerified` describes arithmetic verification, not whether a taxpayer exists.
Use `resolve()` with a suitable provider for the separate registration outcome.

The same metadata is returned by `taxid_validate()`. `taxid:validate --json` includes
`checksum_verified` and the actual accepting `scheme`. Existing method signatures and
two-argument construction of `ValidationResult` continue to work.

Personal identifiers are distinct from business and VAT numbers:

```php
$taxid->validate('756.0000.0000.19', 'CH', 'ahv'); // Swiss AHV/AVS, EAN-13 control
$taxid->validate('2000000000015', 'MD', 'idnp');  // Moldovan personal IDNP
$taxid->validate('1000000000018', 'MD', 'idno');  // Organization/entrepreneur IDNO
$taxid->validate('0101990410012', 'MK', 'embg');  // Macedonian personal EMBG
$taxid->validate('4000000000013', 'MK', 'edb');   // Macedonian tax registration EDB
```

These are synthetic examples. For a Macedonian personal-ID field, always require `embg`:
the broad `edb` scheme checks the checksum but does not interpret birth dates or personal
registration regions. Both schemes reject an incorrect control digit, but only `embg`
requires a valid personal date and region. See [algorithm sources and limitations](algorithms.md).

Icelandic kennitala validation also checks its century marker and full calendar date.
Norwegian personal-ID validation checks the calendar against the published year/serial
allocation ranges. It distinguishes 1900 from 2000 leap-day cases and keeps D-number day
offsets. For pre-2000 D-numbers, 1900 is used for calendar validation; the exact older birth
century is not exposed. This does not implement the new 2032 checksum.

### Supplying the Country

The value comes first and the country is optional:

```php
$taxid->isValid('12345678Z', 'ES');   // explicit
$taxid->isValid('ESB12345674');       // inferred from the VAT prefix
$taxid->isValid('EL094259216');       // Greece is quoted EL, and that is understood
$taxid->isValid('123456789');         // false — CountryRequired
```

Inference works from a **leading two-letter prefix the registry recognises**, and from nothing else.
It is never inferred from the digits: a nine-digit number is a plausible tax ID in dozens of
jurisdictions, so a guess would be confidently wrong a lot of the time. Without a prefix and without
a country you get `CountryRequired`, which is the honest answer.

A prefix is only stripped when stripping it makes the value match. `ATU12345678` becomes
`U12345678`, because the Austrian national number really does begin with `U`. An Italian codice
fiscale that happens to start `IT` is left alone, because the whole value already matches.

Under CodeIgniter you can set `Config\TaxId::$defaultCountry` and let the helper apply it — see
[The helper](#the-helper).

---

## The Twelve Violation Codes

`validate()` runs a fixed sequence and returns on the **first** failure. The code is part of the
public API: branch on it rather than on the message.

| Order | `ViolationCode` | Meaning |
| --- | --- | --- |
| 1 | `TooLong` | Raw input over 64 characters — a fail-fast guard before any work. |
| 2 | `Blank` | Empty, or empty after separators were stripped. |
| 3 | `TooShort` | Under 4 characters. No jurisdiction issues anything shorter. |
| 4 | `IllegalCharacters` | Something outside `A-Z0-9&` survived normalization. |
| 5 | `CountryRequired` | No country given and none inferable. |
| 6 | `UnknownCountry` | That country has no rules in the registry. |
| 7 | `SchemeMismatch` | You demanded a scheme; the value is a valid *different* one. |
| 8 | `BadLength` | Nothing matched, and the length fits none of the country's schemes. |
| 9 | `MalformedStructure` | Nothing matched, but the length was plausible. |
| 10 | `ReservedValue` | Well-formed, but in a documented never-issued range. |
| 11 | `CheckDigitFailed` | The scheme's verifier rejected it. |
| — | `NothingToValidate` | Reserved for callers with nothing to check. |

```php
$result = $taxid->validate('12345678A', 'ES');

$result->isValid();     // false
$result->errorCode();   // 'check_digit_failed'
$result->error();       // 'The tax ID check digit is invalid.'
$result->firstViolation()->code;         // ViolationCode::CheckDigitFailed
$result->firstViolation()->messageKey;   // 'taxid.violation.check_digit_failed'
```

`ReservedValue` is worth handling separately from `CheckDigitFailed`. It means the value is
arithmetically fine but was never issued — `111.111.111-11` as a Brazilian CPF, or a US SSN starting
`000`. Those are usually placeholder data rather than typos, and deserve a different message.

---

## Countries with More Than One Scheme

Some countries issue several kinds of tax ID, and their formats overlap. Germany's eleven-digit IdNr
and its ten-to-thirteen-digit Steuernummer are both plausible readings of eleven digits; a US EIN and
SSN are both nine digits.

The rule: **a value is valid if any of the country's schemes accepts it**, tried in registry order.

```php
$taxid->isValid('12345678901', 'DE');            // true — a valid Steuernummer
$taxid->isValid('12345678901', 'DE', 'idnr');    // false — fails the IdNr check digit
```

When you need one specific reading, name it. That is also what turns a vague "invalid" into the
much more useful `SchemeMismatch`:

```php
$taxid->validate('12345678Z', 'ES', 'cif')->error();
// 'The tax ID is a valid "nif", not the requested "cif".'
```

`$taxid->schemes('ES')` lists a country's schemes in detection order.

---

## Parsing

```php
$parsed = $taxid->parse('111.444.777-35', 'BR');

$parsed->countryCode;   // 'BR'
$parsed->scheme;        // 'cpf'
$parsed->schemeName;    // 'CPF — Cadastro de Pessoas Físicas'
$parsed->entityType;    // EntityType::Natural
$parsed->vat;           // false
$parsed->compact;       // '11144477735'
$parsed->input;         // '111.444.777-35' — exactly what you passed
$parsed->checkDigit;    // '35'
(string) $parsed;       // '11144477735'
```

`parse()` throws `InvalidTaxIdException` on a bad value, and the exception carries the full result:

```php
try {
    $taxid->parse($input, 'ES');
} catch (InvalidTaxIdException $e) {
    $code = $e->result()->firstViolation()?->code;
}
```

`tryParse()` returns `null` instead, which is usually what a controller wants.

A `ParsedTaxId` can be passed back into `validate()`, `isValid()`, `format()` and `resolve()` without
repeating the country — it already knows.

### VAT Numbers

```php
$taxid->parse('B12345674', 'ES')->vatNumber();   // 'ESB12345674'
$taxid->parse('094259216', 'GR')->vatNumber();   // 'EL094259216'
$taxid->parse('11144477735', 'BR')->vatNumber(); // null — a CPF is not a VAT number
$taxid->parse('51824753556', 'AU')->vatNumber(); // null — Australia quotes no prefix
```

---

## Formatting

Three shapes, described in full in [formatting.md](formatting.md):

```php
use Daycry\TaxId\Enums\TaxIdFormat;

$taxid->format('11144477735', TaxIdFormat::Compact, 'BR');      // '11144477735'
$taxid->format('11144477735', TaxIdFormat::Print, 'BR');        // '111.444.777-35'
$taxid->format('11144477735', TaxIdFormat::Anonymized, 'BR');   // '*********35'
```

`Anonymized` exists so a tax ID can appear in a log or a support ticket without the log becoming a
list of taxpayer identifiers. Two characters are enough to correlate a value across lines, and not
enough to reconstruct it.

Formatting never throws: an unparseable value comes back normalized rather than raising, because
templates format before they validate.

---

## Resolving a Taxpayer

```php
$result = $taxid->resolve('ESB12345674');

$result->isValid();        // bool   — offline validity
$result->isRegistered();   // ?bool  — true | false | null
$result->name();           // ?string
$result->address();        // ?string
$result->taxId();          // ?ParsedTaxId
$result->entity();         // ?EntityInfo
$result->validation();     // ValidationResult
```

### `null` Is Not `false`

`isRegistered()` has three states and conflating two of them is the most likely way to misuse this
package:

| Value | Meaning |
| --- | --- |
| `true` | A provider confirmed the registration. |
| `false` | A provider actively answered "no such registration". |
| `null` | **Nobody could answer.** No provider, non-EU country, or the service was unreachable. |

```php
// Wrong: rejects every customer whenever VIES has a bad afternoon.
if (! $result->isRegistered()) {
    throw new ValidationException('Invalid VAT number');
}

// Right.
if ($result->isRegistered() === false) {
    throw new ValidationException('This VAT number is not registered for intra-EU trade');
}

if ($result->isRegistered() === null) {
    $this->flagForManualReview($result->taxId());
}
```

An invalid value never reaches a provider at all: `resolve()` validates first, and returns with
`taxId() === null` and the reason in `validation()`.

---

## Providers

| Provider | Framework | What it does |
| --- | --- | --- |
| `NullProvider` | none | The default. Answers nothing, ever. Validation still works. |
| `ViesProvider` | CI4 | EU VAT existence check against the European Commission's VIES. |
| `DatabaseProvider` | CI4 | The local `tax_entities` table. |
| `CachedProvider` | CI4 | Decorator. Caches hits, never misses. |
| `ChainProvider` | none | Tries providers in order; first hit wins. |

Wire them through `Config\TaxId::$provider`:

```php
public string $provider = 'chain';   // DatabaseProvider, then ViesProvider
public ?int $cacheTtl   = 86400;
```

Or build the stack yourself, outside CodeIgniter:

```php
use Daycry\TaxId\Providers\ChainProvider;
use Daycry\TaxId\Registry\Registry;

$taxid = new TaxId(new Registry(), new ChainProvider([$myProvider, $myFallback]));
```

### VIES, in Practice

VIES is a free public service that forwards each query to the issuing member state. It is slow,
aggressively rate-limited, and individual member states go offline for maintenance. Three
consequences:

- **Cache it.** Set `$cacheTtl`. Registration status rarely changes within a day.
- **Never loop over a table with it.** You will be throttled, and rightly so.
- **Handle `null`.** `MS_UNAVAILABLE` is a normal Tuesday, and it must not become "invalid customer".

It covers the EU-27 plus Northern Ireland (`XI`). Anything else returns `null` without a request
being sent.

Traders may opt out of name disclosure, in which case VIES returns `---`. That becomes `null` here,
not a company called "---".

### Writing Your Own Provider

```php
use Daycry\TaxId\Contracts\LookupProviderInterface;
use Daycry\TaxId\DTO\EntityInfo;

final class MyCrmProvider implements LookupProviderInterface
{
    public function findByTaxId(string $countryCode, string $taxId): ?EntityInfo
    {
        $row = $this->crm->lookup($countryCode, $taxId);

        return $row === null ? null : new EntityInfo(
            countryCode: $countryCode,
            taxId: $taxId,
            name: $row['name'],
            resolvedBy: 'crm',
        );
    }
}
```

Two rules: **never throw** (a network fault is an unknown answer, not an exception) and return `null`
for both "not found" and "couldn't look". Only implement `RegistrationCheckInterface` as well if your
source can genuinely tell those apart — a database that is merely incomplete cannot.

Point `Config\TaxId::$provider` at the class name.

---

## `Config\TaxId`

`php spark taxid:publish` writes `app/Config/TaxId.php` as a subclass, so anything you don't override
keeps inheriting the package default — including properties added in a later release.

| Property | Default | Notes |
| --- | --- | --- |
| `$provider` | `'null'` | `'null'`, `'vies'`, `'database'`, `'chain'`, or an FQCN. |
| `$defaultFormat` | `'print'` | `'compact'`, `'print'`, `'anonymized'`. Used by the helper. |
| `$defaultCountry` | `null` | Assumed when the caller names none. Used by the helper and commands. |
| `$dbGroup` | `null` | `null` leaves CI4's environment-aware default in place. |
| `$table` | `'tax_entities'` | |
| `$cacheTtl` | `null` | `null` disables caching; `0` means *never expires* (CI4's convention). |
| `$viesEndpoint` | EC SOAP URL | Point it at your own proxy if you have one. |
| `$viesTimeout` | `10` | Seconds. A timeout is reported as unknown, never as unregistered. |

Every property is also settable from `.env` with the `taxid.` prefix:

```dotenv
taxid.provider = chain
taxid.cacheTtl = 86400
taxid.defaultCountry = ES
```

---

## The Helper

```php
helper('taxid');
```

Twelve functions wrapping `service('taxid')`. They differ from the facade in exactly one way: the
country falls back to `Config\TaxId::$defaultCountry`, so a single-country application configures it
once.

| Function | Returns |
| --- | --- |
| `taxid_validate($taxId, $country = null, $scheme = null)` | `ValidationResult` |
| `taxid_is_valid($taxId, $country = null, $scheme = null)` | `bool` |
| `taxid_parse($taxId, $country = null)` | `?ParsedTaxId` — never throws |
| `taxid_normalize($taxId, $country = null)` | `string` |
| `taxid_format($taxId, $format = null, $country = null)` | `string` — `null` format uses the config |
| `taxid_resolve($taxId, $country = null)` | `LookupResult` |
| `taxid_country($taxId, $country = null)` | `?string` |
| `taxid_scheme($taxId, $country = null)` | `?string` |
| `taxid_name($taxId, $country = null)` | `?string` — registered name |
| `vat_number($taxId, $country = null)` | `?string` |
| `vat_is_registered($taxId, $country = null)` | `?bool` — three-state |

---

## Spark Commands

```bash
# Validate. Exit 0 valid, 1 invalid.
php spark taxid:validate 12345678Z --country ES
php spark taxid:validate ESB12345674 --json
php spark taxid:validate B12345674 --country ES --scheme cif

# Parse into a table (or JSON).
php spark taxid:parse 11.222.333/0001-81 --country BR
php spark taxid:parse B12345674 --country ES --json

# Resolve through the configured provider.
#   0 = valid (registered, or nothing could answer)
#   1 = invalid tax ID
#   2 = valid but NOT registered
php spark taxid:lookup ESB12345674

# Browse the registry.
php spark taxid:countries
php spark taxid:countries ES
php spark taxid:countries ES --json

# Publish the config.
php spark taxid:publish [--force]
```

`taxid:lookup` exiting 0 on "unknown" is deliberate: a script must be able to tell a bad number from
a service outage, and only the former should fail a pipeline.

---

## Validating a Form Field

```php
use Daycry\TaxId\Enums\ViolationCode;

$result = taxid_validate($this->request->getPost('tax_id'), 'ES');

if (! $result->isValid()) {
    return redirect()->back()->with('error', match ($result->firstViolation()?->code) {
        ViolationCode::Blank             => lang('Tax.required'),
        ViolationCode::CheckDigitFailed  => lang('Tax.checkDigit'),
        ViolationCode::ReservedValue     => lang('Tax.placeholder'),
        ViolationCode::CountryRequired   => lang('Tax.chooseCountry'),
        default                          => lang('Tax.invalid'),
    });
}

// Store the compact form; show the printed one.
$model->save(['tax_id' => taxid_normalize($input, 'ES')]);
```

See [i18n.md](i18n.md) for translating the messages the package itself produces.
