# Roadmap

## v1.0 — shipped

The whole of it, in one list:

- **94 jurisdictions, 136 schemes**, 88 of them with a verified check digit. The full matrix is in
  [countries.md](countries.md).
- **Framework-free core** — validation, parsing and formatting with zero dependencies, enforced by
  `tests/Architecture/CoreIsFrameworkFreeTest.php` rather than by good intentions.
- **Twelve violation codes** that distinguish a wrong length from a wrong shape from a wrong scheme
  from a bad check digit from a never-issued range.
- **Country inference** from a VAT prefix, and a deliberate refusal to guess from bare digits.
- **Multi-scheme countries** handled properly: valid under any of a country's schemes is valid, and
  a specific reading can be demanded.
- **Three output formats**, including an anonymized one for logs.
- **Opt-in resolution** through `NullProvider`, `ViesProvider`, `DatabaseProvider`, `CachedProvider`
  and `ChainProvider`, with a three-state registration answer.
- **CI4 integration**: `service('taxid')`, a twelve-function helper, five spark commands, a
  publishable config, a migration and a model.
- 1,284 tests, PHPStan level 8, PSR-12, no network access in the suite.

## v1.1 — shipped

Natural-person identifiers for eight countries that v1.0 covered only through their corporate
number.

- **Nine schemes**, taking the registry to **145 schemes**, 95 of them with a verified check digit,
  across the same 94 jurisdictions: `AT/steuernummer`, `FI/henkilotunnus`, `FR/spi`,
  `HU/adoazonosito`, `LT/asmens_kodas`, `LU/matricule`, `MT/id_card`, `MT/tin`, `NO/fodselsnummer`.
- **Seven new national validators**, taking the validator count to 84, plus a fifth checksum
  primitive, `Verhoeff`. Luxembourg pairs it with a Luhn digit over the same eleven-digit base,
  because Luhn is blind to an adjacent transposition whose digits differ by five.
- **`+` is a separator during normalization**, in every country — Finland's century marker for the
  1800s is a literal plus, and it could never reach a pattern before.
- 1,371 tests, PHPStan level 8, PSR-12, still no network access in the suite.

## v1.2 — shipped

One scheme, correcting an inconsistency inside the registry rather than adding a country.

- **`SK/rodne_cislo`**, taking the registry to **146 schemes**, 96 of them with a verified check
  digit, and the validator count to 85. Czechia and Slovakia both kept the Czechoslovak rodné
  číslo after 1993, but only Czechia had a scheme for it: the same personal identifier validated
  as `CZ` and was refused as `SK`.
- **`SK/vat` is `entity: both`, not `legal`.** The IČ DPH is the DIČ with an SK prefix, and the
  DIČ is issued to sole traders as well as companies.
- 1,382 tests, PHPStan level 8, PSR-12, still no network access in the suite.

## Deliberately out of scope

These are not "not yet". They are decisions.

**Guessing the country from an unprefixed value.** A nine-digit number is a plausible tax ID in
dozens of jurisdictions. Any guess would be confidently wrong a large fraction of the time, and a
confident wrong answer is worse than `CountryRequired`.

**Non-VAT registry lookups.** Outside EU VAT there is essentially no free, public, per-country
service that answers "does this taxpayer exist?". Where a national register exists, it is usually
licensed, rate-limited, or both — which makes it an application's integration, not a library's. The
`LookupProviderInterface` is the seam for exactly that.

**Inventing check digits.** Where an authority publishes no verifier, or publishes one whose details
are reported inconsistently, this package validates structure only and says so in the scheme's
`notes` and in [algorithms.md](algorithms.md). Rejecting a valid tax ID costs a real customer;
accepting an invalid one costs a row that fails later anyway.

**Shipping taxpayer data.** The `tax_entities` table is created empty and the seeder inserts nothing.
See [licensing.md](licensing.md).

**Translating the core's messages.** English plus a stable `messageKey`, so the application's own
translation layer does the work. See [i18n.md](i18n.md).

**A `TaxIdType` enum.** Scheme codes are registry strings on purpose: as an enum, adding a country
would be a BC-relevant change to a public type. `Registry::scheme()` is the typed lookup.

## Possible later

Ideas, not commitments. Each would need a real use case behind it before it earns its complexity.

- **More jurisdictions.** The long tail is mostly structural-only entries; each is a small data edit
  ([adding-a-country.md](adding-a-country.md)). Contributions welcome, especially from someone who
  can verify their own country's algorithm.
- **Check digits for the currently structural-only schemes**, where an authority publishes the
  algorithm or a contributor can cite it confidently. `ZA`, `UA`, `GT`, `SG` and `TW` are the
  nearest misses.
- **A CI4 validation rule** (`taxid[ES]`) so `$this->validate()` can use it directly. Small, useful,
  and purely in the adapter layer.
- **Batch resolution** — an interface for providers that can answer many IDs in one call, which some
  commercial services offer and VIES does not.
- **A VIES REST transport** alongside the SOAP one, if and when the Commission's REST interface
  stabilizes. `Config\TaxId::$viesEndpoint` already makes the endpoint swappable.

## Versioning

Semantic versioning. Specifically:

- **Adding a country or a scheme is a MINOR release.** It can change what `validate()` returns for
  values that were previously `UnknownCountry`, so it is not a patch.
- **Correcting a wrong algorithm is a PATCH**, even though it changes behaviour — the previous
  behaviour was a bug, and leaving it in place to protect a version number would be the wrong trade.
  Such changes are always called out in `CHANGELOG.md`.
- **Violation codes, scheme codes and the public API are frozen within a major version.** New
  violation codes may be added; existing ones are never renamed or repurposed.
