# Natural-person tax ID schemes for eight European countries — design spec

Status: **approved** (2026-07-28), not yet implemented. Target release: **1.1.0** (MINOR, per the
versioning note in [CHANGELOG.md](../../../CHANGELOG.md): adding a scheme changes what `validate()`
returns for values that previously failed).

This document extends, and does not amend, the frozen v1.0 contract in
[2026-07-27-daycry-taxid-v1-design.md](2026-07-27-daycry-taxid-v1-design.md). No public signature
changes. One shared-core behaviour changes (§3.2) and it is called out in the changelog.

---

## 1. Problem statement

The v1.0 registry covers all eight of these countries, but in every case only with the identifier a
**company** carries. A natural person in Austria, Finland, France, Hungary, Lithuania, Luxembourg,
Malta or Norway holds a different number, and that number is currently rejected.

The distinction that matters is which violation the library returns today:

- `bad_length` / `malformed_structure` — **no scheme can ever match that shape**, so the country has a
  real gap. These eight are that case.
- `check_digit_failed` — the shape *is* accepted; only an invented check digit failed. Not a gap.
  Greece (`afm`), Croatia (`oib`), Czechia (`ico`/`dic`), Slovakia, Cyprus and Slovenia all fall here,
  because the personal and the corporate identifier share one format. Portugal (`nif`) and Ireland
  (`vat`, which already accepts the PPS shape `1234567FA`) validate a personal number as-is. Twelve
  countries already ship an explicit natural-person scheme (ES `nif`/`nie`, DE `idnr`, IT `cf`,
  PL `pesel`, RO `cnp`, SE `personnummer`, BE `nn`, BG `egn`, DK `cpr`, EE `isikukood`,
  LV `personas_kods`, NL `bsn`). **None of those are touched by this work.**

What today's registry has, against what a person actually holds:

| Country | Existing schemes | What a natural person has | Today's violation |
| --- | --- | --- | --- |
| AT | `uid` (`ATU`+8) | Steuernummer, 9 digits | `malformed_structure` |
| FI | `ytunnus` (8) | Henkilötunnus, `DDMMYYCZZZQ` | `bad_length` |
| FR | `siret` (14), `tva` (11), `siren` (9) | Numéro fiscal / SPI, 13 digits | `bad_length` |
| HU | `adoszam` (11), `anum` (8) | Adóazonosító jel, 10 digits | `bad_length` |
| LT | `pvm` (9/12) | Asmens kodas, 11 digits | `bad_length` |
| LU | `vat` (8) | Matricule, 13 digits | `bad_length` |
| MT | `vat` (8) | Identity card number, 7 digits + letter | `malformed_structure` |
| NO | `orgnr` (9), `mva` (9+`MVA`) | Fødselsnummer, 11 digits | `bad_length` |

## 2. Scope

### 2.1 Inclusion criterion

**Only what the tax administration itself uses as the taxpayer's identifier.** This is the one boundary
that can be applied without re-arguing each country.

It admits the Maltese nine-digit TIN for non-nationals — an official TIN, even though it has no
checksum and no internal structure — because rejecting a legitimate TIN is a *wrong* answer, not a
cautious one, and "structure only, with mandatory `notes`" is precedent already set by `US/ein`,
`US/ssn`, `US/itin` and `DK/cpr`.

It excludes the Austrian **SVNR** (Sozialversicherungsnummer, 10 digits, check digit in the fourth
position, weights `3,7,9,0,5,8,4,2,1,6` mod 11). The algorithm is known and verified; the reason to
leave it out is definitional. Austria's TIN is the Steuernummer. Every one of the twelve personal
numbers already bundled doubles as its country's TIN; the SVNR would be the first that does not, and
once the registry holds a non-tax identifier there is no rule left to answer "what belongs here".
Recorded here so the decision is not re-litigated by accident.

### 2.2 Deliverable

Nine schemes across eight existing countries. Registry totals move from **136 schemes / 88 with a
verified check digit / 77 validators / 4 checksum primitives** to **145 / 95 / 84 / 5**. The
jurisdiction count stays at 94 — every country involved is already present.

### 2.3 Non-goals

- No new countries.
- No changes to any existing scheme, pattern, validator or violation ordering.
- No generator for `docs/countries.md`. That file claims to be generated and is in fact maintained by
  hand; this work keeps it hand-maintained and does not fix the claim.

## 3. Core changes

### 3.1 `Core/Checksum/Verhoeff` — a fifth primitive

Luxembourg's thirteenth digit is a Verhoeff check digit, and nothing in `Core/Checksum/` can produce
one. `Iso7064` is a different family (MOD 11,10 / MOD 11-2 / MOD 37,36) and cannot be bent into it.

```php
final class Verhoeff
{
    public static function isValid(string $digits): bool;   // payload INCLUDING its check digit
    public static function checkDigit(string $payload): int; // payload WITHOUT it
}
```

The standard dihedral-group-D₅ tables: an 8×10 permutation table, a 10×10 multiplication table and a
10-element inverse table. Pure and total, mirroring `Luhn`'s contract exactly — same two method names,
same meaning, so a reader who knows `Luhn` needs no orientation. It belongs in the guarded
framework-free `src/Core` tree; the architecture test covers it with no change.

Verhoeff earns its place over Luhn on transposition: it catches **every** adjacent transposition, which
Luhn does not (`09` ↔ `90`). That property is what its tests must pin, not merely "a known value
passes".

### 3.2 `Core/Normalizer` — `+` becomes a separator

```diff
- private const SEPARATORS = '/[\s.\-\/\\\\_()\[\]:,\']+/';
+ private const SEPARATORS = '/[\s.\-+\/\\\\_()\[\]:,\']+/';
```

Required by Finland: the century marker for someone born in the 1800s is a literal `+`, and the
`IllegalCharacters` check (step 4 of the pipeline, `/[^A-Z0-9&]/`) runs *before* scheme detection, so
today a `+` can never reach a pattern.

This is a **widening in every country**, and deliberately so: any value containing `+` returns
`illegal_characters` today, so nothing that currently validates can change meaning, and `+` joins the
separators a human plausibly types alongside `.`, `-`, `/` and `_`. It also makes the docblock of
`SePersonnummerValidator` true — it already claims the `+` form is stripped during normalization.

Changelog entry goes under **Changed**, not Added, because it is observable from every country.

## 4. Registry entries

All nine are `vat: false`. Every one is `entity: natural` except AT (see §4.1). **No new scheme
overlaps an existing scheme of its country structurally**, verified country by country, so each is
appended after the country's existing schemes and no current detection order changes.

| Country | `code` | `pattern` (compact form) | `lengths` | `mask` | `check_digit_offset` |
| --- | --- | --- | --- | --- | --- |
| AT | `steuernummer` | `/^\d{9}$/` | 9 | `##-###/####` | `[-1, 1]` |
| FI | `henkilotunnus` | `/^\d{6}[A-FU-Y]?\d{3}[0-9A-FHJ-NPR-Y]$/` | 10, 11 | `######-####` | `[-1, 1]` |
| FR | `spi` | `/^[0-3]\d{12}$/` | 13 | — | `[-3, 3]` |
| HU | `adoazonosito` | `/^8\d{9}$/` | 10 | — | `[-1, 1]` |
| LT | `asmens_kodas` | `/^[1-69]\d{10}$/` | 11 | — | `[-1, 1]` |
| LU | `matricule` | `/^\d{13}$/` | 13 | `#### ## ## ### ##` | `[-2, 2]` |
| MT | `id_card` | `/^\d{7}[ABGHLMPZ]$/` | 8 | — | `null` |
| MT | `tin` | `/^\d{9}$/` | 9 | — | `null` |
| NO | `fodselsnummer` | `/^\d{11}$/` | 11 | `###### #####` | `[-2, 2]` |

Scheme names, in the local language where that is common usage: `Steuernummer — Abgabenkontonummer`,
`Henkilötunnus — personal identity code`, `Numéro fiscal de référence (SPI)`,
`Adóazonosító jel — personal tax identifier`, `Asmens kodas — personal code`,
`Matricule — numéro d'identification national`, `Identity card number`,
`TIN — tax identification number (non-nationals)`, `Fødselsnummer — national identity number`.

### 4.1 AT is `both`, not `natural`

The Abgabenkontonummer is issued to individuals and companies alike. The gap being closed is the
person's, but declaring the scheme `natural` would put a falsehood in the data.

### 4.2 FI legitimately has two lengths

Not a concession — a consequence of normalization. With the classic `-` or `+` marker the marker is
stripped and the compact form is **10** characters; with a letter marker (`A`-`F` for the 2000s,
`U`-`Y` for the 1900s) it survives and the form is **11**. The checksum never involves the marker, so
nothing is lost.

The regex is unambiguous by length: an 11-character all-digit value fails, because the optional marker
slot cannot absorb a digit and the marker-less alternative is 10 characters.

The 10-placeholder mask applies to the classic form and prints `010190-123X`, the canonical Finnish
presentation, round-tripping through `normalize()`. The 11-character form falls back to compact, which
is *already* its canonical presentation. Both satisfy `testPrintedFormRoundTrips`.

Examples must therefore include one of each length. Note that `testPatternCompilesAndMatchesEveryExample`
and `testCheckDigitSchemesRejectMutations` only exercise `examples[0]`, so the **10-character form goes
first**.

### 4.3 MT `id_card` declares no `check_digit_offset`

The trailing letter is a category marker, not a verifier. Exposing it as `ParsedTaxId::$checkDigit`
would be a semantic type error, and `notes` says so.

### 4.4 No mask where the official presentation is a single block

FR `spi`, HU `adoazonosito`, LT `asmens_kodas`, MT `id_card` and MT `tin` get no mask. Inventing a
grouping that no tax office uses is worse than printing the compact form.

## 5. Validators

Seven new classes in `src/National/`, each `final`, extending `AbstractNationalValidator`, stateless,
never throwing.

| Class | Algorithm | Reuses | Date sanity |
| --- | --- | --- | --- |
| `AtSteuernummerValidator` | Luhn over all 9 digits | `Luhn::isValid()` | — |
| `FiHenkilotunnusValidator` | `ddmmyyiii mod 31` indexed into `0123456789ABCDEFHJKLMNPRSTUVWXY` | — | month 1-12, day 1-31 |
| `FrSpiValidator` | last 3 digits == `first10 mod 511` | — | — |
| `HuAdoazonositoValidator` | `Σ dᵢ×i` for i=1..9, mod 11 == d₁₀; a remainder of 10 is never issued | `Weights::sum()` | — |
| `LtAsmensKodasValidator` | two-pass mod 11, weights `1..9,1` then `3..9,1,2,3`, second 10 folds to 0 | `Weights::sum()` | month/day unless the value starts with 9 |
| `LuMatriculeValidator` | d₁₂ = Luhn over the first 11; d₁₃ = Verhoeff over the same first 11 | `Luhn`, `Verhoeff` | — |
| `NoFodselsnummerValidator` | `(11 − Σ) mod 11` twice, weights `3,7,6,1,8,9,4,5,2` then `5,4,3,2,7,6,5,4,3,2`; 10 is invalid | `Weights::sum()` | month 1-12; day 1-31 **or** 41-71 |

Date checks follow the house style set by `BgEgnValidator`: a cheap range check, never a calendar.

### 5.1 Austria needs no new arithmetic

The published rule maps digits in odd positions through `0246813579` and takes `(10 − Σ) mod 10`. That
table is exactly "double, subtract 9 if over 9", and for a 9-digit value with the check digit last the
odd indices are precisely the positions Luhn doubles. So the Austrian Steuernummer check **is** Luhn,
and the class is a length guard plus `Luhn::isValid()`. The equivalence is to be re-derived
numerically during implementation, not taken on trust from this paragraph.

### 5.2 Lithuania gets its own class, not a second country on Estonia's

The algorithm is identical to Estonia's isikukood — `python-stdnum`'s `lt/asmens` literally imports
`ee.ik`. Reuse via `COUNTRIES = ['EE', 'LT']` would nonetheless make `supports('EE', 'asmens_kodas')`
and `supports('LT', 'isikukood')` return `true`, a false cross-product. A separate class with its own
weight constants costs eight lines and keeps `supports()` exact; it is also what the repository already
does for `SeOrgnr`/`SeVat`/`SePersonnummer`, all three of which are Luhn.

Latvia is **not** a third user: its personas kods is a single-pass mod 11 with different weights. Two
users do not justify extracting a shared primitive.

### 5.3 Judgment calls, each documented in `notes` or the docblock

- **Norway accepts D-numbers, rejects H-numbers.** A D-number (day offset by 40, issued to
  non-residents) is a real TIN and shares the checksum, hence day 41-71. An H-number (month offset by
  40) exists only inside the health service, hence the strict month.
- **Luxembourg validates no date.** Two independent check digits already give strong protection, and
  whether matricules are issued with `00` month/day is unconfirmed. Same reasoning `LvPersonalCodeValidator`
  records for Latvia.
- **Finland accepts temporary serials 900-999.** `python-stdnum` rejects them by default; they are
  really issued, and rejecting them would be inventing a rule.
- **Austria does not validate the Finanzamt code** (first two digits). `python-stdnum` cross-checks a
  bundled `fa.dat`; that is data that expires, and its absence does not weaken the checksum.
- **Malta requires the eight-character form.** The short form `999L` is the same number with leading
  zeros dropped; IT processing always writes them out, so the pattern demands them and `notes`
  explains it.

### 5.4 Provenance

To be recorded per algorithm in [docs/algorithms.md](../../algorithms.md).

Sources are marked **primary** where the implementation or specification was read directly, and
**secondary** where only a published restatement of it was available. The distinction matters when a
rule later turns out to be wrong: it says where to look first.

| Scheme | Verified against | |
| --- | --- | --- |
| AT `steuernummer` | `python-stdnum` `stdnum/at/tin.py` (odd-position map `0246813579`, `(10 − Σ) mod 10`) | primary |
| FI `henkilotunnus` | `python-stdnum` `stdnum/fi/hetu.py` (century map, 31-character alphabet, mod 31) | primary |
| FR `spi` | `python-stdnum` `stdnum/fr/nif.py` (13 digits, leading `0-3`, `first10 mod 511`) | primary |
| HU `adoazonosito` | Hungarian Wikipedia's statement of the NAV rule (`Σ dᵢ×i`, i=1..9, mod 11; remainder 10 not issued) | secondary |
| LT `asmens_kodas` | `python-stdnum` `stdnum/lt/asmens.py`, which delegates to `stdnum/ee/ik.py` — the algorithm this package already implements for Estonia | primary |
| LU `matricule` | Reference implementation `grischard/lu.snj.matricule` (Luhn then Verhoeff, both over the first 11). Corroborated by the CCSS glossary, which blocks automated fetching and was read only as a published quotation | primary + secondary |
| MT `id_card`, `tin` | OECD / EU TIN country sheet MT (`(0000)999L`, letters M G A P L H B Z; nine digits for non-nationals). The PDFs did not render; read as published summaries | secondary |
| NO `fodselsnummer` | `python-stdnum` `stdnum/no/fodselsnummer.py` (both weight vectors, `(11 − Σ) mod 11`) | primary |

Reference implementations were read to confirm the arithmetic only. Every class here is written from
the stated rule, and no reference value is bundled as an example (§6).

The two `secondary` rows are where §9's residual risk actually lives. Malta carries no checksum, so a
wrong reading there can only mis-shape a pattern — visible and cheap to correct. Hungary does carry
one, and it is the single algorithm in this batch with no primary source: its test row should include
a value whose remainder is 10, since that is the branch a restatement is most likely to have dropped.

## 6. Synthetic examples

One per scheme, two for FI. Each is **computed** with the algorithm from an obviously synthetic base —
birth date `01-01-1990`, a low serial — the same convention as the examples already bundled
(`0101901234` for DK, `198001011231` for SE). Values taken from reference implementations are used only
to verify the local implementation, never bundled. See [docs/licensing.md](../../licensing.md).

## 7. Tests

1. **`tests/Core/ChecksumTest.php`** — Verhoeff: a known reference value; `checkDigit()` → `isValid()`
   round-trip; rejection of every single-digit error; rejection of every adjacent transposition (the
   property that distinguishes it from Luhn); rejection of non-digits.
2. **`tests/Core/NormalizerTest.php`** — `+` is stripped, and a value containing one no longer yields
   `illegal_characters`.
3. **`tests/National/EuropeValidatorsTest.php`** — nine `yield` rows of valid and invalid values. Per
   country, the boundary case that would hide a wrong implementation: AT a broken Luhn; FI a control
   character outside the alphabet, both lengths, an impossible date; FR a leading `4` and a shifted
   511 key; HU a value not starting with 8 and one whose remainder is 10; LT both passes and a leading
   7/8; LU **Luhn right with Verhoeff wrong, and the reverse** — the case that justifies two digits;
   MT a letter outside the set; NO a valid D-number, a rejected H-number, a broken second check digit.
4. **`tests/Registry/CountriesDataIntegrityTest.php`** — unchanged as the gate; its per-scheme
   assertions run on the new data automatically. Only the documented floor moves: `>= 130` schemes
   becomes `>= 145`, equal to the new actual count, so a later removal fails loudly.

## 8. Documentation

- `docs/countries.md` — nine rows, plus the count line in the header.
- `docs/algorithms.md` — Verhoeff as the fifth primitive (the file currently says "four"), and the nine
  provenance entries from §5.4.
- `README.md`, `CLAUDE.md` — the coverage counters.
- `CHANGELOG.md` — a `[1.1.0]` section: **Added** the nine schemes, the seven validators and the
  Verhoeff primitive; **Changed** the `Normalizer` separator set, with its cross-country effect stated.

## 9. Residual risk

The nine algorithms are verified against reference implementations and official or secondary sources,
**not against genuinely issued numbers**. The mutation test proves a checksum discriminates; it cannot
prove the rule is the right one. If a rule were wrong the symptom would be rejecting valid
identifiers in production.

The two where that risk is heaviest:

- **LU** — Verhoeff can be implemented inverted and still pass its own round-trip tests. Its tests must
  include an externally-stated reference value, not only self-consistency.
- **FR** — 511 is an unusual modulus and invites an off-by-one in which digits feed it.

## 10. Work sequence

Approved: one vertical slice per country, each commit green.

| Commit | Content |
| --- | --- |
| 1 | `Verhoeff` + the `Normalizer` separator, with their tests. Both prerequisites, both verifiable alone. |
| 2-10 | One country per commit — validator, test row, registry entry, computed example — running `tests/Registry` before committing. Order: AT, FI, FR, HU, LT, LU, MT (both schemes together), NO. |
| 11 | Documentation and changelog. |

`docs/algorithms.md` currently carries uncommitted line-reflow changes that break the file's 100-column
convention. Discarding them before starting keeps this work's diff clean; that is the author's call,
not something this work does silently.
