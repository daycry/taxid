# Daycry TaxId - Documentation

Validation, parsing, formatting and optional taxpayer resolution for **CodeIgniter 4** and standalone PHP **8.3+**.

[Package README](../README.md) | [Contributing](../CONTRIBUTING.md) | [GitHub](https://github.com/daycry/taxid)

---

## Documentation Index

### [Usage Guide](usage.md)

- Framework-free facade and CodeIgniter service.
- Validation assurance: `checksumVerified`, accepting scheme and detailed violations.
- Explicit country/type selection, parsing and formatting.
- Helper functions, CLI commands, providers and configuration.

### [API Reference](api-reference.md)

- Public facade methods, DTOs and enums.
- Registry profiles, schemes and custom loaders.
- Checksum, reserved-value and optional variant-aware contracts.
- Resolver, lookup providers and CodeIgniter integration.

### [Country Coverage](countries.md)

- **94 jurisdictions**, **149 schemes** and **101 schemes with checksum support**.
- Entity types, VAT capability, compact lengths and examples.
- Personal and business identifiers, including CH AHV/AVS, MD IDNO/IDNP and MK EMBG/EDB.

### [Algorithm Sources & Limits](algorithms.md)

- National checksum formulas and shared arithmetic primitives.
- Exceptional formats and structural-only schemes.
- Source provenance, reference implementations and corroborating evidence.
- Calendar/century rules and the limits of offline validation.

### [Formatting](formatting.md)

- Compact, print and anonymized output.
- Country-specific masks and round-trip behavior.
- Swiss AHV/AVS, UID and other worked examples.

### [Adding a Country](adding-a-country.md)

- Registry data, scheme order and synthetic examples.
- National validators and reserved-value checks.
- `checksum` capability flags and per-value checksum exceptions.
- Required tests, coverage-table updates and quality checks.

### [Architecture](architecture.md)

- Core, resolver and CodeIgniter integration boundaries.
- Normalization, detection, validation, parsing and formatting pipeline.
- Registry hydration, provider composition and registration outcomes.
- Architecture and data-integrity tests.

### [Translation](i18n.md)

- Stable violation codes and `messageKey` values.
- Application-level translations with CodeIgniter language files.
- Mapping technical validation failures to form messages.

### [Data & Licensing](licensing.md)

- Independently authored registry data and algorithm provenance.
- Synthetic examples and empty database migrations.
- Optional VIES requests and handling identifiers in applications.

### [Roadmap](roadmap.md)

- Current development changes and shipped versions.
- Remaining coverage work and extension points.
- Versioning policy and scope decisions.

---

## Feature Matrix

| Feature | Availability |
|---------|--------------|
| Offline format and scheme validation | All registered schemes |
| Arithmetic checksum support | 101 of 149 schemes; some variants have exceptions |
| Per-value validation assurance | `ValidationResult::checksumVerified` and `scheme` |
| Compact, print and anonymized formats | Framework-free core |
| Country detection | Recognized prefixes; unprefixed values require a country |
| Taxpayer registration checks | Optional provider; `true`, `false` or `null` |
| VIES | EU VAT numbers for the EU-27 and Northern Ireland |
| Service, helper, CLI and database adapters | CodeIgniter 4 integration |
| Custom countries, validators and providers | Public extension contracts |

---

## Quick Links

- **Installation:** [Quick Start](../README.md#quick-start).
- **First validation:** [Facade](usage.md#the-facade).
- **Check what passed:** [Validation Assurance](usage.md#knowing-what-was-checked).
- **Inspect a country:** [Coverage Matrix](countries.md).
- **Report an issue:** [daycry/taxid issues](https://github.com/daycry/taxid/issues).
- **Package:** [daycry/taxid on Packagist](https://packagist.org/packages/daycry/taxid).
