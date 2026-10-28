# Tax ID validation expansion

Approved scope: strengthen existing jurisdictions using verified published algorithms,
correct US checksum reporting, and expose the successful scheme and checksum outcome
without changing existing validation method signatures or registration semantics.

- T-01: Add JP/mynumber, TW/ban (including the 2023 expansion), KZ/bin (BIN/IIN) checks.
  Status: completed. Synthetic fixtures, positive and negative branches; no network in tests.
  RED: ExpandedValidatorsTest::testPublishedChecksumRules (JP wrong control) failed: true instead of false · 2026-10-28.
  RED: ExpandedValidatorsTest::testPublishedChecksumRules (TW wrong control) failed: true instead of false · 2026-10-28.
  RED: ExpandedValidatorsTest::testPublishedChecksumRules (KZ wrong control) failed: true instead of false · 2026-10-28.
  GREEN: ExpandedValidatorsTest.php — 17 tests, 29 assertions.
- T-02: Correct checksum capability metadata and expose per-value checksum verification.
  Status: completed. Explicit scheme enforcement and custom validator compatibility preserved.
  RED: ValidationAssuranceTest::testUSValidatorsAreNotAdvertisedAsChecksums failed: true instead of false · 2026-10-28.
  RED: ValidationAssuranceTest::testReportsWhatWasActuallyVerified (Spain checksum) failed: assurance property absent · 2026-10-28.
  RED: CommandsTest::testValidateJsonReportsChecksumAssurance failed: checksum_verified absent · 2026-10-28.
  RED: CommandsTest::testValidateJsonReportsTheExplicitAcceptingScheme failed: idnr instead of explicit steuernummer · 2026-10-28.
  GREEN: Commands + assurance + registry integration — 1240 tests, 4484 assertions (before removal of redundant property-existence assertions).
- T-03: Update coverage, provenance, API documentation and run all required checks.
  Status: completed. TDD n/a: prose/documentation.

Final verification (2026-10-28, PHP 8.4.18):

- `composer test`: OK (1581 tests, 5381 assertions).
- `composer analyze`: PHPStan level 8, no errors.
- `composer cs`: 0 of 172 files require formatting changes.
- `git diff --check`: passed.
- Live registry: 94 jurisdictions, 146 schemes, 96 arithmetic checksum schemes across 57 jurisdictions.

Compatibility: existing method signatures, two-argument DTO construction, explicit scheme
selection, legacy custom validators, US range rejection and nullable registration outcomes
remain supported. New checks intentionally reject incorrect JP/TW/KZ values previously
accepted on structure alone. Primary source links and exceptional formats are documented
in `docs/algorithms.md`; API metadata is documented in `docs/api-reference.md` and `docs/usage.md`.

Remaining structural-only countries are not claimed to have gained checksum validation.
