# Changelog

All notable changes to `daycry/taxid` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project
adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

Note on versioning, spelled out in [docs/roadmap.md](docs/roadmap.md): **adding a country or scheme
is a MINOR release** (it changes what `validate()` returns for values that were previously
`UnknownCountry`), and **correcting a wrong algorithm is a PATCH** even though behaviour changes —
the old behaviour was a bug. Such corrections are always called out here by name.

## [Unreleased]

## [1.2.0] - 2026-07-29

Slovakia's natural-person identifier, and a correction to a claim v1.1's own design spec made about
it.

### Added

- **`SK/rodne_cislo`** — the Slovak rodné číslo, nine or ten digits, taking the registry to 146
  schemes, 96 of them with a verified check digit, and the validator count to 85. Czechia and
  Slovakia both kept the Czechoslovak birth number after 1993, but only Czechia had a scheme for it:
  the same personal identifier validated as `CZ` and was refused as `SK` with `check_digit_failed`.
  That code was doubly misleading — the checksum was fine, and the value failed only because the
  ten-digit Slovak scheme is a VAT number whose third digit must encode a taxpayer category a birth
  number cannot carry.
- The arithmetic is **shared with `CZ/dic` rather than duplicated**: `CzDicValidator`'s two
  birth-number checks join `isValidIco()` as public static methods, which is how the eight-digit IČO
  was already shared between the two countries.

### Changed

- **`SK/vat` is `entity: both`, not `legal`.** The IČ DPH is the DIČ with an `SK` prefix, and the DIČ
  is issued to sole traders as well as companies — so `ParsedTaxId::$entityType` now reports `both`
  for a Slovak VAT number. The old value was a factual error in the data, not a design choice.
- `SK/vat` and `SK/rodne_cislo` overlap at ten digits. `vat` is declared first, so a value that is a
  valid DIČ still reads as one; a birth number falls through to `rodne_cislo`. This is the same
  documented multi-scheme behaviour Germany and the United States already rely on.

### Notes

- v1.1's design spec claimed Slovakia was **not** a coverage gap, on the grounds that its personal
  and corporate identifiers share one format. They do not, and the claim is withdrawn here. The
  spec's own caveat — that a `check_digit_failed` verdict proves the structure is accepted, not that
  the checksum rule is the personal number's — is what turned out to matter.

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
  to **ten** characters, while one written with a letter marker keeps **eleven**. Both are correct
  and both validate; the marker never enters the checksum.

### Notes

- The nine algorithms are independently authored from published national rules and verified against
  reference implementations, not against issued identifiers. Hungary's is the one with no primary
  source available; see `docs/algorithms.md` for the per-algorithm provenance.
- Bundled examples remain synthetic and checksum-valid.

## [1.0.0] - 2026-07-27

First release. Validation, parsing, formatting and opt-in taxpayer resolution for tax identification
numbers, built for CodeIgniter 4 and equally usable standalone.

### Added

- **Registry of 94 jurisdictions and 136 schemes**, 88 of them with a verified check digit
  (`src/Registry/data/countries.php`, matrix in [docs/countries.md](docs/countries.md)). Rules are
  independently authored from publicly documented national formats; bundled examples are synthetic
  and checksum-valid rather than real taxpayers' identifiers
  ([docs/licensing.md](docs/licensing.md)).
- **Framework-free core** — `Normalizer`, `SchemeDetector`, `Validator`, `Parser`, `Formatter`, the
  registry and 77 national validators, with zero dependencies. Enforced by
  `tests/Architecture/CoreIsFrameworkFreeTest.php`, which also guards `src/TaxId.php`,
  `NullProvider`, `ChainProvider` and the registry data individually and carries two self-tests
  proving the detector isn't a stub.
- **Public facade `Daycry\TaxId\TaxId`** implementing `ValidatorInterface`, `ParserInterface` and
  `ResolverInterface`: `validate()`, `isValid()`, `normalize()`, `parse()`, `tryParse()`,
  `format()`, `resolve()`, plus registry introspection (`supports()`, `countries()`, `country()`,
  `schemes()`, `scheme()`) and sub-service accessors.
- **Optional country argument.** Omitted, the country is inferred from a leading VAT/country prefix
  (`ESB12345674`, `EL094259216`, `XI123456782`) — and from nothing else. A prefix is only stripped
  when stripping it makes the value match, so `ATU12345678` resolves correctly and an Italian codice
  fiscale beginning `IT` is left intact.
- **Twelve `ViolationCode` cases** distinguishing a wrong length from a wrong shape from a wrong
  scheme from a bad check digit from a never-issued range, each with a stable `messageKey`
  ([docs/i18n.md](docs/i18n.md)).
- **Multi-scheme countries handled honestly.** Germany's IdNr and Steuernummer overlap at eleven
  digits; a US EIN and SSN are both nine. A value is valid if **any** of a country's schemes accepts
  it, tried in registry order, and a specific reading can be demanded with the `$scheme` argument.
- **`ReservedValue`** for values that are arithmetically fine but never issued — a Brazilian CPF of
  repeated digits, a US SSN starting `000`, an ITIN with an unallocated middle group — reported
  separately from a check-digit failure via the optional `ReservedValueDetectorInterface`.
- **Three output formats** (`TaxIdFormat`): `Compact`, `Print` (a per-country mask where `#` consumes
  one character) and `Anonymized` (`*********35`), so a tax ID can be logged without the log becoming
  a list of identifiers ([docs/formatting.md](docs/formatting.md)).
- **Shared checksum primitives** — `Luhn`, `Mod97`, `Iso7064` (MOD 11,10 / 11-2 / 37,36) and
  `Weights`, tested directly against published reference values as well as through their callers
  ([docs/algorithms.md](docs/algorithms.md)).
- **Opt-in resolution** through `LookupProviderInterface`: `NullProvider` (default, inert),
  `ViesProvider` (EU VAT existence check), `DatabaseProvider` (the local `tax_entities` table),
  `CachedProvider` and `ChainProvider`. `Resolver` validates first and never sends an invalid value
  to a provider.
- **Three-state registration answer.** `LookupResult::isRegistered()` returns `true`, `false` or
  `null` — the last meaning *nobody could answer*. Only a provider implementing
  `RegistrationCheckInterface` can produce a `false`; `DatabaseProvider` deliberately does not, since
  a missing row is not evidence a taxpayer doesn't exist.
- **CodeIgniter 4 integration**: `Config\TaxId` (publishable), `Config\Services::taxid()`
  (auto-discovered), `Config\Registrar`, a twelve-function `taxid_helper`, and five spark commands —
  `taxid:validate`, `taxid:parse`, `taxid:lookup` (exit 2 = valid but not registered),
  `taxid:countries`, `taxid:publish`.
- **`tax_entities` table** with a migration and `TaxEntityModel`. Created **empty**; the seeder
  inserts nothing, deliberately.
- **Documentation set**: usage, complete API reference, generated country matrix, a guide to adding a
  country, per-algorithm provenance, architecture, formatting, i18n, licensing and roadmap, plus the
  frozen design spec under `docs/superpowers/specs/`.
- **1,284 tests / 3,322 assertions.** The backbone is
  `tests/Registry/CountriesDataIntegrityTest.php`, which for every scheme of every country checks the
  pattern, lengths, mask, validator wiring and round-tripping, runs **every bundled example through
  the public facade**, and bumps each digit in turn to prove the checksum actually discriminates.
  No test touches the network.
- PHPStan level 8 over `src` and `tests` with no baseline; PSR-12 via PHP-CS-Fixer; CI across PHP 8.3
  and 8.4 plus a CodeQL scan of the workflows.

### Known limitations

Documented rather than hidden — see [docs/algorithms.md](docs/algorithms.md) for the full list and
the reasoning:

- 48 schemes validate **structure only**, because the issuing authority publishes no verifier
  algorithm (GB UTR and NINO, IN PAN, DE Steuernummer, DK CPR, ZA, UA, SG, TW, and most of the long
  tail). Each says so in its registry `notes`.
- A French TVA number with an **alphabetic** key can only have its SIREN verified; the DGFiP does not
  publish how those keys are derived.
- US identifiers have **no check digit at all**; only the documented never-issued ranges are
  enforced.
- The country is never guessed from an unprefixed value. A nine-digit number is a plausible tax ID in
  dozens of jurisdictions, so `CountryRequired` is the honest answer.

[Unreleased]: https://github.com/daycry/taxid/compare/v1.1.0...HEAD
[1.1.0]: https://github.com/daycry/taxid/compare/v1.0.0...v1.1.0
[1.0.0]: https://github.com/daycry/taxid/releases/tag/v1.0.0
