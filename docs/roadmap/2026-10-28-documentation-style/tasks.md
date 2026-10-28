# Documentation Style Refresh

Reference: the local checkout of `daycry/auth`, README and public documentation guides.

- T-01: Align README badges, feature tables, quick start, contribution and resource sections. Completed.
  TDD n/a: prose/Markdown.
- T-02: Add a documentation index and contribution guide; align public guide navigation,
  section presentation, coverage and extension instructions. Completed.
  TDD n/a: prose/Markdown. Preserve coverage-table rows and existing guide paths.
- T-03: Validate after the user's Composer dependency update. Completed.
  The full suite passed with PHPUnit 11.5.57 before the config refactor (1636 tests / 5621 assertions).
  PHPStan 2.3.3 with phpstan-codeigniter 2.2.0 reported two contradictory constant-result
  checks in Services::config(). The ordered fallback loop retains the same runtime behavior.
  TDD n/a: refactor; existing service/command/helper tests passed afterward (48 tests / 127 assertions).
  PHPStan then passed with no errors. No suppressions, dependency rollback or vendor edits.

Final verification after the config refactor:

- 13 public Markdown files, 119 local links/anchors, no missing targets or unclosed fences.
- All six README PHP blocks pass syntax checks.
- README validation and formatting examples pass against the public facade.
- `composer test`: OK (1636 tests, 5621 assertions), including the coverage-table synchronization tests.
- `composer analyze`: PHPStan 2.3.3 / phpstan-codeigniter 2.2.0, no errors.
- `composer cs`: PHP-CS-Fixer 3.95.27, no files require changes.
- `git diff --check`: passed.

Public guide paths and existing coverage-table cells remain compatible. No documentation
hosting, new CI gates, network-dependent tests or changes to the Auth checkout were introduced.

## PHP 8.5 CI follow-up

- T-04: Add PHP 8.5 to the PHPUnit matrix and update README/maintainer guidance. Completed.
  TDD n/a: workflow configuration and prose. Coverage remains on PHP 8.4; PHPStan and
  code style continue on the minimum supported PHP 8.3. Declare curl and SQLite3 explicitly
  in the test workflow because the integration suite uses them.
- `composer test` on PHP 8.4.18: OK (1636 tests, 5621 assertions).
- Full PHPUnit suite on PHP 8.5.12, with required extensions enabled for the process:
  OK (1636 tests, 5621 assertions). No machine-wide PHP configuration was changed.
- `composer analyze`, `composer cs` and `git diff --check`: passed.
