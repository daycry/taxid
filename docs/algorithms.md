# Algorithms and their provenance

[Documentation Index](README.md) | [Package README](../README.md)

What each bundled check digit actually is, and — just as important — where the gaps are.

Of 149 schemes, **101 support an arithmetic checksum**. The other 48 validate structure and,
where available, reserved ranges. Some accepted variants lack a complete checksum;
`ValidationResult::checksumVerified` identifies these for each successful value. This file
says which is which and why, because "no checksum" is a meaningful limitation and hiding it would be
worse than not shipping the country at all.

---

## The Five Primitives

Nearly every national algorithm is one of five ideas with different constants. They live in
`src/Core/Checksum/` and are tested directly against published reference values, because a bug in one
of them is a bug in dozens of countries at once.

| Primitive | Idea | Used by |
| --- | --- | --- |
| `Weights` | Multiply each digit by a positional weight, sum, take a modulus. | Most of the EU, all of Latin America. |
| `Luhn` | ISO/IEC 7812-1 mod-10, double every second digit from the right. | FR, SE, IT, CA, IL, and (over base 36) IN. |
| `Mod97` | Long numeric strings mod 97, computed digit by digit. | BE, GB, FR's TVA key, NL's post-2020 rule. |
| `Iso7064` | The ISO check-character standard: MOD 11,10 / MOD 11-2 / MOD 37,36. | HR, RS, DE, CN. |
| `Verhoeff` | Dihedral-group D₅ folding; catches every adjacent transposition, which Luhn does not. | Luxembourg |

The two mod-11 conventions are worth knowing apart, since the same modulus produces different digits:

- `Weights::mod11ComplementDigit()` — `11 - (sum mod 11)`, with 10 and 11 folded however the country
  says. The European convention.
- `Weights::mod11RemainderDigit()` — `sum mod 11`, where a remainder below 2 gives `0`. Brazil,
  Colombia and their neighbours.

---

## Europe

| Country | Scheme | Algorithm |
| --- | --- | --- |
| AT | `uid` | Alternating 1/2 weighting over 7 digits, digit-summed, closed at `(96 − total) mod 10`. |
| AT | `steuernummer` | Luhn over all nine digits — the published odd-position table `0246813579` *is* "double, subtract 9 if over 9", verified identical across 100 000 bodies. The Finanzamt code is not checked. |
| BE | `vat` | Last two digits = `97 − (first eight mod 97)`. Nine-digit legacy form left-padded. |
| BE | `nn` | Same mod-97 rule; born-2000-or-later needs a literal `2` prefixed, so both readings are tried. |
| BG | `eik` | Weights 1…8, retrying with 3…10 on a remainder of 10; the 13-digit form adds a second pass. |
| BG | `egn` | Weights `248510973`, mod-11, 10 folds to 0. Month encodes the century by ±20/±40. |
| CH | `uid`, `mwst` | Weights `54327654`, mod-11. `CHE` is part of the number, not a prefix. |
| CH | `ahv` | EAN-13 mod-10 with alternating 1/3 weights. Exactly 13 digits, always starting with 756. |
| CY | `vat` | Odd positions through an irregular substitution table, total mod 26 → check letter. |
| CZ | `ico`, `dic` | 8 digits: weights 8…2, `(11 − r) mod 10`. 9/10 digits: a rodné číslo, divisible by 11. |
| DE | `vat` | ISO 7064 MOD 11,10 — the BZSt's product/sum recurrence is exactly that standard. |
| DE | `idnr` | Same MOD 11,10 over the first ten digits. |
| DK | `cvr` | Weights `2765432`, mod-11; a remainder of 1 is unissuable. |
| EE | `vat` | Weights 3,7,1 repeating, closed mod 10. |
| EE | `isikukood` | Two-pass mod-11: weights 1…9,1 then 3…9,1,2,3. |
| ES | `nif`, `nie` | `number mod 23` indexes `TRWAGMYFPDXBNJZSQVHLCKE`. NIE's leading X/Y/Z stands for 0/1/2. |
| ES | `cif` | Luhn-style sum over 7 digits; whether the control is a digit or a letter depends on the organisation letter. |
| FI | `ytunnus` | Weights `7 9 10 5 8 4 2`, mod-11; a remainder of 1 is unissuable. |
| FI | `henkilotunnus` | The nine digits of date and serial read as one integer, mod 31, indexing `0123456789ABCDEFHJKLMNPRSTUVWXY` (no G, I, O, Q, Z). The century marker takes no part, which is why ten- and eleven-character forms both validate. |
| FR | `siren`, `siret` | Luhn, with La Poste's documented exception (digit sum divisible by 5). |
| FR | `tva` | Numeric key = `(12 + 3 × (SIREN mod 97)) mod 97`. **Alphabetic keys are not verifiable** — see below. |
| FR | `spi` | Last three digits = first ten mod 511. Accumulated digit by digit, since ten digits overflow a 32-bit integer. |
| GB | `vat` | Weights 8…2 plus the two check digits, total divisible by 97 — **or** by 97 after adding 55 (the "9755" series). `GD`/`HA` numbers have no checksum. |
| GR | `afm` | Weights are powers of two, 256…2, mod-11 with 10 folded to 0. |
| HR | `oib` | ISO 7064 MOD 11,10. |
| HU | `anum`, `adoszam` | Weights `9731973` over the first eight digits, closed mod 10. |
| HU | `adoazonosito` | Each of the first nine digits times its own position, mod 11; a remainder of 10 is unissuable rather than folded. Always begins with 8. |
| IE | `vat` | Weights 8…2 mod 23 → `WABCDEFGHIJKLMNOPQRSTUV`; the post-2013 trailing letter joins with weight 9. |
| IS | `kennitala` | Weights `32765432`, mod-11. Companies add 40 to the day field. The century marker (8/9/0) and full calendar date are checked. |
| IT | `piva` | Luhn. |
| IT | `cf` | Two 36-entry lookup tables (odd/even position), total mod 26. Omocodia substitutions handled. |
| LT | `pvm` | Weights counting up from 1, wrapping after 9; retried shifted by two on a remainder of 10. |
| LT | `asmens_kodas` | Two-pass mod-11, weights 1…9,1 then 3…9,1,2,3 — identical to Estonia's isikukood. The date is checked unless the leading digit is 9. |
| LU | `vat` | Last two digits = first six mod 89. |
| LU | `matricule` | Two check digits over the **same** first eleven digits: Luhn in the twelfth, Verhoeff in the thirteenth. |
| LV | `vat` | Weights `9148310257 6` with the documented `9…`/remainder-4 correction. Person-shaped codes fall through to the personal-code scheme. |
| LV | `personas_kods` | `(1101 − sum) mod 11 mod 10`. |
| MD | `idno`, `idnp` | The first twelve digits weighted `731731731731`, sum mod 10 equals the final digit. IDNO starts with 1; IDNP starts with 2 or legacy 09. |
| MK | `embg` | Weights `765432765432`, mod-11 complement with 10/11 written as 0. Checks calendar date and domestic regions 41–49 or foreign-resident region 04. |
| MK | `edb` | Weights `765432765432`, mod-11 complement with 10/11 written as 0. No personal date/region interpretation; the all-zero placeholder is rejected. |
| MT | `vat` | Weights `346789` over six digits, `37 − (sum mod 37)`. |
| MT | `id_card` | **No checksum.** Seven digits and a category letter (A, B, G, H, L, M, P, Z); the letter is a category, not a verifier. |
| MT | `tin` | **No checksum and no structure** — the nine-digit TIN of a non-national. Length and character set are all there is. |
| NL | `vat` | Classic elfproef, **or** a mod-97 rule over the letter-expanded string for post-2020 numbers. |
| NL | `bsn` | Elfproef with the ninth digit subtracted rather than added. |
| NO | `orgnr`, `mva` | Weights `32765432`, mod-11. |
| NO | `fodselsnummer` | Two mod-11 complements, weights `376189452` then `5432765432`; a digit computing to 10 is unissuable. Day +40 is a D-number (accepted); month +40 is an H-number (refused). Calendar checks use the published year/serial ranges; pre-2000 D-numbers use 1900 as the calendar century. |
| PL | `nip` | Weights `657234567`, remainder is the check digit; 10 is unissuable. |
| PL | `pesel` | Weights 1,3,7,9 repeating, closed mod 10. |
| PL | `regon` | Weights `89234567` (9 digits) / `2485097361248` (14), remainder mod 11 with 10 → 0. |
| PT | `nif` | Weights 9…2, `11 − (sum mod 11)`, ≥10 folds to 0. |
| RO | `cui` | Body right-aligned into nine positions against the key `753217532`, `(sum × 10) mod 11`. |
| RO | `cnp` | Weights `279146358279`, remainder 10 → 1. |
| RS | `pib` | ISO 7064 MOD 11,10. |
| RU | `inn` | Weighted mod-11-mod-10; the 12-digit form carries two independent check digits. |
| SE | `orgnr`, `vat`, `personnummer` | Luhn over the ten-digit core. |
| SI | `vat` | Weights 8…2, `11 − r`, 10 → 0, 11 invalid. |
| SK | `vat` | No check digit: the whole ten-digit number must be divisible by 11, third digit in `234789`. |
| SK | `ico` | Identical to the Czech IČO — a shared Czechoslovak standard. |
| SK | `rodne_cislo` | The Czechoslovak birth number, shared verbatim with `CZ/dic`: ten digits carry a check digit (first nine mod 11, a remainder of 10 written as 0 for 1954-1985 births); the nine-digit pre-1954 form has none. Month +50 for women, +20 more since 2004. |
| TR | `vkn` | Per-digit shift, powers of two, mod 9; closed mod 10. |
| TR | `tckn` | Two check digits: `(7×odd − even) mod 10`, then the sum of the first ten mod 10. |

---

## Americas

| Country | Scheme | Algorithm |
| --- | --- | --- |
| AR | `cuit` | Weights `5432765432`, mod-11 complement; a computed 10 is never issued. |
| BR | `cpf` | Two mod-11 digits, weights counting down from 10 then 11. Repeated-digit values are reserved. |
| BR | `cnpj` | Same rule, weights `543298765432` / `6543298765432`. Repeated-digit values reserved. |
| CA | `bn`, `sin` | Luhn. A SIN never starts with 0 or 8. |
| CL | `rut` | Cycling weights 2…7 from the right, mod-11; 10 is written `K`, 11 is `0`. |
| CO | `nit` | DIAN weights `3 7 13 17 19 23 …` from the right, remainder folded at 2. |
| DO | `rnc` | Weights `79865432`; remainder 0 → check 2, remainder 1 → check 1. |
| EC | `cedula`, `ruc` | Three algorithms chosen by the third digit: natural (mod-10, Luhn-like), public (mod-11 over 8), company (mod-11 over 9). |
| MX | `rfc` | Homoclave check: the SAT alphabet, weights 13…2, `11 − (sum mod 11)` with 11→`0` and 10→`A`. The two generic RFCs are exempt. |
| PE | `ruc` | Weights `5432765432`, mod-11 with 10→0 and 11→1. |
| PY | `ruc` | Cycling weights from 2 upward, mod-11 with 11→0 and 10→1. |
| US | `ein`, `ssn`, `itin` | **No check digit exists.** Only never-issued ranges are enforced — see below. |
| UY | `rut` | DGI weights `43298765432`, mod-11 complement. |
| VE | `rif` | The type letter enters the sum with weight 4 and its own value; then weights `32765432`. |

---

## Asia-Pacific, Middle East and Africa

| Country | Scheme | Algorithm |
| --- | --- | --- |
| AU | `abn` | Subtract 1 from the leading digit, weights `10 1 3 5 … 19`, total divisible by 89. |
| AU | `acn` | Weights 8…1, closed mod 10. |
| AU | `tfn` | Weights `143758 6 9 10`, total divisible by 11. |
| CN | `uscc` | 31-symbol alphabet (no I/O/S/V/Z), weights are powers of three mod 31. |
| CN | `ric` | ISO 7064 MOD 11-2; the check character may be `X`. |
| IL | `id`, `hp` | Luhn over nine digits. |
| IN | `gstin` | Luhn over base 36, folding each product against 36 rather than 9. |
| JP | `houjinbangou` | Check digit is the **first**: `9 − (weighted sum of the other twelve mod 9)`. |
| JP | `mynumber` | Last digit: mod-11 complement of eleven digits weighted `6 5 4 3 2 7 6 5 4 3 2`; remainders 0 and 1 produce 0. |
| KR | `brn` | Weights `137137135`, plus the ninth digit's carry from `×5 ÷ 10`, closed mod 10. |
| KZ | `bin` (BIN/IIN) | Weights 1…11, mod-11; remainder 10 retries with `3 4 5 6 7 8 9 10 11 1 2`. A second 10 is unissuable. Dates and entity categories are not inferred. |
| NZ | `ird` | Weights `32765432`, retried with `74325276` when the first pass demands a 10. |
| TW | `ban` | Weights `1 2 1 2 1 2 4 1`, each product digit-summed, total divisible by **5** under the 2023 expansion. If the seventh digit is 7, total +1 may instead be divisible by 5. |

---

## Where There Is No Checksum, and Why

These schemes validate structure only. Every one of them carries the same reason in its registry
`notes`: the issuing authority publishes no verifier algorithm, or publishes one whose details are
reported inconsistently enough that implementing it would reject valid numbers.

**Rejecting a valid tax ID is worse than accepting an invalid one**, in almost every application:
one is a lost customer, the other is a row that fails later anyway. So where the algorithm is
uncertain, this package does not guess.

| Scheme | Note |
| --- | --- |
| `FR/tva` with an alphabetic key | The numeric key is fully derived from the SIREN and verified. Alphabetic keys were introduced when the numeric space ran short and the DGFiP does not publish their derivation, so only the SIREN's Luhn check applies. |
| `GB/utr` | HMRC does not publish the UTR check-digit algorithm. |
| `GB/nino` | No check digit; the excluded letters are what the pattern enforces. |
| `DE/steuernummer` | Format and length vary by Land, with no nationwide check digit. |
| `DK/cpr` | The modulus-11 check was abandoned in 2007 precisely because it was exhausting the number space. |
| `IN/pan` | The final letter is a check character, but the Income Tax Department has never published its derivation. |
| `ZA/vat`, `ZA/itr` | SARS describes a modulus-10 check whose exact digit ordering is reported inconsistently across sources; not enforced rather than enforced wrongly. |
| `UA/edrpou`, `UA/inn` | The published two-pass checksum uses range-dependent weights; not implemented. |
| `SG/uen`, `GT/nit` | Checksum not implemented; further source verification is needed before enforcing it. |
| `KR` personal numbers | Not bundled; these are legally restricted personal identifiers. |
| AD, AE, AL, AM, AZ, BA, BO, BY, CR, DZ, EG, GE, HK, HN, ID, KE, LI, MA, ME, MY, NG, NI, PA, PH, PK, SA, SV, TH, TN, VN | No checksum implemented in this package; this is not a claim that no public algorithm exists. |

### Sources for the 2026-10 Validation Expansion

- **Japan:** [e-Gov, Article 5](https://laws.e-gov.go.jp/law/426M60000008085)
  defines the My Number weights and folded mod-11 control. Checksum validation does not
  change the restrictions on collecting or processing the identifier.
- **Taiwan:** the [Ministry of Finance announcement](https://www.fia.gov.tw/singlehtml/3?cntId=c4d9cff38c8642ef8872774ee9987283)
  changes the modulus from 10 to 5 for the 2023 expansion. The weights and seventh-digit
  exception are also documented in the [official inspection method, attachment 7](https://gazette.nat.gov.tw/EG_FileManager/eguploadpub/eg029234/ch04/type2/gov30/num6/Eg.pdf).
- **Kazakhstan:** [Decree 352, section 5](https://adilet.zan.kz/rus/docs/P070000352_)
  specifies the shared BIN/IIN two-pass checksum; [the IIN formation rules](https://adilet.zan.kz/rus/docs/V2300032942)
  restate the control. Synthetic tests cover both passes and the unissuable remainder.

The first expansion added three checks and removed the three US range verifiers from
the checksum count. The European follow-up adds three schemes and four checks, taking
the registry to 149 schemes. The subsequent EDB check brings the checksum total to 101. US range validation
is retained; `hasCheckDigit()` correctly returns false for EIN, SSN and ITIN.

### Sources and Limits for the European Follow-up

- **Switzerland:** [Central Compensation Office, AHV structure](https://www.zas.admin.ch/de/ahv-nummer-identifikator)
  specifies prefix 756, thirteen digits and the EAN-13 control. The earlier UID algorithm
  remains unchanged; MWST has sixteen compact characters, TVA and IVA fifteen.
- **Moldova:** [the state-register specification](https://www.legis.md/cautare/downloadpdf/119470)
  distinguishes organizations from persons. Arithmetic and the legacy personal prefix were
  cross-checked against [iAsig's primary validator implementation](https://github.com/iAsig/idnx-validator),
  not an issuing-authority publication of the formula. New PHP code and fixtures are independently
  authored. The [tax authority's 2026 guidance](https://www.sfs.md/ro/ordinele-de-baze-de-date-de-generalizare/1341)
  also assigns IDNO to registered independent entrepreneurs, hence entity type `both`.
- **North Macedonia:** [the identification-number law, articles 4–5](https://www.refworld.org/sites/default/files/attachments/5aabd7614.pdf)
  specifies the personal date/region/serial/control layout. The mod-11 arithmetic and zero
  folding follow the shared personal-number rule, cross-checked against the
  [primary EMŠO reference implementation](https://github.com/arthurdejong/python-stdnum/blob/master/stdnum/si/emso.py).
  This is reference-implementation evidence for the arithmetic, not a claim that the Macedonian
  issuing authority published the weights. The three-digit year follows that implementation's
  convention (800–999 → 1800–1999; 000–799 → 2000–2799); birth dates are checked as calendar
  dates, not against a person's age or a live register. Require `scheme: 'embg'` when collecting
  a personal identifier: EDB does not apply personal date/region checks.
- **Iceland:** [Registers Iceland](https://www.skra.is/library/Samnyttar-skrar-/Einstaklingar/thjodskra/fylgiskjal%20vottorda_Skjalenska.pdf)
  describes the date, control digit and century marker. The historical marker 8 and the 9/0
  mapping are corroborated by the [primary Business::IS::PIN manual](https://man.freebsd.org/cgi/man.cgi?apropos=0&manpath=FreeBSD+Ports+14.1&query=Business%3A%3AIS%3A%3APIN&sektion=3).
- **Norway:** [Skatteetaten's allocation rules, sections 2-2-1 and 2-2-2](https://www.skatteetaten.no/rettskilder/type/handboker/folkeregisterhandboken/gjeldende/kapittel2-registreringspliktige-personer-identifikasjonsnummer/ID-2-2.001/ID-2-2.006/)
  distinguish ordinary-number year/serial ranges for 1854–1899, 1900–1999, 1940–1999
  and 2000–2039. D-number serials 000–499 refer to pre-2000 dates and 500–999 to
  post-2000 dates. For the former group, 1900 is the calendar-validation century;
  the exact older birth century is not inferred. Impossible dates and unallocated ordinary
  combinations are rejected. This does not implement the new 2032 checksum.

### EDB Verification Follow-up

The EDB rule was initially deferred because its
[original implementation notes](https://lists.arthurdejong.org/python-stdnum-commits/2022/msg00048.html)
recorded uncertainty. Further research corroborated the current
[primary implementation's arithmetic](https://github.com/arthurdejong/python-stdnum/blob/master/stdnum/mk/edb.py)
against 26 government-published EDB values: 25 from the
[Resen municipal gazette](https://resen.gov.mk/wp-content/uploads/2021/04/%D0%A1%D0%BB%D1%83%D0%B6%D0%B1%D0%B5%D0%BD-%D0%B3%D0%BB%D0%B0%D1%81%D0%BD%D0%B8%D0%BA-%D0%B1%D1%80.-16-2016.pdf)
and one [public tax-administration registry entry](https://www.ujp.gov.mk/mk/prebaruvanje_pravni_lica/prikazi?edb=4030003504248).
Additional examples in public ministry documents also matched. This is corroboration
of the reference algorithm, not an issuing-authority publication of the weights or a live
registration check. The PHP implementation and all newly added test fixtures are independent;
real taxpayer values from this research are not copied into the tests. The all-zero
placeholder is explicitly rejected as a library policy.

### The United States in Particular

An EIN, SSN and ITIN have **no check digit at all** — SSNs have been randomized since 2011 and carry
no derivable structure. What the SSA and IRS do publish is which values they will never issue, and
that is exactly what this package enforces, reported as `ReservedValue` rather than
`CheckDigitFailed`:

- SSN: area `000`, `666` or `9xx`; group `00`; serial `0000`.
- ITIN: must start with `9`, with the middle group in 50–65, 70–88, 90–92 or 94–99.
- EIN: only the `00` prefix, because the IRS campus prefix list has grown over the years and pinning
  today's version down would reject numbers issued tomorrow.

Confirming a US taxpayer ID really belongs to someone requires the IRS TIN-matching programme, not
arithmetic.

---

## How to Check Any of This Yourself

Every algorithm has its own test with real branches and digit-flipped negatives
(`tests/National/`), and the data-integrity test bumps each digit of every bundled example and
requires at least half the mutations to be rejected. If you believe one of these is wrong, that test
is the fastest way to prove it: add the number you think should validate, and watch it fail.

### Sources for the 1.1.0 Natural-person Schemes

Six of the nine were verified against a reference implementation read directly: Austria, Finland,
France, Lithuania and Norway against `python-stdnum`, and Luxembourg against a published
implementation of the CCSS rule, whose own page blocks automated fetching and was read only as a
quotation.

Three rest on secondary sources, and that is worth knowing before debugging a rejected identifier:

- **Malta** — the OECD and EU TIN country sheets, read as published summaries rather than from the
  PDFs. Neither Maltese scheme carries a checksum, so a misreading here can only mis-shape a
  pattern.
- **Hungary** — a restatement of the NAV rule rather than the rule itself. It is the one algorithm
  in the batch with a real check digit and no primary source, so it is the first place to look if a
  valid Hungarian identifier is ever refused. Its unissuable-remainder branch is pinned by
  `testHungarianPersonalIdRejectsTheUnwritableRemainder`.
