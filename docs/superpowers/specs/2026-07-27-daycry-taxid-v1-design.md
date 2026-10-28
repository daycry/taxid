# `daycry/taxid` v1.0 — design spec

Status: **approved** (2026-07-27), **implemented and reconciled**. This document is the frozen
contract for the v1.0 public API: class names, method names and parameter order are taken verbatim
from here by the implementation. If a signature here looks wrong, change it here first — never
diverge silently.

Three things changed during implementation and are reflected below, each for a reason found by
writing the code:

1. **`BadLength` is checked before `MalformedStructure`** (steps 8 and 9 in §5). More specific first,
   consistent with the rest of the ordering.
2. **`Validator::analyze()` and `Core\AnalysisResult` were added.** `Parser` needs the winning scheme
   as well as the verdict, and re-deriving it independently is exactly how a parser and a validator
   drift apart.
3. **Several schemes may match one value, and all of them are tried.** Germany's IdNr and
   Steuernummer overlap at eleven digits; a US EIN and SSN are both nine. The value is valid if any
   candidate accepts it. This was not foreseen in the original draft and is now §5's rule.

Sibling package this one deliberately mirrors: [`daycry/iban`](https://github.com/daycry/iban-calculator)
(same two-layer architecture, same tooling, same doc set, same enforcement test).

---

## 1. Problem statement

There is no universal tax-identification-number algorithm. Every jurisdiction defines its own length,
character set, structure and — where one exists at all — its own check-digit algorithm. A single
`isValidTaxId()` regex is therefore a lie. What *is* tractable is a **registry of per-country rules**
plus **per-scheme check-digit validators**, wrapped in one small, stable API.

This package validates a tax ID **offline** (structure + check digit) and — opt-in — verifies that an
EU VAT number **actually exists** through VIES.

Explicit non-goal: proving that a *non-VAT* tax ID belongs to a real taxpayer. Almost no jurisdiction
publishes that data. `validate()` answers "could this have been issued?", never "was it issued?".

## 2. Vocabulary

| Term | Meaning |
| --- | --- |
| **Country** | ISO 3166-1 alpha-2 code. The registry key. |
| **Scheme** | One tax-ID *kind* within a country, identified by a lowercase code: `nif`, `nie`, `cif`, `vat`, `cpf`, `cnpj`, `rfc`, `ein`, `ssn`… A country has 1..N schemes. |
| **Compact form** | The normalized value: uppercased, separators stripped, VAT country prefix removed. The canonical form everything else is derived from. |
| **Check digit** | A verifier character (digit or letter) computed from the rest of the value. Present in most, but not all, schemes. |
| **Structural-only scheme** | A scheme with a documented format but no public check-digit algorithm. Validation stops at length + pattern. |

## 3. Architecture — the unidirectional dependency rule

```
[ CI4 integration ]  Config\TaxId, service('taxid'), taxid_helper, spark taxid:*
        |  (thin adapter, no domain logic)
        v
[ Resolver ]  ResolverInterface -> LookupProviderInterface
        |     (NullProvider | ViesProvider | DatabaseProvider | CachedProvider | ChainProvider)
        |     produces LookupResult / EntityInfo
        v
[ Core ]  Registry (country profiles -> schemes) -> Normalizer -> SchemeDetector
          -> Validator (structure + check digit) -> Parser -> Formatter
          zero dependencies, usable outside CI4, produces ParsedTaxId / ValidationResult
```

**Framework-free (must never import `codeigniter4/*` nor reference `CodeIgniter\`):**
`src/Contracts`, `src/Core` (incl. `Core/Checksum`), `src/DTO`, `src/Enums`, `src/Exceptions`,
`src/Registry`, `src/National`, `src/Resolver`, plus the individual files `src/TaxId.php` and
`src/Providers/NullProvider.php` / `src/Providers/ChainProvider.php`.

**Allowed to depend on CI4 (thin adapters only):** `src/Config`, `src/Commands`, `src/Models`,
`src/Database`, `src/Helpers`, and `src/Providers/{ViesProvider,DatabaseProvider,CachedProvider}.php`.

Enforced by `tests/Architecture/CoreIsFrameworkFreeTest.php`, which greps the guarded directories and
the guarded individual files for `CodeIgniter\` / `codeigniter4` and fails on a hit. It also carries
two self-tests proving the detector is not a stub.

## 4. Domain model

### 4.1 Enums

```php
enum ViolationCode: string {
    case Blank             = 'blank';
    case TooShort          = 'too_short';
    case TooLong           = 'too_long';
    case IllegalCharacters = 'illegal_characters';
    case UnknownCountry    = 'unknown_country';
    case CountryRequired   = 'country_required';   // no country given and none inferable
    case BadLength         = 'bad_length';
    case MalformedStructure = 'malformed_structure';
    case CheckDigitFailed  = 'check_digit_failed';
    case SchemeMismatch    = 'scheme_mismatch';    // valid, but not the scheme the caller demanded
    case ReservedValue     = 'reserved_value';     // well-formed but never issued (e.g. SSN 000-…)
    case NothingToValidate = 'nothing_to_validate';
}

enum EntityType: string { case Natural = 'natural'; case Legal = 'legal'; case Both = 'both'; }

enum TaxIdFormat { case Compact; case Print; case Anonymized; }
```

`EntityType` answers "does this scheme identify a person, a company, or either?". `TaxIdFormat::Print`
renders the country's conventional mask (`123.456.789-09`); `Anonymized` keeps the last two characters
and masks the rest (`*********09`), so a value can be logged without leaking it.

**Why the scheme code is a `string`, not an enum:** adding a country would otherwise mean adding enum
cases, i.e. every new jurisdiction becomes a BC-relevant change to a public type. Scheme codes are
registry data (`'nif'`, `'cpf'`, …), documented and stable, and `Registry::scheme()` is the typed way
to look one up.

### 4.2 DTOs (all `final readonly`)

```php
final readonly class Violation {
    public function __construct(public ViolationCode $code, public string $messageKey, public string $message) {}
}

final readonly class ValidationResult {
    /** @param Violation[] $violations */
    public function __construct(
        public bool $valid,
        public array $violations,
        public ?bool $checksumVerified = null,
        public ?string $scheme = null,
    ) {}
    public function isValid(): bool;
    /** @return Violation[] */
    public function violations(): array;
    public function firstViolation(): ?Violation;
    public function error(): ?string;   // firstViolation()->message, or null when valid
}

final readonly class ParsedTaxId {
    public function __construct(
        public string $countryCode,   // 'ES'
        public string $scheme,        // 'nif'
        public string $schemeName,    // 'NIF — Número de Identificación Fiscal'
        public EntityType $entityType,
        public bool $vat,             // usable as a VAT number in this country
        public string $compact,       // 'B12345678'
        public string $input,         // exactly what the caller passed
        public ?string $checkDigit,   // extracted when the scheme defines its position
    ) {}
    public function format(TaxIdFormat $f = TaxIdFormat::Print): string;
    public function vatNumber(): ?string;   // 'ESB12345678' when $vat, else null
    public function __toString(): string;   // $compact
}

final readonly class EntityInfo {
    public function __construct(
        public string $countryCode,
        public string $taxId,          // compact form
        public ?string $name,
        public ?string $address,
        public ?EntityType $entityType,
        public ?string $resolvedBy,    // provenance: 'vies', 'database', 'cache:vies', …
    ) {}
}

final readonly class LookupResult {
    public function __construct(
        public ParsedTaxId $taxId,
        public bool $valid,            // offline validity (structure + check digit)
        public ?bool $registered,      // true/false when a provider could answer; null = unknown
        public ?EntityInfo $entity,
        public ValidationResult $validation,
    ) {}
    public function isValid(): bool;
    public function isRegistered(): ?bool;
    public function entity(): ?EntityInfo;
    public function name(): ?string;
}
```

2026-10 validation assurance addition (backward compatible): successful engine results
identify the accepting `scheme` and set `checksumVerified` to true only when that value's
complete checksum was checked. Structure/range-only results and exceptional formats
without a verifiable complete checksum report false. Invalid results and manually
constructed results without supplied metadata retain null. Registration remains the
independent, nullable `LookupResult::registered` outcome.

`TaxIdScheme` gains a trailing optional `bool $checksum = true` constructor parameter;
`hasCheckDigit()` requires both a verifier class and this flag. Custom registry loaders
can set `checksum => false` while retaining their range verifier. An optional
`Contracts\ChecksumAwareValidatorInterface` extends the existing check-digit contract
with `hasChecksum(string $compact): bool` for schemes with exceptional non-checksummed
variants; existing custom validators need not implement it. The existing explicit
`$scheme` argument continues to prevent fallback to a different identifier type.

`registered === null` is load-bearing: with the default `NullProvider` nothing is ever looked up, and
"unknown" must never be reported as "not registered".

### 4.3 Registry

```php
final readonly class TaxIdScheme {
    public function __construct(
        public string $countryCode,
        public string $code,            // 'nif'
        public string $name,            // 'NIF — Número de Identificación Fiscal'
        public EntityType $entityType,
        public bool $vat,
        public string $pattern,         // full PCRE, anchored, applied to the compact form
        public ?string $mask,           // print mask, '#' = one value char, anything else literal
        public ?string $checkDigitClass, // class-string<CheckDigitValidatorInterface>|null
        public ?array $checkDigitOffset, // [offset, length] within the compact form, or null
        public array $examples,          // string[] — real, valid values; the integrity test runs them
    ) {}
    public function matches(string $compact): bool;
    public function hasCheckDigit(): bool;
}

final readonly class CountryProfile {
    public function __construct(
        public string $countryCode,
        public string $name,             // English country name
        public ?string $vatPrefix,       // 'ES', 'EL' for Greece, 'XI' for Northern Ireland; null if none
        public array $schemes,           // array<string, TaxIdScheme> keyed by scheme code
    ) {}
    public function scheme(string $code): TaxIdScheme;   // throws OutOfBoundsException
    public function hasScheme(string $code): bool;
    /** @return TaxIdScheme[] */
    public function vatSchemes(): array;
}

final class Registry {
    public const string VERSION = '2026-07 (…countries; independently authored)';
    public function __construct(private readonly RegistryLoaderInterface $loader = new PhpRegistryLoader());
    public function has(string $cc): bool;
    public function get(string $cc): CountryProfile;              // throws OutOfBoundsException
    public function scheme(string $cc, string $code): TaxIdScheme; // throws OutOfBoundsException
    /** @return array<string, CountryProfile> */
    public function all(): array;
    /** @return string[] */
    public function countries(): array;
    public function countryForVatPrefix(string $prefix): ?string;
}
```

Data lives in `src/Registry/data/countries.php`, keyed by ISO 3166-1 alpha-2, hand-authored from
publicly documented formats — never copied from a third-party dataset (see `docs/licensing.md`).
Scheme ordering inside a country is significant: `SchemeDetector` returns the **first** match, so the
more specific scheme must come first (ES: `nif` before `nie` before `cif`).

## 5. Core pipeline

`Core\Normalizer::normalize(string $value, ?string $country = null): string`
Uppercases, strips whitespace and the conventional separators `. - / \ ( ) : ,` and a leading
`VAT`/`TAX`/`NIF`… label is **not** stripped (too ambiguous across languages) — only a leading country
VAT prefix is, and only when it matches `$country` or is a known VAT prefix. Never validates, never
drops unexpected characters: those survive so the Validator can report `IllegalCharacters`.

`Core\SchemeDetector`
- `inferCountry(string $rawValue): ?string` — leading two letters that are a known VAT prefix (`EL`→GR,
  `XI`→GB-NI…) **and** followed by at least one digit.
- `detect(CountryProfile $p, string $compact, ?string $scheme = null): ?TaxIdScheme` — first matching
  scheme, honouring an explicitly requested scheme code.

`Core\Validator::validate(string|ParsedTaxId $value, ?string $country = null, ?string $scheme = null): ValidationResult`
Fixed, short-circuiting check order (cheapest and most specific first):

1. `TooLong` — raw input over 64 chars (fail fast before any work)
2. `Blank` — empty after normalization
3. `TooShort` — under 4 characters
4. `IllegalCharacters` — anything outside `A-Z0-9` survives normalization
5. `CountryRequired` — no `$country` given and none inferable from a VAT prefix
6. `UnknownCountry` — country not in the registry
7. `SchemeMismatch` — an explicit `$scheme` was requested and the value doesn't match it, **but** does
   match another scheme of that country
8. `BadLength` — no scheme matched, and the length fits none of the country's schemes either
9. `MalformedStructure` — no scheme matched, but the length was plausible
10. `ReservedValue` — structurally fine but in a documented never-issued range
11. `CheckDigitFailed` — the scheme's check-digit validator rejects it

Steps 10 and 11 run against **every** matching scheme, in registry order, and the value is valid if
any of them accepts it; only when all fail is the first candidate's failure reported. Passing an
explicit `$scheme` narrows the candidates to that one.

`isValid()`, `validate()` and `tryParse()` **never throw**, on any input.

`Core\Parser` — `normalize()`, `parse()` (throws `InvalidTaxIdException` carrying the
`ValidationResult`), `tryParse()` (returns `null`), `format()`.

`Core\Formatter::format(string $compact, ?string $mask, TaxIdFormat $f): string`
`Compact` returns the compact form; `Print` fills the mask's `#` placeholders left-to-right and falls
back to the compact form when the mask's placeholder count differs from the value length (variable
length schemes) or the mask is `null`; `Anonymized` keeps the last two characters and replaces every
earlier one with `*`.

`Core\Checksum` — shared, side-effect-free algorithm primitives so 90 countries don't each hand-roll
one: `Luhn`, `Mod11` (configurable weights/direction/remainder mapping), `Mod97`, `Iso7064`
(MOD 11,10 / MOD 11-2 / MOD 37,36), `WeightedSum`.

`Contracts\CheckDigitValidatorInterface`
```php
public function supports(string $countryCode, string $scheme): bool;
public function verify(string $compact): bool;   // receives the compact form; never throws
```
A scheme with no validator is a **skip, never a failure**.

## 6. Resolution (opt-in, mirrors `daycry/iban`)

```php
interface LookupProviderInterface {
    public function findByTaxId(string $countryCode, string $taxId): ?EntityInfo;
}
```

- `NullProvider` — the default. Always `null`, zero dependencies, framework-free.
- `ViesProvider` — opt-in EU VAT existence check against the VIES SOAP endpoint (`checkVat`). CI4
  `curlrequest`; a network/SOAP fault is reported as **unknown**, never as "not registered". Tests use
  a recording fake, never the network.
- `DatabaseProvider` — the local `tax_entities` table via `Models\TaxEntityModel`.
- `CachedProvider` — wraps any provider with CI4's cache; only hits are cached, never misses.
- `ChainProvider` — tries providers in order, first hit wins; framework-free.

`Resolver::resolve(string|ParsedTaxId $value, ?string $country = null): LookupResult` validates first
and **never consults a provider for an invalid value**.

## 7. Public API — `Daycry\TaxId\TaxId`

```php
public function __construct(
    Registry $registry = new Registry(),
    LookupProviderInterface $provider = new NullProvider(),
);

// validation
public function validate(string|ParsedTaxId $taxId, ?string $country = null, ?string $scheme = null): ValidationResult;
public function isValid(string|ParsedTaxId $taxId, ?string $country = null, ?string $scheme = null): bool;

// parsing / formatting
public function normalize(string $taxId, ?string $country = null): string;
public function parse(string $taxId, ?string $country = null): ParsedTaxId;      // throws InvalidTaxIdException
public function tryParse(string $taxId, ?string $country = null): ?ParsedTaxId;
public function format(string|ParsedTaxId $taxId, TaxIdFormat $f = TaxIdFormat::Print, ?string $country = null): string;

// resolution
public function resolve(string|ParsedTaxId $taxId, ?string $country = null): LookupResult;

// registry introspection
public function supports(string $country): bool;
/** @return string[] */
public function countries(): array;
/** @return TaxIdScheme[] */
public function schemes(string $country): array;
public function scheme(string $country, string $scheme): TaxIdScheme;

// sub-services
public function validator(): Validator;
public function parser(): Parser;
public function resolver(): Resolver;
public function registry(): Registry;
```

The value is always the **first** argument (as in `daycry/iban`) and the country is optional: omitted,
it is inferred from a leading VAT prefix (`ESB12345678`, `DE123456789`, `EL094259216`). Passing a
country and a prefixed value is fine as long as they agree.

## 8. CodeIgniter 4 layer

- `Config\TaxId`: `$provider` (`'null'|'vies'|'database'|FQCN`), `$defaultFormat`, `$defaultCountry`,
  `$dbGroup`, `$table` (`tax_entities`), `$cacheTtl` (`?int`; `null` disables, `0` = never expires),
  `$viesEndpoint`, `$viesTimeout`.
- `Config\Services::taxid()` — auto-discovered; wires the provider, then the optional cache layer.
- `Config\Registrar` — present for discovery symmetry; declares no `Autoload()` hook (dead code in CI4).
- `Helpers/taxid_helper.php` — `taxid_validate()`, `taxid_is_valid()`, `taxid_parse()`,
  `taxid_format()`, `taxid_normalize()`, `taxid_resolve()`, `taxid_country()`, `taxid_scheme()`,
  `taxid_name()`, `vat_number()`, `vat_is_registered()`.
- Commands: `taxid:validate`, `taxid:parse`, `taxid:lookup`, `taxid:countries`, `taxid:publish`.
- `Models\TaxEntityModel` + migration `CreateTaxEntitiesTable` + `TaxEntitiesSeeder` (intentionally
  empty — this package ships no taxpayer data).

## 9. Testing strategy

- TDD: failing test → minimal implementation → green.
- `tests/Registry/CountriesDataIntegrityTest.php` is the backbone: for **every** scheme of **every**
  country it asserts the pattern compiles, the mask's placeholder count is consistent, the
  `checkDigitClass` exists and implements the contract and `supports()` the scheme, `examples` is
  non-empty, and **every example validates green through the full public facade**. A country cannot
  land broken.
- Every check-digit validator gets its own test with real valid values **and** digit-flipped negatives
  (proving the algorithm actually discriminates, not just accepts).
- `tests/Architecture/CoreIsFrameworkFreeTest.php` enforces §3.
- No test touches the network. Provider tests use fakes.

## 10. Explicit non-goals for v1.0

- No country-guessing from an unprefixed value (ambiguous by construction — a 9-digit number is a
  valid something almost everywhere).
- No non-VAT registry lookups (the data is not public).
- No i18n of violation messages in the core: hardcoded English + a stable `messageKey` per violation,
  exactly as `daycry/iban` does (see `docs/i18n.md`).
- No `composer.lock` (library convention; CI runs `composer update`).
