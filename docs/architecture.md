# Architecture

Two layers, one direction, one test that keeps it that way.

## The problem this shape solves

A tax-ID library is mostly *data*: ninety-odd jurisdictions, each with its own format and, sometimes,
its own arithmetic. Data of that size rots quietly — a mistyped pattern, a validator wired to the
wrong scheme, an example that never actually passed. And the code around it is the sort that gets
copied into a hurry-up project with no framework at all.

So the design has two goals: keep the validation engine dependency-free, and make bad data
impossible to merge.

## Two layers, one direction

```
[ CI4 integration ]  Config\TaxId, service('taxid'), taxid_helper, spark taxid:*
        |  thin adapter — no domain logic lives here
        v
[ Resolver ]  ResolverInterface -> LookupProviderInterface
        |     Null | Vies | Database | Cached | Chain
        |     produces LookupResult / EntityInfo
        v
[ Core ]  Registry (country profiles -> schemes)
              -> Normalizer -> SchemeDetector -> Validator -> Parser -> Formatter
          zero dependencies, produces ParsedTaxId / ValidationResult
```

Dependencies point **down only**. The Core knows nothing about the Resolver; the Resolver knows
nothing about CodeIgniter. You can `new Daycry\TaxId\TaxId()` in a plain script and everything above
the resolution layer works untouched.

## The Core, step by step

**`Normalizer`** — uppercases and strips the separators humans type (`. - / \ _ ( ) [ ] : , '`). It
does not validate and does not drop characters it doesn't recognise: an `@` survives so the Validator
can report `IllegalCharacters` rather than a baffling length error.

**`SchemeDetector`** — answers *which country* and *which scheme*. Two rules do the heavy lifting:

1. A country is only inferred from a leading two-letter prefix the registry recognises. Never from
   the digits. Nine digits is a plausible tax ID in dozens of places, so guessing would produce
   confident nonsense.
2. The value is matched **whole first**, and only then with a prefix stripped. Austria forces the
   stripping (`ATU12345678` → `U12345678`, because the national number really does start with `U`);
   Italy forces the caution (a codice fiscale for someone named "Ital…" legitimately begins `IT`).

**`Validator`** — a fixed check order, cheapest and most specific first, returning on the first
violation. Several schemes may match one value; the value is valid if **any** of them accepts it.
See [usage.md](usage.md#the-twelve-violation-codes) for the full ordering.

**`Parser`** — validation plus attribution. It reuses `Validator::analyze()` rather than
re-detecting, so parsing and validating can never disagree about which scheme won.

**`Formatter`** — Compact, Print (a mask where `#` consumes one character) and Anonymized.

**`Core/Checksum/`** — `Luhn`, `Mod97`, `Iso7064`, `Weights` and `Verhoeff`, the five primitives
the 85 national validators are built from. A bug in one of them is a bug in dozens of countries at
once, which is why they are tested directly against published reference values as well as through
their callers.

## Registry as data

`src/Registry/data/countries.php` is a plain `return [...]` array — OPcache keeps it compiled, and
adding a country is a data edit, not a code change. Each country has a name, an optional VAT prefix
and an **ordered** list of schemes; each scheme has a pattern, its legal lengths, an optional print
mask, an optional validator class-string, and worked examples.

`Registry` hydrates that lazily and memoizes it. `PhpRegistryLoader` is swappable, so an application
can compile the same shape from a database or add a jurisdiction of its own.

## Resolution, and the three-state answer

Validation says *could this have been issued?*. Resolution asks a provider *was it?* — and only VIES
can actually answer, for EU VAT numbers.

`LookupResult::isRegistered()` therefore returns `true`, `false` **or `null`**, and the third state is
the important one: it means nobody could answer. No provider configured, country outside VIES, or the
service was unreachable. A provider that merely returns nothing yields `null`; only a provider
implementing `RegistrationCheckInterface` can produce a `false`. `DatabaseProvider` deliberately does
not implement it — a missing row is not evidence a taxpayer doesn't exist.

The resolver validates first and never sends an invalid value to a provider: a useless round-trip and
an avoidable disclosure of someone's data.

## The dependency rule, enforced

`tests/Architecture/CoreIsFrameworkFreeTest.php` greps the guarded directories — `Contracts`, `Core`,
`DTO`, `Enums`, `Exceptions`, `National`, `Registry`, `Resolver` — plus the individually-guarded
`src/TaxId.php`, `Providers/NullProvider.php`, `Providers/ChainProvider.php` and the registry data
file, for `CodeIgniter\` and `codeigniter4`. A hit fails the build.

`src/Providers/` is a **split directory**: `NullProvider` and `ChainProvider` are framework-free by
design, while `ViesProvider`, `DatabaseProvider` and `CachedProvider` are CI4 adapters. Guarding the
directory wholesale would be wrong, so those two files are named individually.

The test also carries two self-tests — one dirty snippet that must be flagged, one clean snippet that
must not — so an inverted or broken detector can't pass green.

## Data integrity, enforced

`tests/Registry/CountriesDataIntegrityTest.php` is the other half. For **every scheme of every
country** it asserts that:

- the pattern compiles and matches the scheme's own first example;
- every example's length is among the declared lengths;
- the print mask's placeholder count matches one of those lengths, so it is actually applied;
- the validator class exists, implements the contract, and `supports()` that exact country/scheme;
- a scheme *without* a validator carries a note explaining why;
- **every example validates green through the public facade**;
- the printed form round-trips back to the original through `normalize()`;
- and the checksum actually discriminates — every digit is bumped in turn and at least half the
  mutations must be rejected.

That last one is the one that matters most: a checksum that accepts everything looks like protection
and provides none.

## Status

v1.0: 94 jurisdictions, 136 schemes, 88 of them with a verified check digit. 1,284 tests, PHPStan
level 8 clean over `src` and `tests`, PSR-12 clean, no network access anywhere in the suite.

v1.1: 94 jurisdictions, 145 schemes, 95 of them with a verified check digit. 1,371 tests, PHPStan
level 8 clean over `src` and `tests`, PSR-12 clean, no network access anywhere in the suite.

v1.2: 94 jurisdictions, 146 schemes, 96 of them with a verified check digit. 1,383 tests, PHPStan
level 8 clean over `src` and `tests`, PSR-12 clean, no network access anywhere in the suite.

## References

- [The frozen design spec](superpowers/specs/2026-07-27-daycry-taxid-v1-design.md)
- [adding-a-country.md](adding-a-country.md) — the practical version of "registry as data"
- [algorithms.md](algorithms.md) — what each check digit is, and where the gaps are
