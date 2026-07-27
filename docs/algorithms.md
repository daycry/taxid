# Algorithms and their provenance

What each bundled check digit actually is, and — just as important — where the gaps are.

Of 136 schemes, **88 verify a real check digit**. The other 48 validate structure only. This file
says which is which and why, because "no checksum" is a meaningful limitation and hiding it would be
worse than not shipping the country at all.

## The four primitives

Nearly every national algorithm is one of four ideas with different constants. They live in
`src/Core/Checksum/` and are tested directly against published reference values, because a bug in one
of them is a bug in dozens of countries at once.

| Primitive | Idea | Used by |
| --- | --- | --- |
| `Weights` | Multiply each digit by a positional weight, sum, take a modulus. | Most of the EU, all of Latin America. |
| `Luhn` | ISO/IEC 7812-1 mod-10, double every second digit from the right. | FR, SE, IT, CA, IL, and (over base 36) IN. |
| `Mod97` | Long numeric strings mod 97, computed digit by digit. | BE, GB, FR's TVA key, NL's post-2020 rule. |
| `Iso7064` | The ISO check-character standard: MOD 11,10 / MOD 11-2 / MOD 37,36. | HR, RS, DE, CN. |

The two mod-11 conventions are worth knowing apart, since the same modulus produces different digits:

- `Weights::mod11ComplementDigit()` — `11 - (sum mod 11)`, with 10 and 11 folded however the country
  says. The European convention.
- `Weights::mod11RemainderDigit()` — `sum mod 11`, where a remainder below 2 gives `0`. Brazil,
  Colombia and their neighbours.

## Europe

| Country | Scheme | Algorithm |
| --- | --- | --- |
| AT | `uid` | Alternating 1/2 weighting over 7 digits, digit-summed, closed at `(96 − total) mod 10`. |
| BE | `vat` | Last two digits = `97 − (first eight mod 97)`. Nine-digit legacy form left-padded. |
| BE | `nn` | Same mod-97 rule; born-2000-or-later needs a literal `2` prefixed, so both readings are tried. |
| BG | `eik` | Weights 1…8, retrying with 3…10 on a remainder of 10; the 13-digit form adds a second pass. |
| BG | `egn` | Weights `248510973`, mod-11, 10 folds to 0. Month encodes the century by ±20/±40. |
| CH | `uid`, `mwst` | Weights `54327654`, mod-11. `CHE` is part of the number, not a prefix. |
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
| FR | `siren`, `siret` | Luhn, with La Poste's documented exception (digit sum divisible by 5). |
| FR | `tva` | Numeric key = `(12 + 3 × (SIREN mod 97)) mod 97`. **Alphabetic keys are not verifiable** — see below. |
| GB | `vat` | Weights 8…2 plus the two check digits, total divisible by 97 — **or** by 97 after adding 55 (the "9755" series). `GD`/`HA` numbers have no checksum. |
| GR | `afm` | Weights are powers of two, 256…2, mod-11 with 10 folded to 0. |
| HR | `oib` | ISO 7064 MOD 11,10. |
| HU | `anum`, `adoszam` | Weights `9731973` over the first eight digits, closed mod 10. |
| IE | `vat` | Weights 8…2 mod 23 → `WABCDEFGHIJKLMNOPQRSTUV`; the post-2013 trailing letter joins with weight 9. |
| IS | `kennitala` | Weights `32765432`, mod-11. Companies add 40 to the day field. |
| IT | `piva` | Luhn. |
| IT | `cf` | Two 36-entry lookup tables (odd/even position), total mod 26. Omocodia substitutions handled. |
| LT | `pvm` | Weights counting up from 1, wrapping after 9; retried shifted by two on a remainder of 10. |
| LU | `vat` | Last two digits = first six mod 89. |
| LV | `vat` | Weights `9148310257 6` with the documented `9…`/remainder-4 correction. Person-shaped codes fall through to the personal-code scheme. |
| LV | `personas_kods` | `(1101 − sum) mod 11 mod 10`. |
| MT | `vat` | Weights `346789` over six digits, `37 − (sum mod 37)`. |
| NL | `vat` | Classic elfproef, **or** a mod-97 rule over the letter-expanded string for post-2020 numbers. |
| NL | `bsn` | Elfproef with the ninth digit subtracted rather than added. |
| NO | `orgnr`, `mva` | Weights `32765432`, mod-11. |
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
| TR | `vkn` | Per-digit shift, powers of two, mod 9; closed mod 10. |
| TR | `tckn` | Two check digits: `(7×odd − even) mod 10`, then the sum of the first ten mod 10. |

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
| KR | `brn` | Weights `137137135`, plus the ninth digit's carry from `×5 ÷ 10`, closed mod 10. |
| NZ | `ird` | Weights `32765432`, retried with `74325276` when the first pass demands a 10. |

## Where there is no checksum, and why

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
| `SG/uen`, `TW/ban`, `GT/nit` | A check character exists but the algorithm is not published by the registry authority. |
| `JP/mynumber`, `KR` personal numbers | Deliberately limited: these are legally restricted personal identifiers, and validating them is not a reason to collect them. |
| AD, AE, AL, AM, AZ, BA, BO, BY, CR, DZ, EG, GE, HK, HN, ID, KE, KZ, LI, MA, MD, ME, MK, MY, NG, NI, PA, PH, PK, SA, SV, TH, TN, VN | No public check-digit algorithm. |

### The United States in particular

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

## How to check any of this yourself

Every algorithm has its own test with real branches and digit-flipped negatives
(`tests/National/`), and the data-integrity test bumps each digit of every bundled example and
requires at least half the mutations to be rejected. If you believe one of these is wrong, that test
is the fastest way to prove it: add the number you think should validate, and watch it fail.
