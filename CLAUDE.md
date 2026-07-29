# CLAUDE.md

Guidance for Claude Code (and other agents) working in this repository.

## Project overview

`daycry/taxid` is a tax-identification-number library for CodeIgniter 4 — validation, parsing,
formatting and taxpayer resolution — that is also usable **standalone**, outside any framework.

- Composer package name: `daycry/taxid`. GitHub repo slug: `daycry/taxid`.
- PHP `^8.3`. CodeIgniter 4 `^4.6` is a **dev-only peer dependency** (`require-dev`), never a hard
  `require` — the core works with CI4 not installed at all. The only runtime extension required is
  `ext-simplexml` (the VIES SOAP response parser).
- **Current status: v1.0.** 94 jurisdictions, 145 schemes, 84 national check-digit validators
  covering 95 of those schemes. 1,284 tests / 3,322 assertions green, PHPStan level 8 clean (`src` + `tests`, with the
  `codeigniter/phpstan-codeigniter` extension), PSR-12 clean. Trust `composer test`'s output over the
  numbers written here.
- Sibling package this one deliberately mirrors: [`daycry/iban`](https://github.com/daycry/iban-calculator)
  — same two-layer architecture, same tooling, same doc set, same enforcement test.

## The one thing to understand first

There is **no universal tax-ID algorithm**. Every jurisdiction defines its own length, character set,
structure and — where one exists at all — its own check digit. So the design is:

- a **registry** of per-country rules (`src/Registry/data/countries.php`), data not code;
- **one small validator class per algorithm** (`src/National/`), wired to a scheme by class-string;
- a **data-integrity test** that runs every bundled example through the public facade, so a country
  cannot land broken.

When you add or change a country, you are editing *data*, and the integrity test is your compiler.

## Layered architecture — the unidirectional dependency rule

```
[ CI4 integration ]  Config\TaxId, service('taxid'), taxid_helper, spark taxid:*
        |  (thin adapter, no domain logic)
        v
[ Resolver ]  ResolverInterface -> LookupProviderInterface
        |     (Null | Vies | Database | Cached | Chain) -> LookupResult / EntityInfo
        v
[ Core ]  Registry (country profiles -> schemes) -> Normalizer -> SchemeDetector
          -> Validator (structure + check digit) -> Parser -> Formatter
          zero dependencies, produces ParsedTaxId / ValidationResult
```

**Framework-free — must never import `codeigniter4/*` or reference `CodeIgniter\`:**
- `src/Contracts` — 7 interfaces: `ValidatorInterface`, `ParserInterface`, `ResolverInterface`,
  `RegistryLoaderInterface`, `CheckDigitValidatorInterface`, `LookupProviderInterface`, plus the two
  optional/additive ones, `ReservedValueDetectorInterface` and `RegistrationCheckInterface`.
- `src/Core` — `Normalizer`, `SchemeDetector`, `DetectionResult`, `Validator`, `AnalysisResult`,
  `Parser`, `Formatter`, `CheckDigitValidatorFactory`, and `Core/Checksum/` (`Luhn`, `Mod97`,
  `Iso7064`, `Weights`, `Verhoeff`).
- `src/DTO` — `Violation`, `ValidationResult`, `ParsedTaxId`, `EntityInfo`, `LookupResult`. All
  `final readonly`.
- `src/Enums` — `ViolationCode` (backed `string`, 12 cases), `EntityType` (backed `string`),
  `TaxIdFormat` (pure).
- `src/Exceptions` — `TaxIdException` (`extends \RuntimeException`), `InvalidTaxIdException`
  (`final`, carries the `ValidationResult` via `result()`).
- `src/Registry` — `Registry`, `PhpRegistryLoader`, `CountryProfile`, `TaxIdScheme` +
  `data/countries.php`.
- `src/National` — 84 check-digit validators, all extending `AbstractNationalValidator`.
- `src/Resolver` — `Resolver`.

**Framework-free but living in an un-guarded directory** (guarded individually by the architecture
test): `src/TaxId.php` (the facade), `src/Providers/NullProvider.php`, `src/Providers/ChainProvider.php`.

**Allowed to depend on CI4 (thin adapters, no domain logic):** `src/Config`, `src/Commands`,
`src/Models`, `src/Database`, `src/Helpers`, and `src/Providers/{ViesProvider,DatabaseProvider,CachedProvider}.php`.
`src/Providers/` is deliberately NOT guarded as a directory — it is a split directory, same as
`daycry/iban`'s `Import/`.

**This rule is enforced by a test**, not just convention:
`tests/Architecture/CoreIsFrameworkFreeTest.php` scans its `GUARDED_DIRECTORIES` and `GUARDED_FILES`
for `CodeIgniter\` / `codeigniter4` and fails on a hit. It also scans `data/countries.php` and
carries two self-tests proving the detector isn't a stub. Before adding any `use CodeIgniter\...`,
check which directory you are in; if it is guarded, the dependency belongs one layer up.

## Build, test, lint

```bash
composer update    # NOT `composer install` — see "No composer.lock" below
composer test      # PHPUnit (tests/)
composer analyze   # PHPStan, level 8, paths: src + tests (phpstan.neon)
composer cs        # PHP-CS-Fixer, PSR-12 + strict_types, dry-run (add `fix` locally to apply)
```

CI runs these same three scripts across a PHP 8.3/8.4 matrix (`.github/workflows/phpunit.yml`,
`phpstan.yml`, `code-style.yml`), plus a CodeQL scan of the workflow files themselves
(`codeql.yml`, `language: actions` — PHP has no native CodeQL analyzer).

**No test touches the network.** `ViesProvider` is tested against canned SOAP envelopes through
`tests/_support/RecordingCurlRequest.php`. Keep it that way: a suite that reaches the European
Commission is a suite that fails on a train, and it would hammer a rate-limited public service.

## No `composer.lock` convention

`composer.lock` is intentionally **gitignored**. This is a library, not an application: consumers
resolve their own dependency graph, and CI always runs `composer update` (never `composer install`)
so the matrix reflects the real constraints in `composer.json`, not a pinned snapshot. Composer cache
keys in CI hash `composer.json`. `config.platform.php` is pinned to `8.3.20` for reproducible
platform resolution despite the missing lock file.

## Where things live

```
src/
  Contracts/    7 interfaces (framework-free), 2 of them optional/additive
  Core/         Normalizer, SchemeDetector, DetectionResult, Validator, AnalysisResult, Parser,
                 Formatter, CheckDigitValidatorFactory; Checksum/ (Luhn, Mod97, Iso7064, Weights,
                 Verhoeff)
  DTO/          final readonly VOs: Violation, ValidationResult, ParsedTaxId, EntityInfo, LookupResult
  Enums/        ViolationCode (12 cases), EntityType, TaxIdFormat
  Exceptions/   TaxIdException, InvalidTaxIdException
  Registry/     Registry, PhpRegistryLoader, CountryProfile, TaxIdScheme + data/countries.php
  National/     84 check-digit validators + AbstractNationalValidator
  Resolver/     Resolver — validates, then consults a provider; never the other way round
  Providers/    NullProvider, ChainProvider (framework-free); ViesProvider, DatabaseProvider,
                 CachedProvider (CI4)
  Config/       Config\TaxId, Config\Services (service('taxid')), Config\Registrar
  Commands/     spark: taxid:validate, taxid:parse, taxid:lookup, taxid:countries, taxid:publish,
                 plus CommandSupport (internal)
  Models/       TaxEntityModel (CI4)
  Database/     Migrations/CreateTaxEntitiesTable, Seeds/TaxEntitiesSeeder (empty by design)
  Helpers/      taxid_helper.php — 12 functions (CI4)
  TaxId.php     public facade Daycry\TaxId\TaxId
tests/          mirrors src/ (PHPUnit, CIUnitTestCase where CI4 is involved)
docs/
  api-reference.md        complete per-symbol API reference, code-verified
  usage.md                task-oriented guide: facade, helper, commands, providers, config
  countries.md            the coverage matrix — every country, scheme and whether it is checksummed
  adding-a-country.md     how to extend the registry, and what the integrity test will demand
  algorithms.md           per-algorithm provenance, and why some schemes have no checksum
  architecture.md         concise architecture/status overview
  formatting.md           Compact/Print/Anonymized reference and the mask language
  i18n.md                 message i18n decision (English core, optional CI4 Language/)
  licensing.md            data-authoring and privacy rationale
  roadmap.md              what v1.0 covers and what is deliberately out of scope
  superpowers/specs/2026-07-27-daycry-taxid-v1-design.md   the frozen design spec
CHANGELOG.md    Keep a Changelog history
```

## Coding standards

- PHP `^8.3`, `declare(strict_types=1)` in every file.
- PSR-12 via PHP-CS-Fixer (`.php-cs-fixer.dist.php`): short arrays, alpha-ordered imports, no unused
  imports, single quotes, trailing commas in multiline.
- PHPStan level 8, paths `src` + `tests`. Keep it clean — **no baseline, no `@phpstan-ignore`**
  without a written reason.
- Domain DTOs are `final readonly class` with promoted constructor properties. Enums are backed
  (`string`) when the value is persisted or serialized (`ViolationCode`, `EntityType`), pure
  otherwise (`TaxIdFormat`).
- Method/class signatures for the public API are **verbatim from the design spec**
  (`docs/superpowers/specs/2026-07-27-daycry-taxid-v1-design.md`) — don't improvise names or
  parameter order; if a signature seems wrong, change the spec first.
- TDD is the working method: failing test → minimal implementation → green → commit.
- Violation messages are hardcoded English in the framework-free core, each with a stable
  `messageKey` — see `docs/i18n.md`.

## Things that will bite you

**Scheme codes are strings, not an enum.** Deliberate: a new country would otherwise mean new enum
cases, making every jurisdiction a BC-relevant change to a public type. Look one up with
`Registry::scheme($country, $code)`.

**Registry order is significant.** `SchemeDetector` returns matches in registry order and the
Validator tries them in that order, so the most specific scheme must be declared first (ES: `nif`,
`nie`, `cif`).

**Several schemes can match one value, and that is not an error.** Germany's IdNr and Steuernummer
overlap at eleven digits; a US EIN and SSN are both nine. The Validator accepts the value if **any**
candidate scheme accepts it, and only reports the first candidate's failure when all of them fail.
Pass an explicit `$scheme` when you need one specific reading enforced.

**Prefix stripping is whole-value-first.** `SchemeDetector` matches the untouched value before it
tries stripping a country/VAT prefix. Austria forces the stripping (`ATU12345678` → `U12345678`);
Italy forces the caution (a codice fiscale for someone named "Ital…" legitimately starts `IT`).
Don't "simplify" this into an unconditional `substr()`.

**`registered === null` is load-bearing.** It means *nobody looked* — no provider, country outside
VIES, or the service was unreachable. It must never collapse into `false`, in the DTO, the helper,
the JSON output or the exit code. A VIES outage must not block a real customer.

**An invalid tax ID never reaches a provider.** Validate first, always: it is a useless round-trip
and an avoidable disclosure of someone's data.

**`&` is a legal character.** A Mexican RFC can contain one, so the `IllegalCharacters` check allows
`A-Z0-9&`, and cache keys are scrubbed accordingly.

**Bundled examples are synthetic.** They are checksum-valid but not real taxpayers' identifiers —
publishing those would be a privacy problem, and a synthetic value exercises the same code path. See
`docs/licensing.md`.

## Public API surface (for quick reference)

- Facade `Daycry\TaxId\TaxId` (also the `service('taxid')` return type).
  `__construct(Registry = new Registry(), LookupProviderInterface = new NullProvider())`.
  - Validation: `validate(string|ParsedTaxId $taxId, ?string $country = null, ?string $scheme = null): ValidationResult`,
    `isValid(...): bool`.
  - Parsing/formatting: `normalize(string, ?string): string`, `parse(string, ?string): ParsedTaxId`
    (throws `InvalidTaxIdException`), `tryParse(string, ?string): ?ParsedTaxId`,
    `format(string|ParsedTaxId, TaxIdFormat = Print, ?string): string`.
  - Resolution: `resolve(string|ParsedTaxId, ?string): LookupResult`.
  - Introspection: `supports()`, `countries()`, `country()`, `schemes()`, `scheme()`.
  - Sub-services: `validator()`, `parser()`, `resolver()`, `registry()`.
- Helper (`helper('taxid')`), 12 functions: `taxid_validate()`, `taxid_is_valid()`, `taxid_parse()`,
  `taxid_normalize()`, `taxid_format()`, `taxid_resolve()`, `taxid_country()`, `taxid_scheme()`,
  `taxid_name()`, `vat_number()`, `vat_is_registered()`, plus the internal `taxid_country_default()`.
  These differ from the facade in one way: the country falls back to `Config\TaxId::$defaultCountry`.
- Commands (5): `taxid:validate`, `taxid:parse`, `taxid:lookup` (exit 2 = valid but NOT registered),
  `taxid:countries`, `taxid:publish`.
- `Config\TaxId`: `$provider` (`'null'|'vies'|'database'|'chain'|FQCN`), `$defaultFormat`,
  `$defaultCountry`, `$dbGroup`, `$table`, `$cacheTtl` (`?int`; `null` disables, `0` = never expires),
  `$viesEndpoint`, `$viesTimeout`.

See `docs/api-reference.md` for the complete per-symbol reference and `docs/usage.md` for the
task-oriented guide.
