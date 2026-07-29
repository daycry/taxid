# Daycry TaxId

[![Tests](https://github.com/daycry/taxid/actions/workflows/phpunit.yml/badge.svg)](https://github.com/daycry/taxid/actions/workflows/phpunit.yml)
[![PHPStan](https://github.com/daycry/taxid/actions/workflows/phpstan.yml/badge.svg)](https://github.com/daycry/taxid/actions/workflows/phpstan.yml)
[![Code Style](https://github.com/daycry/taxid/actions/workflows/code-style.yml/badge.svg)](https://github.com/daycry/taxid/actions/workflows/code-style.yml)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

Validate, parse and format tax identification numbers for **94 jurisdictions** — NIF, CIF, VAT,
CPF, CNPJ, RFC, EIN, GSTIN and 137 other schemes — with real check-digit verification, not just a
regex.

Built for CodeIgniter 4, and equally usable standalone with zero dependencies.

```bash
composer require daycry/taxid
```

```php
$taxid = new \Daycry\TaxId\TaxId();

$taxid->isValid('12345678Z', 'ES');        // true  — NIF, mod-23 letter checked
$taxid->isValid('ESB12345674');            // true  — country inferred from the VAT prefix
$taxid->isValid('111.444.777-35', 'BR');   // true  — separators are irrelevant
$taxid->isValid('12345678A', 'ES');        // false — wrong check letter

$taxid->parse('11.222.333/0001-81', 'BR')->scheme;   // 'cnpj'
```

## Why not just a regex?

Because there is no universal tax-ID algorithm. Every jurisdiction defines its own length, character
set, structure and — where one exists at all — its own check digit. A single `isValidTaxId()` regex
accepts a mistyped number as readily as a real one.

This package instead ships a **registry of per-country rules** plus **one validator per algorithm**,
and a data-integrity test that runs every bundled example through the public API so a country cannot
land broken.

What "valid" means here is *could this have been issued?* — structure plus check digit. It never
means *was it issued?*. For that, EU VAT numbers can be checked against VIES; see
[Resolving a taxpayer](#resolving-a-taxpayer-optional).

## Quickstart

### Facade (framework-free, works standalone)

```php
use Daycry\TaxId\TaxId;
use Daycry\TaxId\Enums\TaxIdFormat;

$taxid = new TaxId();

// Validate. Never throws, whatever you feed it.
$result = $taxid->validate('12345678A', 'ES');
$result->isValid();     // false
$result->errorCode();   // 'check_digit_failed'
$result->error();       // 'The tax ID check digit is invalid.'

// Parse: which country, which scheme, who it identifies.
$parsed = $taxid->parse('111.444.777-35', 'BR');
$parsed->scheme;                            // 'cpf'
$parsed->schemeName;                        // 'CPF — Cadastro de Pessoas Físicas'
$parsed->entityType;                        // EntityType::Natural
$parsed->compact;                           // '11144477735'
$parsed->format();                          // '111.444.777-35'
$parsed->format(TaxIdFormat::Anonymized);   // '*********35'

// VAT numbers, quotable internationally.
$taxid->parse('B12345674', 'ES')->vatNumber();   // 'ESB12345674'
$taxid->parse('094259216', 'GR')->vatNumber();   // 'EL094259216' — Greece quotes EL, not GR

// Ask for one specific scheme.
$taxid->isValid('12345678Z', 'ES', 'cif');   // false — it is a valid NIF, not a CIF
```

### CI4 service

```php
$taxid = service('taxid');   // auto-discovered, no wiring needed

$taxid->isValid($request->getPost('vat_number'));
```

### CI4 helper

```php
helper('taxid');

taxid_is_valid('12345678Z', 'ES');         // true
taxid_format('11144477735', null, 'BR');   // '111.444.777-35'
vat_number('B12345674', 'ES');             // 'ESB12345674'

// Set Config\TaxId::$defaultCountry = 'ES' and stop repeating yourself:
taxid_is_valid('12345678Z');               // true
```

### spark commands

```bash
php spark taxid:validate 12345678Z --country ES     # exit 0 valid, 1 invalid
php spark taxid:validate ESB12345674 --json
php spark taxid:parse 11.222.333/0001-81 --country BR
php spark taxid:lookup ESB12345674                  # exit 2 = valid but NOT registered
php spark taxid:countries                           # the whole registry
php spark taxid:countries ES                        # one country's schemes
php spark taxid:publish                             # publish the config into your app
```

## Coverage

**94 countries, 145 schemes.** 95 of them verify a real check digit; the rest validate
structure only, because the jurisdiction publishes no verifier algorithm — and each of those says so
in its registry `notes`.

| Region | Highlights |
| --- | --- |
| EU-27 | Every member state's VAT number, check digit verified. Plus national IDs where they are the tax identifier: ES NIF/NIE/CIF, PT NIF, IT codice fiscale & partita IVA, NL BSN, PL NIP/PESEL/REGON, RO CNP, BG EGN, HR OIB, EE isikukood, LV personas kods, BE rijksregisternummer, SE personnummer, CZ/SK IČO. |
| Rest of Europe | GB (VAT, UTR, NINO), XI Northern Ireland, CH UID/MWST, NO organisasjonsnummer/MVA, IS kennitala, RS PIB, RU INN, TR VKN/TCKN, MC, AL, BA, BY, GE, MD, ME, MK, UA, AM, AZ, KZ, LI. |
| Americas | BR CPF/CNPJ, MX RFC, AR CUIT, CL RUT, CO NIT, PE RUC, UY RUT, VE RIF, EC RUC/cédula, DO RNC, PY RUC, US EIN/SSN/ITIN, CA BN/SIN, plus BO, CR, GT, HN, NI, PA, SV. |
| Asia-Pacific | IN GSTIN/PAN, CN USCC & resident ID, JP corporate number, KR BRN, AU ABN/ACN/TFN, NZ IRD, plus HK, ID, MY, PH, PK, SG, TH, TW, VN. |
| Middle East & Africa | IL, SA, AE, EG, ZA, KE, MA, NG, DZ, TN. |

`php spark taxid:countries` prints the current list; [docs/countries.md](docs/countries.md) has the
full matrix with lengths, entity types and which schemes are checksummed.

## Features

- ✅ **Real check digits** — mod-11, mod-97, Luhn, Verhoeff, ISO/IEC 7064 (MOD 11,10 / 11-2 /
  37,36), and 84 national variants, each in its own small, tested class.
- ✅ **Country inference** from a VAT prefix (`ESB12345674`, `DE136695976`, `EL094259216`) — and
  never from bare digits, because that would be a confident guess with no basis.
- ✅ **Precise violations** — twelve `ViolationCode` cases tell "wrong length" from "wrong shape"
  from "wrong scheme" from "bad check digit" from "never-issued range".
- ✅ **Formatting** — Compact, Print (each country's conventional mask) and Anonymized (`*********35`)
  so a tax ID can be logged without leaking it.
- ✅ **Multi-scheme countries** handled honestly: a value valid under *any* of a country's schemes is
  valid, and you can demand one specific reading.
- ✅ **Optional VIES lookup** for EU VAT numbers, with a three-state answer that never confuses
  "the service was down" with "not registered".
- ✅ **Framework-free core** — enforced by a test, not a promise.
- ✅ PHPStan level 8, PSR-12, 1,370 tests.

## Resolving a taxpayer (optional)

Validation is offline and always available. *Resolution* asks a provider whether the taxpayer really
exists — and is entirely opt-in.

```php
// app/Config/TaxId.php (php spark taxid:publish)
public string $provider = 'chain';   // local table first, then VIES
public ?int $cacheTtl   = 86400;     // VIES is slow and rate-limited: cache it
```

```php
$result = service('taxid')->resolve('ESB12345674');

$result->isValid();        // true  — offline validity
$result->isRegistered();   // true | false | null
$result->name();           // 'ACME SL', or null if undisclosed
```

**`isRegistered()` returns three states, and the third one matters.** `null` means *nobody could
answer*: no provider configured, the country is outside VIES, or the service was unreachable.
Treating it as `false` would block real customers whenever someone else's server has a bad day.

Providers: `NullProvider` (default, inert), `ViesProvider` (EU VAT), `DatabaseProvider` (your own
`tax_entities` table), `CachedProvider`, `ChainProvider`. See [docs/usage.md](docs/usage.md).

## Database setup (optional)

Only needed for `DatabaseProvider` or `ChainProvider`.

```bash
php spark migrate --all
```

That creates an **empty** `tax_entities` table. This package ships no taxpayer data — what goes in it
is yours to decide: cached VIES answers, a customer master, or a register you are licensed to hold.

## Standalone usage (outside CodeIgniter 4)

The core has no framework dependency at all:

```php
require 'vendor/autoload.php';

$taxid = new \Daycry\TaxId\TaxId();
$taxid->isValid('11144477735', 'BR');   // true
```

Only `Config\`, `Commands\`, `Models\`, `Database\`, `Helpers\` and three of the five providers touch
CodeIgniter, and `tests/Architecture/CoreIsFrameworkFreeTest.php` fails the build if that ever stops
being true.

## Compatibility

| | |
| --- | --- |
| PHP | 8.3, 8.4 |
| CodeIgniter | 4.6+ (optional — dev dependency only) |
| Extensions | `ext-simplexml` (VIES response parsing) |

## Documentation

| Document | What's in it |
| --- | --- |
| [docs/usage.md](docs/usage.md) | Task-oriented guide: facade, helper, commands, providers, config |
| [docs/api-reference.md](docs/api-reference.md) | Complete per-symbol reference |
| [docs/countries.md](docs/countries.md) | Coverage matrix: every country and scheme |
| [docs/adding-a-country.md](docs/adding-a-country.md) | How to extend the registry |
| [docs/algorithms.md](docs/algorithms.md) | Per-algorithm provenance, and why some have no checksum |
| [docs/architecture.md](docs/architecture.md) | The two layers and the rule between them |
| [docs/formatting.md](docs/formatting.md) | Format reference and the print-mask language |
| [docs/i18n.md](docs/i18n.md) | Translating violation messages |
| [docs/licensing.md](docs/licensing.md) | Data authoring and privacy |
| [docs/roadmap.md](docs/roadmap.md) | What v1.0 and v1.1 cover, and what it deliberately doesn't |

## Development

```bash
composer update    # not `install` — this library ships no composer.lock
composer test      # PHPUnit
composer analyze   # PHPStan level 8
composer cs        # PHP-CS-Fixer (dry run); `vendor/bin/php-cs-fixer fix` to apply
```

No test touches the network.

## License

MIT. See [LICENSE](LICENSE).
