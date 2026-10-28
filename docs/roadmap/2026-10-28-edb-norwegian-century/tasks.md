# EDB checksum and Norwegian century validation

User authorized further improvements. Retain all prior working-tree changes.

- T-01: Add MK/edb arithmetic validation and reject the all-zero placeholder. Completed.
  RED: EdbAndNorwegianCenturyTest::testEdbRejectsAnIncorrectControlDigit — bad control accepted · 2026-10-28.
  RED: EdbAndNorwegianCenturyTest::testEdbRejectsTheAllZeroPlaceholder — placeholder accepted · 2026-10-28.
- T-02: Infer the Norwegian century from documented national/D-number allocation rules. Completed.
  RED: EdbAndNorwegianCenturyTest::testNorwegianCalendarUsesTheAllocatedCentury (1900 non-leap day) — invalid February 29 accepted · 2026-10-28.
  RED: EdbAndNorwegianCenturyTest::testNorwegianCalendarUsesTheAllocatedCentury (unallocated century combination) — unallocated year/serial pair accepted · 2026-10-28.
- T-03: Update coverage/provenance and run all required checks. Completed. TDD n/a: documentation.

Evidence: the current python-stdnum EDB implementation documents weights 765432765432
and mod-11 with complement 10/11 folded to zero. Independently calculated checks matched
all 26 EDB values read from the official Resen gazette and a public UJP registry entry.
Additional examples in public ministry documents also matched. This corroborates the
reference rule; it is not an official publication of the algorithm or proof of issuance.
No real taxpayer identifiers from that research are added to the test suite.

Norwegian allocation source: Skatteetaten Folkeregister handbook, section 2-2-1 and 2-2-2.

Verification (2026-10-28, PHP 8.4.18):

- Focused EDB/Norway/European tests: 121 tests, 462 assertions passed.
- `composer test`: OK (1636 tests, 5621 assertions).
- `composer analyze`: PHPStan level 8, no errors.
- `composer cs`: 0 of 178 files require fixes.
- `git diff --check`: passed.
- Live registry: 94 jurisdictions, 149 schemes, 101 arithmetic checksum schemes.
- Public facade checks: mutated EDB gives `check_digit_failed`; zero placeholder gives
  `reserved_value`; the 1900 leap-day fixture is rejected, the 2000 fixture is accepted;
  the user's Swiss AHV/AVS `756.1234.5678.97` still passes with checksum verification.

The earlier EDB deferral is resolved with the evidence described above and in
`docs/algorithms.md`. An explicit EMBG scheme remains necessary to require personal
date/region rules. No API signatures changed; no network calls were added to tests.
Pre-2000 D-number validation uses 1900 for calendar calculations and does not expose
an exact older birth century. The future 2032 checksum remains outside this change.
