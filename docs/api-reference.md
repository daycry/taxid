# API Reference

Every public symbol, verified against the source. For task-oriented guidance see
[usage.md](usage.md).

## Contents

- [Facade — `Daycry\TaxId\TaxId`](#facade--daycrytaxidtaxid)
- [Value objects](#value-objects)
- [Enums](#enums)
- [Exceptions](#exceptions)
- [Registry](#registry)
- [Core](#core)
- [Checksum primitives](#checksum-primitives)
- [Contracts](#contracts)
- [Providers](#providers)
- [Resolver](#resolver)
- [CodeIgniter 4 layer](#codeigniter-4-layer)

---

## Facade — `Daycry\TaxId\TaxId`

`final class TaxId implements ValidatorInterface, ParserInterface, ResolverInterface`

```php
public function __construct(
    Registry $registry = new Registry(),
    LookupProviderInterface $provider = new NullProvider(),
)
```

Default-constructible and framework-free. `service('taxid')` returns this class, wired from
`Config\TaxId`.

### Validation

| Method | Returns |
| --- | --- |
| `validate(string\|ParsedTaxId $taxId, ?string $country = null, ?string $scheme = null)` | `ValidationResult` |
| `isValid(string\|ParsedTaxId $taxId, ?string $country = null, ?string $scheme = null)` | `bool` |

Neither throws, for any input. `$country` omitted is inferred from a leading VAT/country prefix, and
from nothing else. `$scheme` given demands that exact scheme.

Passing a `ParsedTaxId` reuses its country, so the second argument is redundant there.

### Parsing and formatting

| Method | Returns | Notes |
| --- | --- | --- |
| `normalize(string $taxId, ?string $country = null)` | `string` | Compact form; safe on any input. |
| `parse(string $taxId, ?string $country = null)` | `ParsedTaxId` | **Throws** `InvalidTaxIdException`. |
| `tryParse(string $taxId, ?string $country = null)` | `?ParsedTaxId` | Never throws. |
| `format(string\|ParsedTaxId $taxId, TaxIdFormat $f = TaxIdFormat::Print, ?string $country = null)` | `string` | Never throws. |

### Resolution

| Method | Returns |
| --- | --- |
| `resolve(string\|ParsedTaxId $taxId, ?string $country = null)` | `LookupResult` |

Validates first; an invalid value never reaches the provider.

### Registry introspection

| Method | Returns |
| --- | --- |
| `supports(string $country)` | `bool` — case-insensitive |
| `countries()` | `string[]` — sorted |
| `country(string $country)` | `CountryProfile` — throws `OutOfBoundsException` |
| `schemes(string $country)` | `TaxIdScheme[]` — registry order |
| `scheme(string $country, string $scheme)` | `TaxIdScheme` — throws `OutOfBoundsException` |

### Sub-services

`validator(): Validator` · `parser(): Parser` · `resolver(): Resolver` · `registry(): Registry`

---

## Value objects

All `final readonly`, in `Daycry\TaxId\DTO`.

### `Violation`

```php
public function __construct(
    public ViolationCode $code,
    public string $messageKey,   // 'taxid.violation.check_digit_failed'
    public string $message,      // hardcoded English
)
```

### `ValidationResult`

```php
public function __construct(public bool $valid, public array $violations)   // Violation[]
```

| Method | Returns |
| --- | --- |
| `isValid()` | `bool` |
| `violations()` | `Violation[]` |
| `firstViolation()` | `?Violation` |
| `error()` | `?string` — the first message, or `null` when valid |
| `errorCode()` | `?string` — the first code's value, or `null` when valid |

### `ParsedTaxId`

```php
public function __construct(
    public string $countryCode,      // 'ES'
    public string $scheme,           // 'nif'
    public string $schemeName,       // 'NIF — Número de Identificación Fiscal (personas físicas)'
    public EntityType $entityType,
    public bool $vat,                // doubles as the country's VAT number
    public string $compact,          // 'B12345674'
    public string $input,            // exactly what the caller passed
    public ?string $checkDigit = null,
    public ?string $mask = null,
    public ?string $vatPrefix = null,
)
```

| Method | Returns |
| --- | --- |
| `format(TaxIdFormat $f = TaxIdFormat::Print)` | `string` |
| `vatNumber()` | `?string` — `'ESB12345674'`; `null` unless `$vat` **and** `$vatPrefix` |
| `__toString()` | `string` — the compact form |

### `EntityInfo`

```php
public function __construct(
    public string $countryCode,
    public string $taxId,               // compact form
    public ?string $name = null,
    public ?string $address = null,
    public ?EntityType $entityType = null,
    public ?string $resolvedBy = null,  // 'vies', 'database', 'cache:vies', …
)
```

`withResolvedBy(string $resolvedBy): self` returns a copy with different provenance; the original is
untouched.

### `LookupResult`

```php
public function __construct(
    public ?ParsedTaxId $taxId,       // null only when validation failed
    public bool $valid,
    public ?bool $registered,         // three-state — see below
    public ?EntityInfo $entity,
    public ValidationResult $validation,
)
```

| Method | Returns |
| --- | --- |
| `isValid()` | `bool` |
| `isRegistered()` | `?bool` — `true` / `false` / **`null` = nobody could answer** |
| `taxId()` | `?ParsedTaxId` |
| `entity()` | `?EntityInfo` |
| `name()` | `?string` |
| `address()` | `?string` |
| `validation()` | `ValidationResult` |

`null` must never be treated as `false`. See [usage.md](usage.md#null-is-not-false).

---

## Enums

### `ViolationCode: string`

`Blank` · `TooShort` · `TooLong` · `IllegalCharacters` · `UnknownCountry` · `CountryRequired` ·
`BadLength` · `MalformedStructure` · `CheckDigitFailed` · `SchemeMismatch` · `ReservedValue` ·
`NothingToValidate`

Backing values are snake_case and part of the public API — they surface in JSON output. Cases may be
added; they are never renamed or repurposed. Full table with the check ordering in
[usage.md](usage.md#the-twelve-violation-codes).

### `EntityType: string`

`Natural` (`'natural'`) · `Legal` (`'legal'`) · `Both` (`'both'`)

`accepts(self $other): bool` — whether a value of this type can belong to `$other`. `Both` is
compatible with everything, in both directions.

### `TaxIdFormat`

`Compact` · `Print` · `Anonymized`. A pure enum: these cases are never persisted, only rendered. See
[formatting.md](formatting.md).

---

## Exceptions

### `TaxIdException extends \RuntimeException`

Base class for everything this package throws.

### `InvalidTaxIdException extends TaxIdException` (final)

Thrown by `parse()`. Carries the full result:

```php
public function result(): ValidationResult
```

The message defaults to the first violation's message, or `'Invalid tax ID'` when there is none.

---

## Registry

`Daycry\TaxId\Registry`.

### `Registry` (final)

```php
public const string VERSION = '2026-07 (independently authored from public national formats)';

public function __construct(RegistryLoaderInterface $loader = new PhpRegistryLoader())
```

| Method | Returns |
| --- | --- |
| `has(string $cc)` | `bool` — case-insensitive |
| `get(string $cc)` | `CountryProfile` — throws `OutOfBoundsException` |
| `scheme(string $cc, string $code)` | `TaxIdScheme` — throws `OutOfBoundsException` |
| `all()` | `array<string, CountryProfile>` |
| `countries()` | `string[]` — sorted |
| `countryForVatPrefix(string $prefix)` | `?string` — `'EL'` → `'GR'` |
| `schemeCount()` | `int` |

Hydration is lazy and memoized: constructing a `Registry` reads nothing.

### `CountryProfile` (final readonly)

```php
public string $countryCode;
public string $name;
public ?string $vatPrefix;
public array $schemes;   // array<string, TaxIdScheme>, in registry order
```

`hasScheme(string): bool` · `scheme(string): TaxIdScheme` · `vatSchemes(): TaxIdScheme[]` ·
`lengths(): int[]`

### `TaxIdScheme` (final readonly)

```php
public string $countryCode;
public string $code;                 // 'nif'
public string $name;
public EntityType $entityType;
public bool $vat;
public string $pattern;              // anchored PCRE over the compact form
public array $lengths;               // int[]
public ?string $mask;                // '#' = one value character
public ?string $checkDigitClass;     // class-string<CheckDigitValidatorInterface>|null
public ?array $checkDigitOffset;     // [offset, length]; negative counts from the end
public array $examples;              // string[] — synthetic, checksum-valid
public ?string $notes;
```

`matches(string): bool` · `hasCheckDigit(): bool` · `extractCheckDigit(string): ?string`

### `PhpRegistryLoader` (final)

The default loader: `require`s `data/countries.php`, resolved relative to itself.

---

## Core

`Daycry\TaxId\Core`.

### `Validator` (final)

```php
public const int MAX_INPUT_LENGTH = 64;
public const int MIN_LENGTH = 4;

public function __construct(
    Registry $registry = new Registry(),
    Normalizer $normalizer = new Normalizer(),
    CheckDigitValidatorFactory $validators = new CheckDigitValidatorFactory(),
    ?SchemeDetector $detector = null,
)
```

Beyond the contract: `analyze(...): AnalysisResult` (verdict + detection + winning scheme),
`detect(string, ?string): DetectionResult`, `registry()`, `detector()`, `normalizer()`.

### `Parser` (final)

```php
public function __construct(Validator $validator = new Validator(), Formatter $formatter = new Formatter())
```

Implements `ParserInterface`, plus `validator(): Validator`.

### `Normalizer` (final)

`normalize(string $taxId): string` — uppercases and strips `\s . - / \ _ ( ) [ ] : , '`. Does not
validate and does not drop unrecognised characters.

### `SchemeDetector` (final)

```php
public function __construct(Registry $registry = new Registry())
```

| Method | Returns |
| --- | --- |
| `detect(string $normalized, ?string $country = null)` | `DetectionResult` |
| `inferCountry(string $normalized)` | `?string` |
| `schemesMatching(CountryProfile $p, string $compact)` | `TaxIdScheme[]` |

### `DetectionResult` (final readonly)

```php
public ?string $countryCode;
public ?CountryProfile $profile;
public string $compact;
public array $matches;   // TaxIdScheme[], registry order
```

`countryKnown(): bool` · `hasAnyMatch(): bool` · `scheme(?string $requested = null): ?TaxIdScheme`

### `AnalysisResult` (final readonly)

```php
public ValidationResult $validation;
public DetectionResult $detection;
public ?TaxIdScheme $scheme;   // the scheme that accepted the value
```

`isValid(): bool`

### `Formatter` (final)

`format(string $compact, ?string $mask = null, TaxIdFormat $format = TaxIdFormat::Print): string` —
never throws.

### `CheckDigitValidatorFactory` (final)

`make(string $class): CheckDigitValidatorInterface` — one memoized instance per class. Throws
`TaxIdException` when the class is missing or doesn't implement the contract, which means the
registry data is wrong.

---

## Checksum primitives

`Daycry\TaxId\Core\Checksum`. All static, pure and total.

### `Luhn`

`isValid(string $digits): bool` · `checkDigit(string $payload): int`

### `Verhoeff`

`isValid(string $digits): bool` — `$digits` is the payload **including** its trailing check digit.
`checkDigit(string $payload): int` — `$payload` is the same value **without** it. Needed because
Luhn and `Weights` both work in ℤ/10ℤ, where an adjacent transposition whose two digits differ by
five can cancel itself out; Verhoeff's non-commutative D₅ multiplication misses none. Luxembourg's
matricule pairs a Verhoeff digit with a Luhn digit over the same base for exactly that reason.

### `Mod97`

`remainder(string $numeric): int` — digit by digit, so arbitrarily long strings work without bcmath.
Non-digits are skipped.

### `Iso7064`

`mod1110(string $payload): int` · `isValidMod1110(string $full): bool` ·
`mod112(string $payload): string` (`'0'`–`'9'` or `'X'`) · `mod3736(string $payload): string`

### `Weights`

| Method | Purpose |
| --- | --- |
| `sum(string $digits, array $weights): int` | Positional weighting. |
| `cyclicSum(string $digits, array $weights, bool $rightToLeft = true): int` | Repeating weights. |
| `mod11ComplementDigit(int $sum, ?int $tenAs = null, ?int $elevenAs = 0): ?int` | `11 − (sum mod 11)` with the overflows folded. |
| `mod11RemainderDigit(int $sum): int` | `sum mod 11`, remainder below 2 gives 0. |

---

## Contracts

`Daycry\TaxId\Contracts`. Seven interfaces; the last two are optional and additive.

| Interface | Methods |
| --- | --- |
| `ValidatorInterface` | `validate()`, `isValid()` |
| `ParserInterface` | `normalize()`, `parse()`, `tryParse()`, `format()` |
| `ResolverInterface` | `resolve()` |
| `RegistryLoaderInterface` | `load(): array<string, array<string, mixed>>` |
| `CheckDigitValidatorInterface` | `supports(string $countryCode, string $scheme): bool`, `verify(string $compact): bool` |
| `LookupProviderInterface` | `findByTaxId(string $countryCode, string $taxId): ?EntityInfo` |
| `ReservedValueDetectorInterface` | `isReserved(string $compact): bool` |
| `RegistrationCheckInterface` | `isRegistered(string $countryCode, string $taxId): ?bool` |

`CheckDigitValidatorInterface` implementations must be stateless and must never throw.
`LookupProviderInterface` implementations must never throw and must return `null` for both "not
found" and "couldn't look". Only implement `RegistrationCheckInterface` when your source can
genuinely tell those apart.

### `AbstractNationalValidator`

`Daycry\TaxId\National\AbstractNationalValidator` — base for the bundled validators. Subclasses
declare `protected const array COUNTRIES` and `protected const array SCHEMES` and implement
`verify()`; `supports()` comes for free.

---

## Providers

`Daycry\TaxId\Providers`.

| Class | Implements | Framework |
| --- | --- | --- |
| `NullProvider` | `LookupProviderInterface` | none |
| `ChainProvider` | `LookupProviderInterface`, `RegistrationCheckInterface` | none |
| `ViesProvider` | both | CI4 (`CURLRequest`) |
| `DatabaseProvider` | `LookupProviderInterface` only | CI4 |
| `CachedProvider` | both | CI4 (`CacheInterface`) |

```php
new ChainProvider(array $providers)                       // + providers(): LookupProviderInterface[]
new ViesProvider(CURLRequest $client, string $endpoint = ViesProvider::DEFAULT_ENDPOINT, int $timeout = 10)
new DatabaseProvider(TaxEntityModel $model)
new CachedProvider(LookupProviderInterface $inner, CacheInterface $cache, int $ttl = 3600,
                   string $prefix = 'taxid_entity_', string $registeredPrefix = 'taxid_registered_')
```

`ViesProvider` covers the EU-27 plus `XI`; anything else returns `null` with no request sent. It
memoizes per instance so `findByTaxId()` and `isRegistered()` share one round-trip.

`DatabaseProvider` deliberately does **not** implement `RegistrationCheckInterface`: a missing row is
not evidence a taxpayer doesn't exist.

`CachedProvider` caches hits but never misses, and caches a definite `true`/`false` registration but
never a `null`.

---

## Resolver

### `Daycry\TaxId\Resolver\Resolver` (final)

```php
public function __construct(
    Parser $parser = new Parser(),
    LookupProviderInterface $provider = new NullProvider(),
)
```

`resolve(string|ParsedTaxId, ?string): LookupResult` · `provider(): LookupProviderInterface`

---

## CodeIgniter 4 layer

### `Config\TaxId`

`$provider` · `$defaultFormat` · `$defaultCountry` · `$dbGroup` · `$table` · `$cacheTtl` ·
`$viesEndpoint` · `$viesTimeout`. Defaults and `.env` overrides in
[usage.md](usage.md#configtaxid).

### `Config\Services`

```php
public static function taxid(bool $getShared = true): TaxId
public static function config(): Config\TaxId
```

Auto-discovered. `config()` resolves by **short name** so a published `app/Config/TaxId.php` wins.

### `Config\Registrar`

Present for discovery symmetry; declares no `Autoload()` hook, because CI4 never merges one into
`Config\Autoload`.

### Helper (`helper('taxid')`)

Twelve functions — see [usage.md](usage.md#the-helper). They differ from the facade only in falling
back to `Config\TaxId::$defaultCountry` and `$defaultFormat`.

### Commands

| Command | Exit codes |
| --- | --- |
| `taxid:validate <taxid> [--country] [--scheme] [--json]` | 0 valid, 1 invalid |
| `taxid:parse <taxid> [--country] [--json]` | 0 parsed, 1 not |
| `taxid:lookup <taxid> [--country] [--json]` | 0 valid, 1 invalid, **2 valid but not registered** |
| `taxid:countries [<country>] [--json]` | 0, 1 on an unknown country |
| `taxid:publish [--force]` | 0, 1 on refusal or write failure |

### `Models\TaxEntityModel`

```php
public function __construct(?string $table = null, ?string $dbGroup = null)
public function findByTaxId(string $countryCode, string $taxId): ?array
```

Table `tax_entities`; fields `country_code`, `tax_id`, `name`, `address`, `entity_type`, `source`,
plus timestamps. Unique on `(country_code, tax_id)`.

### Database

`Database\Migrations\CreateTaxEntitiesTable` creates the table **empty**.
`Database\Seeds\TaxEntitiesSeeder` inserts nothing, by design — see
[licensing.md](licensing.md).
