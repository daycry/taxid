[![Donate](https://img.shields.io/badge/Donate-PayPal-green.svg)](https://www.paypal.com/donate?business=SYC5XDT23UZ5G&no_recurring=0&item_name=Thank+you%21&currency_code=EUR)

# Daycry TaxId

<!-- Tests & coverage -->
[![Tests](https://github.com/daycry/taxid/actions/workflows/phpunit.yml/badge.svg)](https://github.com/daycry/taxid/actions/workflows/phpunit.yml)
[![Coverage Status](https://coveralls.io/repos/github/daycry/taxid/badge.svg)](https://coveralls.io/github/daycry/taxid)

<!-- Static analysis & quality gates -->
[![PHPStan](https://github.com/daycry/taxid/actions/workflows/phpstan.yml/badge.svg)](https://github.com/daycry/taxid/actions/workflows/phpstan.yml)
[![Code Style](https://github.com/daycry/taxid/actions/workflows/code-style.yml/badge.svg)](https://github.com/daycry/taxid/actions/workflows/code-style.yml)
[![CodeQL](https://github.com/daycry/taxid/actions/workflows/codeql.yml/badge.svg)](https://github.com/daycry/taxid/actions/workflows/codeql.yml)

<!-- Package -->
[![Latest Stable Version](https://poser.pugx.org/daycry/taxid/v/stable)](https://packagist.org/packages/daycry/taxid)
[![Latest Unstable Version](https://poser.pugx.org/daycry/taxid/v/unstable)](https://packagist.org/packages/daycry/taxid)
[![Total Downloads](https://poser.pugx.org/daycry/taxid/downloads)](https://packagist.org/packages/daycry/taxid)
[![Monthly Downloads](https://poser.pugx.org/daycry/taxid/d/monthly)](https://packagist.org/packages/daycry/taxid)
[![License](https://poser.pugx.org/daycry/taxid/license)](LICENSE)

<!-- Tooling -->
[![PHP Version Require](https://poser.pugx.org/daycry/taxid/require/php)](https://packagist.org/packages/daycry/taxid)
[![PHPStan level 8](https://img.shields.io/badge/PHPStan-level%208-2563eb.svg)](phpstan.neon)

<!-- Community -->
[![GitHub stars](https://img.shields.io/github/stars/daycry/taxid)](https://github.com/daycry/taxid/stargazers)
[![GitHub forks](https://img.shields.io/github/forks/daycry/taxid)](https://github.com/daycry/taxid/network/members)
[![GitHub issues](https://img.shields.io/github/issues/daycry/taxid)](https://github.com/daycry/taxid/issues)

A tax identification number library for **CodeIgniter 4**, with a framework-free core for standalone PHP applications.

Validate, parse and format identifiers across **94 jurisdictions and 149 schemes**, including NIF, CIF, VAT, CPF, CNPJ, RFC, AHV/AVS, IDNO, IDNP, EMBG and EDB. **101 schemes support arithmetic checksums**; each validation result reports what was actually checked.

```bash
composer require daycry/taxid
```

---

## Features

### Validation

| Feature | Description |
|---------|-------------|
| **Country Registry** | 94 jurisdictions and 149 identifier schemes, with entity types, formats and examples |
| **Checksum Verification** | 101 schemes with national algorithms, including mod-11, mod-97, Luhn, Verhoeff, EAN-13 and ISO 7064 |
| **Validation Assurance** | `checksumVerified` distinguishes a checked checksum from accepted structure/ranges |
| **Explicit Scheme Selection** | Require a specific type such as `nif`, `ahv`, `idnp`, `embg` or `edb` |
| **Country Inference** | Detect recognized country/VAT prefixes, including Greece's `EL` and Northern Ireland's `XI` |
| **Detailed Errors** | Twelve violation codes for input, country, structure, scheme, reserved values and checksum failures |
| **National Rules** | Personal/company variants, reserved ranges and supported calendar/century checks |

### Parsing & Formatting

| Feature | Description |
|---------|-------------|
| **Normalization** | Uppercase and remove supported separators before checking a value |
| **Structured Results** | Country, accepting scheme, entity type, compact value and available check characters |
| **Print Formatting** | Country-specific masks, including Swiss AHV/AVS `756.1234.5678.97` |
| **Anonymized Formatting** | Mask all but the final two characters for display or logging |
| **VAT Representation** | Return the international VAT form when the scheme and country define one |

### Integration & Resolution

| Feature | Description |
|---------|-------------|
| **CodeIgniter Service** | Auto-discovered `service('taxid')` and a publishable configuration |
| **Helper Functions** | Validation, parsing, formatting and taxpayer lookup helpers |
| **CLI Commands** | Validate, parse, resolve and inspect country coverage, with JSON output |
| **Standalone Core** | Validation, parsing and formatting without CodeIgniter or runtime Composer packages |
| **Optional VIES Lookup** | EU VAT registration checks for the EU-27 and Northern Ireland |
| **Provider Chain & Cache** | Combine local database records, VIES and custom providers with optional caching |
| **Extension Points** | Custom registry loaders, national validators and lookup providers |

**Validity and registration are separate outcomes.** A successful validation confirms that the value passes the implemented rules. It does not confirm that a taxpayer exists. Some schemes check structure/ranges only, and some checksum schemes have exceptional formats. See [Validation Assurance](docs/usage.md#knowing-what-was-checked) and [Algorithm Sources & Limits](docs/algorithms.md).

---

## Quick Start

### Requirements

- PHP **8.3** or higher; CI tests PHP **8.3**, **8.4** and **8.5**.
- Composer and the **SimpleXML** PHP extension, declared by the package for VIES response parsing.
- CodeIgniter **4.6** or higher when using the service, helpers, commands or framework providers.

### 1. Install via Composer

```bash
composer require daycry/taxid
```

### 2. Validate a Tax ID

```php
use Daycry\TaxId\TaxId;

$taxid = new TaxId();

$result = $taxid->validate('12345678Z', 'ES', 'nif');

$result->isValid();          // true
$result->checksumVerified;   // true
$result->scheme;             // 'nif'

$invalid = $taxid->validate('12345678A', 'ES', 'nif');
$invalid->errorCode();        // 'check_digit_failed'

$taxid->isValid('ESB12345674');                   // true: country inferred
$taxid->isValid('756.1234.5678.97', 'CH', 'ahv'); // true: Swiss personal identifier
$taxid->isValid('1000000000018', 'MD', 'idno');   // true: Moldovan organization ID
$taxid->isValid('2000000000015', 'MD', 'idnp');   // true: Moldovan personal ID
$taxid->isValid('0101990410012', 'MK', 'embg');   // true: Macedonian personal ID
$taxid->isValid('4000000000013', 'MK', 'edb');    // true: Macedonian tax registration
```

Always pass the third argument when a field requires one identifier type. Without it, any matching scheme for the country may accept the value.

### 3. Parse & Format

```php
use Daycry\TaxId\Enums\TaxIdFormat;

$parsed = $taxid->parse('111.444.777-35', 'BR');

$parsed->countryCode;                         // 'BR'
$parsed->scheme;                              // 'cpf'
$parsed->compact;                             // '11144477735'
$parsed->format();                            // '111.444.777-35'
$parsed->format(TaxIdFormat::Anonymized);       // '*********35'

$taxid->parse('B12345674', 'ES')->vatNumber(); // 'ESB12345674'
```

`parse()` throws `InvalidTaxIdException` for an invalid value. Use `tryParse()` for a nullable result. `validate()` returns the violations without throwing for invalid string input.

### 4. Use the CodeIgniter Service or Helper

```php
$taxid = service('taxid');
$taxid->isValid('756.1234.5678.97', 'CH', 'ahv'); // true

helper('taxid');

taxid_is_valid('12345678Z', 'ES', 'nif');       // true
taxid_format('11144477735', null, 'BR');        // '111.444.777-35'
vat_number('B12345674', 'ES');                 // 'ESB12345674'
```

Validation works immediately. To customize the CI4 configuration:

```bash
php spark taxid:publish
```

Set `Config\TaxId::$defaultCountry` when the helper and CLI should assume a country. The standalone facade continues to use its explicit country or a recognized input prefix.

### CLI Commands

```bash
php spark taxid:validate 12345678Z --country ES --scheme nif
php spark taxid:validate 756.1234.5678.97 --country CH --scheme ahv --json
php spark taxid:parse 11.222.333/0001-81 --country BR
php spark taxid:countries
php spark taxid:countries MK --json
```

`taxid:validate` exits **0** for a valid value and **1** for an invalid one. JSON output includes the accepting `scheme` and nullable `checksum_verified` field.

---

## Country Coverage

| Region | Examples |
|--------|----------|
| **European Union** | ES NIF/NIE/CIF, PT NIF, IT codice fiscale/partita IVA, FR SIREN/SIRET/TVA, DE VAT/IdNr, PL NIP/PESEL/REGON |
| **Other European Jurisdictions** | CH UID/IVA/AHV, NO organization/personal IDs, IS kennitala/VSK, MD IDNO/IDNP, MK EMBG/EDB, GB VAT/UTR/NINO, XI VAT |
| **Americas** | BR CPF/CNPJ, MX RFC, AR CUIT, CL RUT, CO NIT, PE RUC, US EIN/SSN/ITIN, CA BN/SIN |
| **Asia-Pacific** | IN GSTIN/PAN, CN USCC/resident ID, JP corporate number/My Number, KR BRN, AU ABN/ACN/TFN, NZ IRD, TW business number |
| **Middle East & Africa** | IL personal/company IDs and registered formats for SA, AE, EG, ZA, KE, MA, NG, DZ and TN |

The table lists coverage, not an identical validation level for every identifier. [Country Coverage](docs/countries.md) contains every scheme, its supported lengths and checksum capability. [Algorithm Sources & Limits](docs/algorithms.md) explains the arithmetic, exceptional formats and structural-only entries.

---

## Taxpayer Resolution (Optional)

Configure a provider when the application needs registration or taxpayer data:

```php
// app/Config/TaxId.php, after php spark taxid:publish
namespace Config;

use Daycry\TaxId\Config\TaxId as BaseTaxId;

class TaxId extends BaseTaxId
{
    public string $provider = 'vies';
    public ?int $cacheTtl = 86400;
}
```

```php
$result = service('taxid')->resolve('ESB12345674');

$result->isValid();        // offline validation result
$result->isRegistered();   // true | false | null
$result->name();           // registered name, when available
```

| Registration Result | Meaning |
|---------------------|---------|
| **`true`** | The configured provider found the taxpayer or confirmed registration |
| **`false`** | A registration-checking provider explicitly reported no registration |
| **`null`** | No answer: provider not configured, unsupported country, unavailable service or inconclusive lookup |

For a local database or the database-first `chain` provider, run the optional migration:

```bash
php spark migrate --all
```

The `tax_entities` table starts empty. No taxpayer dataset is bundled. See [Providers & Configuration](docs/usage.md#providers) before enabling external lookups.

---

## Standalone Usage

```php
require 'vendor/autoload.php';

$taxid = new \Daycry\TaxId\TaxId();
$taxid->isValid('756.1234.5678.97', 'CH', 'ahv'); // true
```

The core has no CodeIgniter dependency. The service, helper, commands, database integration, VIES client and CI4 cache adapter use CodeIgniter.

---

## Documentation

Start with the **[Documentation Index](docs/README.md)** for guides and feature summaries.

| Section | Description |
|---------|-------------|
| [Usage Guide](docs/usage.md) | Facade, validation assurance, helpers, CLI, providers and configuration |
| [API Reference](docs/api-reference.md) | Public methods, DTOs, contracts and extension points |
| [Country Coverage](docs/countries.md) | All 94 jurisdictions and 149 identifier schemes |
| [Algorithm Sources & Limits](docs/algorithms.md) | Checksums, exceptional variants and verification provenance |
| [Formatting](docs/formatting.md) | Compact, print and anonymized output, with mask examples |
| [Adding a Country](docs/adding-a-country.md) | Registry entries, validators and required checks |
| [Architecture](docs/architecture.md) | Dependency boundaries, validation pipeline and integrity checks |
| [Translation](docs/i18n.md) | Violation codes and application-level translation |
| [Data & Licensing](docs/licensing.md) | Registry authoring, example data and external lookups |
| [Roadmap](docs/roadmap.md) | Current development, release history and future work |

---

## Development

```bash
composer update
composer test
composer analyze
composer cs
```

- **Tests:** PHPUnit; test cases do not make network requests.
- **Static Analysis:** PHPStan level 8 over `src/` and `tests/`.
- **Code Style:** PSR-12 with PHP-CS-Fixer; `composer cs` checks without editing.
- **Architecture:** Tests enforce the framework-free core and registry/documentation consistency.
- **Dependencies:** `composer.lock` is intentionally ignored for this library; CI resolves `composer.json` with `composer update`.

---

## Contributing

Code, documentation, country rules and bug reports are welcome. See [CONTRIBUTING.md](CONTRIBUTING.md) for the workflow and [Adding a Country](docs/adding-a-country.md) for validation-specific requirements.

## License

This project is licensed under the MIT License. See [LICENSE](LICENSE) for details.

## Acknowledgements

<a href="https://github.com/daycry/taxid/graphs/contributors">
  <img src="https://contrib.rocks/image?repo=daycry/taxid" alt="Daycry TaxId contributors" />
</a>

Made with [contrib.rocks](https://contrib.rocks).
