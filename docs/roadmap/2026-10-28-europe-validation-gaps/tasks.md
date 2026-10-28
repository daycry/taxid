# European tax identifier gaps

User-authorized follow-up for CH, MD, NO, IS and MK. Preserve existing unrelated changes.
Use synthetic fixtures and keep network requests out of the test suite.

- T-01: CH VAT lengths and personal AHV/AVS checksum. Completed.
  RED: EuropeanCoverageTest::testSwissVatLengthsIncludeEveryLanguageSuffix — expected [15,16], got [16] · 2026-10-28.
  RED: EuropeanCoverageTest::testSwissPersonalAhvUsesEan13AndPreservesFormatting — valid AHV rejected · 2026-10-28.
- T-02: IS century/calendar checks and NO calendar plausibility. Completed.
  RED: EuropeanCoverageTest::testIcelandicCalendarAndCentury (invalid century) — true instead of false · 2026-10-28.
  RED: EuropeanCoverageTest::testIcelandicCalendarAndCentury (1900 is not a leap year) — true instead of false · 2026-10-28.
  RED: EuropeanCoverageTest::testNorwegianCalendarRejectsImpossibleDatesEvenWithCorrectChecksums — February 31 accepted · 2026-10-28.
- T-03: MD IDNO checksum and distinct personal IDNP; MK personal EMBG. Completed.
  RED: EuropeanCoverageTest::testMoldovanOrganizationChecksumRejectsAMutatedDigit — altered check digit accepted · 2026-10-28.
  RED: EuropeanCoverageTest::testMoldovanPersonalIdsAreDistinctFromOrganizationIds — IDNP scheme absent · 2026-10-28.
  RED: EuropeanCoverageTest::testMacedonianPersonalIdsCheckDateRegionAndControl — EMBG scheme absent · 2026-10-28.
  RED: EuropeanCoverageTest::testNationalVerifiersRejectUnnormalizedInput (MD IDNO / MK EMBG) — trailing newline accepted by direct verifier · 2026-10-28.
- T-04: Coverage/provenance/docs and complete test, analysis and style checks. Completed.
  TDD n/a: documentation. Required checks: composer test, composer analyze, composer cs.

At this stage EDB was deferred because the reference implementation recorded uncertainty.
The later [EDB/Norway follow-up](../2026-10-28-edb-norwegian-century/tasks.md) resolves
that deferral with additional government-published corroboration and records the new checks.

Verification on 2026-10-28 (PHP 8.4.18):

- Focused European validation: 104 tests / 391 assertions passed before the extra direct-input guards.
- Registry + European regression suite: 1232 tests / 4498 assertions passed.
- `composer test`: OK (1623 tests, 5578 assertions).
- `composer analyze`: PHPStan level 8, no errors.
- `composer cs`: 0 of 176 files require fixes.
- `git diff --check`: passed.
- Registry: 94 jurisdictions, 149 schemes, 100 arithmetic checksum schemes.

The figures above record verification of this phase before the later follow-up.
The EDB checksum deferral and Norwegian calendar-century limitation were subsequently
addressed in that follow-up. Explicit `embg` selection remains necessary for personal
fields because EDB does not interpret personal dates or registration regions.

No public API signatures changed. No network calls or real taxpayer records were
added to the tests. The pre-existing working-tree changes were retained.
