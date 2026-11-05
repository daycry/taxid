# Montenegro Personal JMBG

User-approved scope: add ME/jmbg alongside the existing eight-digit PIB. Keep chat in
Spanish and repository documentation in English. Use synthetic fixtures; no registry lookups.

- T-01: Register the personal scheme and verify checksum/assurance. Completed.
  RED: MontenegroJmbgTest::testChecksumAndAssurance (ordinary control, sum 74)
  failed: expected valid=true, got false because ME/jmbg is absent · 2026-11-05.
  GREEN: six checksum/assurance cases passed (30 assertions), including remainder one rejection.
- T-02: Check the calendar date, domestic regions 21–29 and direct verifier input. Completed.
  RED: MontenegroJmbgTest::testImpossibleDateWithCorrectChecksumIsRejected
  failed: February 31 with a correct checksum was accepted · 2026-11-05.
  RED: MontenegroJmbgTest::testRegionOutsideMontenegroIsRejected
  failed: region 30 with a correct checksum was accepted · 2026-11-05.
  GREEN: 19 focused tests / 91 assertions passed, including all nine domestic regions,
  region boundaries, 1900/2000 leap dates, direct compact input, parsing and PIB regression.
- T-03: Update coverage, provenance and usage documentation; run required checks. Completed.
  TDD n/a: prose/documentation.

Sources: Montenegro's checksum and registration-area regulations (2008, as corrected),
plus REGOS's published JMBG algorithm for the remainder-zero convention omitted from
the Montenegrin checksum text. Remainder one is unissuable and must not fold to zero.

Final verification (2026-11-05, PHP 8.5.12):

- Focused JMBG and registry/documentation checks: 1241 tests / 4528 assertions passed.
- `composer test`: OK (1663 tests, 5743 assertions).
- `composer analyze`: PHPStan level 8, no errors.
- `composer cs`: no fixes required across 180 files.
- `git diff --check`: passed.
- The supplied value was checked through the public facade, with and without an explicit
  scheme: valid=true, scheme=jmbg, checksumVerified=true. It is not stored in tests or docs.
- Live coverage: 94 jurisdictions, 150 schemes, 102 arithmetic-checksum schemes and
  93 national verifiers. Historical release counts remain unchanged.

Scope limits: domestic regions 21–29 only; the three-digit year follows the existing EMBG
calendar convention. No age, future-date, issuance or live registration check is added.
No API signatures changed; tests use independently calculated synthetic fixtures and no network.
