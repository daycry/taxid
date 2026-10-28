# Contributing to Daycry TaxId

Contributions are welcome: country rules, algorithm corrections, documentation, reproducible bug reports and integrations.

[Package README](README.md) | [Documentation](docs/README.md)

---

## Development Setup

- PHP **8.3+** and the extensions required by Composer.
- Composer.
- A local checkout of this repository.

```bash
composer update
```

This library intentionally ignores `composer.lock`. CI resolves the declared dependency constraints with `composer update`.

---

## Making a Change

1. Describe the expected behavior and identify the affected country/scheme or API.
2. For behavior changes, write a focused regression test and reproduce the failure first.
3. Implement the change within the existing dependency boundaries.
4. Update the relevant guides, coverage table and `CHANGELOG.md`.
5. Run the checks below and describe the result in the pull request.

Documentation-only changes do not require a new behavior test. Check links, examples and any existing documentation-integrity tests affected by the edit.

## Country Rules & Examples

- Follow [Adding a Country](docs/adding-a-country.md).
- Cite the national specification or the actual algorithm reference, with any verification limits.
- Generate synthetic examples; do not add taxpayer records from research to tests.
- Cover valid values, incorrect controls, exceptional formats and relevant boundaries.
- Preserve the distinction between a verified checksum, accepted structure/ranges and registration.

## Required Checks

```bash
composer test
composer analyze
composer cs
```

- **PHPUnit:** All bundled examples, national rules, integration behavior and architecture checks.
- **PHPStan:** Level 8 over source and tests.
- **PHP-CS-Fixer:** PSR-12 and the repository's configured rules.
- **No Network in Tests:** Use fixtures or test doubles for external services.

## Pull Requests & Bug Reports

- Submit pull requests to [daycry/taxid](https://github.com/daycry/taxid/pulls).
- Report bugs through [GitHub Issues](https://github.com/daycry/taxid/issues).
- Include the country, scheme, expected result and reproduction steps.
- Use a synthetic identifier or a redacted example when reporting a validation issue.
- Keep changes focused and explain any intentional validation behavior changes.

## License

Contributions are provided under the repository's [MIT License](LICENSE).
