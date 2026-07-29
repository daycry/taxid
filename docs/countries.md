# Country coverage

**94 jurisdictions, 145 schemes, 95 of them with a verified check digit.**

This file is generated from `src/Registry/data/countries.php`; `php spark taxid:countries` prints the
same thing from the live registry. If the two ever disagree, believe the command.

Reading the table:

- **Scheme** — the code you pass as the third argument to `validate()`, or as `--scheme` on the CLI.
- **VAT** — whether the value doubles as the country's VAT number, so `vatNumber()` returns something.
- **Entity** — `natural` (a person), `legal` (a company), `both` (the same scheme serves either).
- **Checksum** — ✅ means a check digit is genuinely verified. **—** means the jurisdiction publishes
  no verifier algorithm, so validation stops at length and structure; each such scheme carries a
  `notes` entry in the registry saying why. See [algorithms.md](algorithms.md).
- **Example** — a synthetic, checksum-valid value. Not a real taxpayer: see [licensing.md](licensing.md).

Where a country has several schemes they are listed in **registry order**, which is also detection
order — the first structural match wins when you don't name a scheme.

| Country | Name | Scheme | Scheme name | Entity | VAT | Lengths | Checksum | Example |
| --- | --- | --- | --- | --- | --- | --- | --- | --- |
| `AD` | Andorra | `nrt` | NRT — Número de Registre Tributari | both | ✅ | 8 | — | `A123456Z` |
| `AE` | United Arab Emirates | `trn` | TRN — Tax Registration Number | both | ✅ | 15 | — | `100000000000003` |
| `AL` | Albania | `nipt` | NIPT — Numri i Identifikimit për Personin e Tatueshëm | both | ✅ | 10 | — | `J91402501L` |
| `AM` | Armenia | `tin` | ՀՎՀՀ — Taxpayer Identification Number | both | ✅ | 8 | — | `12345678` |
| `AR` | Argentina | `cuit` | CUIT/CUIL — Clave Única de Identificación Tributaria/Laboral | both | ✅ | 11 | ✅ | `20123456786` |
| `AT` | Austria | `uid` | UID — Umsatzsteuer-Identifikationsnummer | legal | ✅ | 9 | ✅ | `U10223006` |
| `AT` | Austria | `steuernummer` | Steuernummer — Abgabenkontonummer | both | — | 9 | ✅ | `123456782` |
| `AU` | Australia | `abn` | ABN — Australian Business Number | both | ✅ | 11 | ✅ | `51824753556` |
| `AU` | Australia | `acn` | ACN — Australian Company Number | legal | — | 9 | ✅ | `004085616` |
| `AU` | Australia | `tfn` | TFN — Tax File Number | both | — | 8, 9 | ✅ | `123456782` |
| `AZ` | Azerbaijan | `voen` | VÖEN — Taxpayer Identification Number | both | ✅ | 10 | — | `1234567890` |
| `BA` | Bosnia and Herzegovina | `jib` | JIB — Jedinstveni identifikacioni broj | legal | ✅ | 13 | — | `1234567890123` |
| `BE` | Belgium | `vat` | BTW/TVA — Ondernemingsnummer | legal | ✅ | 9, 10 | ✅ | `0776091951` |
| `BE` | Belgium | `nn` | Rijksregisternummer / Numéro de registre national | natural | — | 11 | ✅ | `85073003328` |
| `BG` | Bulgaria | `egn` | ЕГН — Единен граждански номер | natural | ✅ | 10 | ✅ | `7523169263` |
| `BG` | Bulgaria | `eik` | ЕИК/BULSTAT — company number | legal | ✅ | 9, 13 | ✅ | `831902483` |
| `BO` | Bolivia | `nit` | NIT — Número de Identificación Tributaria | both | ✅ | 7, 8, 9, 10, 11, 12 | — | `1234567` |
| `BR` | Brazil | `cnpj` | CNPJ — Cadastro Nacional da Pessoa Jurídica | legal | ✅ | 14 | ✅ | `11222333000181` |
| `BR` | Brazil | `cpf` | CPF — Cadastro de Pessoas Físicas | natural | — | 11 | ✅ | `11144477735` |
| `BY` | Belarus | `unp` | УНП — Payer Account Number | both | ✅ | 9 | — | `100003234` |
| `CA` | Canada | `bn` | BN — Business Number (and GST/HST account) | legal | ✅ | 9, 15 | ✅ | `123456782` |
| `CA` | Canada | `sin` | SIN — Social Insurance Number | natural | — | 9 | ✅ | `130692544` |
| `CH` | Switzerland | `mwst` | MWST/TVA/IVA — VAT number | legal | ✅ | 16 | ✅ | `CHE116281839MWST` |
| `CH` | Switzerland | `uid` | UID — Unternehmens-Identifikationsnummer | legal | — | 12 | ✅ | `CHE116281839` |
| `CL` | Chile | `rut` | RUT/RUN — Rol Único Tributario | both | ✅ | 8, 9 | ✅ | `123456785` |
| `CN` | China | `uscc` | 统一社会信用代码 — Unified Social Credit Identifier | legal | ✅ | 18 | ✅ | `91350100M000100Y56` |
| `CN` | China | `ric` | 居民身份证号码 — Resident identity card number | natural | — | 18 | ✅ | `110101199003070011` |
| `CO` | Colombia | `nit` | NIT — Número de Identificación Tributaria | both | ✅ | 9, 10, 11 | ✅ | `8300013381` |
| `CR` | Costa Rica | `cedula_juridica` | Cédula jurídica | legal | ✅ | 10 | — | `3101123456` |
| `CY` | Cyprus | `vat` | ΦΠΑ — VAT registration number | both | ✅ | 9 | ✅ | `10259033P` |
| `CZ` | Czechia | `ico` | IČO — Identifikační číslo osoby | legal | — | 8 | ✅ | `00006947` |
| `CZ` | Czechia | `dic` | DIČ — Daňové identifikační číslo | both | ✅ | 8, 9, 10 | ✅ | `00006947` |
| `DE` | Germany | `vat` | USt-IdNr — Umsatzsteuer-Identifikationsnummer | legal | ✅ | 9 | ✅ | `136695976` |
| `DE` | Germany | `idnr` | IdNr — Steuerliche Identifikationsnummer | natural | — | 11 | ✅ | `12345678903` |
| `DE` | Germany | `steuernummer` | Steuernummer — local tax office number | both | — | 10, 11, 12, 13 | — | `1234567890` |
| `DK` | Denmark | `cvr` | CVR-nummer | legal | ✅ | 8 | ✅ | `13585628` |
| `DK` | Denmark | `cpr` | CPR-nummer — personal number | natural | — | 10 | — | `0101901234` |
| `DO` | Dominican Republic | `rnc` | RNC — Registro Nacional del Contribuyente | legal | ✅ | 9 | ✅ | `101010101` |
| `DO` | Dominican Republic | `cedula` | Cédula de identidad y electoral | natural | — | 11 | — | `00112345678` |
| `DZ` | Algeria | `nif` | NIF — Numéro d'Identification Fiscale | both | ✅ | 15 | — | `000216001808337` |
| `EC` | Ecuador | `ruc` | RUC — Registro Único de Contribuyentes | both | ✅ | 13 | ✅ | `1710034065001` |
| `EC` | Ecuador | `cedula` | Cédula de identidad | natural | — | 10 | ✅ | `1710034065` |
| `EE` | Estonia | `isikukood` | Isikukood — personal identification code | natural | — | 11 | ✅ | `37605030299` |
| `EE` | Estonia | `vat` | KMKR — VAT registration number | legal | ✅ | 9 | ✅ | `100594102` |
| `EG` | Egypt | `tin` | Tax Registration Number | both | ✅ | 9 | — | `100531385` |
| `ES` | Spain | `nif` | NIF — Número de Identificación Fiscal (personas físicas) | natural | ✅ | 9 | ✅ | `12345678Z` |
| `ES` | Spain | `nie` | NIE — Número de Identidad de Extranjero | natural | ✅ | 9 | ✅ | `X1234567L` |
| `ES` | Spain | `cif` | CIF — Código de Identificación Fiscal (personas jurídicas) | legal | ✅ | 9 | ✅ | `B12345674` |
| `FI` | Finland | `ytunnus` | Y-tunnus — business identity code | legal | ✅ | 8 | ✅ | `02012566` |
| `FI` | Finland | `henkilotunnus` | Henkilötunnus — personal identity code | natural | — | 10, 11 | ✅ | `010190123M` |
| `FR` | France | `siret` | SIRET — establishment number | legal | — | 14 | ✅ | `55210055400013` |
| `FR` | France | `tva` | TVA intracommunautaire | legal | ✅ | 11 | ✅ | `96552100554` |
| `FR` | France | `siren` | SIREN — legal entity number | legal | — | 9 | ✅ | `552100554` |
| `FR` | France | `spi` | Numéro fiscal de référence (SPI) | natural | — | 13 | ✅ | `1234567890066` |
| `GB` | United Kingdom | `vat` | VAT registration number | legal | ✅ | 5, 9, 12 | ✅ | `123456782` |
| `GB` | United Kingdom | `utr` | UTR — Unique Taxpayer Reference | both | — | 10 | — | `1234567890` |
| `GB` | United Kingdom | `nino` | NINO — National Insurance number | natural | — | 9 | — | `AB123456C` |
| `GE` | Georgia | `tin` | Taxpayer Identification Number | both | ✅ | 9 | — | `123456789` |
| `GR` | Greece | `afm` | ΑΦΜ — Αριθμός Φορολογικού Μητρώου | both | ✅ | 9 | ✅ | `094259216` |
| `GT` | Guatemala | `nit` | NIT — Número de Identificación Tributaria | both | ✅ | 5, 6, 7, 8, 9, 10, 11, 12 | — | `12345678` |
| `HK` | Hong Kong | `br` | Business Registration Number | legal | — | 8 | — | `12345678` |
| `HN` | Honduras | `rtn` | RTN — Registro Tributario Nacional | both | ✅ | 14 | — | `08019995123453` |
| `HR` | Croatia | `oib` | OIB — Osobni identifikacijski broj | both | ✅ | 11 | ✅ | `69435151530` |
| `HU` | Hungary | `adoszam` | Adószám — full tax number | legal | — | 11 | ✅ | `10778757443` |
| `HU` | Hungary | `anum` | Közösségi adószám — EU VAT number | legal | ✅ | 8 | ✅ | `10778757` |
| `HU` | Hungary | `adoazonosito` | Adóazonosító jel — personal tax identifier | natural | — | 10 | ✅ | `8123456786` |
| `ID` | Indonesia | `npwp` | NPWP — Nomor Pokok Wajib Pajak | both | ✅ | 15, 16 | — | `013456789012000` |
| `IE` | Ireland | `vat` | VAT registration number | both | ✅ | 8, 9 | ✅ | `1234567T` |
| `IL` | Israel | `hp` | ח.פ. — company number / VAT dealer number | legal | ✅ | 9 | ✅ | `512345679` |
| `IL` | Israel | `id` | מספר זהות — personal identity number | natural | — | 9 | ✅ | `123456782` |
| `IN` | India | `gstin` | GSTIN — Goods and Services Tax Identification Number | both | ✅ | 15 | ✅ | `27AAPFU0939F1ZV` |
| `IN` | India | `pan` | PAN — Permanent Account Number | both | — | 10 | — | `AAPFU0939F` |
| `IS` | Iceland | `kennitala` | Kennitala — identification number | both | — | 10 | ✅ | `1203894599` |
| `IS` | Iceland | `vsk` | VSK — VAT registration number | legal | ✅ | 5, 6 | — | `12345` |
| `IT` | Italy | `cf` | Codice fiscale | natural | — | 16 | ✅ | `RSSMRA85T10A562S` |
| `IT` | Italy | `piva` | Partita IVA | legal | ✅ | 11 | ✅ | `00743110157` |
| `JP` | Japan | `houjinbangou` | 法人番号 — Corporate Number | legal | ✅ | 13 | ✅ | `3700150000123` |
| `JP` | Japan | `mynumber` | マイナンバー — Individual Number | natural | — | 12 | — | `123456789018` |
| `KE` | Kenya | `pin` | KRA PIN — Personal Identification Number | both | ✅ | 11 | — | `A123456789Z` |
| `KR` | South Korea | `brn` | 사업자등록번호 — Business Registration Number | legal | ✅ | 10 | ✅ | `1234567891` |
| `KZ` | Kazakhstan | `bin` | BIN/IIN — business or individual identification number | both | ✅ | 12 | — | `123456789012` |
| `LI` | Liechtenstein | `uid` | MWST-Nummer | legal | ✅ | 5 | — | `12345` |
| `LT` | Lithuania | `pvm` | PVM mokėtojo kodas — VAT number | both | ✅ | 9, 12 | ✅ | `213179412` |
| `LT` | Lithuania | `asmens_kodas` | Asmens kodas — personal code | natural | — | 11 | ✅ | `39001011237` |
| `LU` | Luxembourg | `vat` | Numéro de TVA | legal | ✅ | 8 | ✅ | `20260743` |
| `LU` | Luxembourg | `matricule` | Matricule — numéro d'identification national | natural | — | 13 | ✅ | `1990010112385` |
| `LV` | Latvia | `personas_kods` | Personas kods — personal code | natural | — | 11 | ✅ | `32081012341` |
| `LV` | Latvia | `vat` | PVN reģistrācijas numurs — VAT number | legal | ✅ | 11 | ✅ | `40003009497` |
| `MA` | Morocco | `if` | IF — Identifiant Fiscal | both | ✅ | 7, 8 | — | `12345678` |
| `MC` | Monaco | `tva` | TVA intracommunautaire (French system) | legal | ✅ | 11 | ✅ | `96552100554` |
| `MC` | Monaco | `siren` | SIREN (French registry) | legal | — | 9 | ✅ | `552100554` |
| `MD` | Moldova | `idno` | IDNO — state identification number | legal | ✅ | 13 | — | `1234567890123` |
| `ME` | Montenegro | `pib` | PIB — Poreski identifikacioni broj | both | ✅ | 8 | — | `12345678` |
| `MK` | North Macedonia | `edb` | ЕДБ — tax number | both | ✅ | 13 | — | `4030000375897` |
| `MT` | Malta | `vat` | VAT registration number | legal | ✅ | 8 | ✅ | `12345634` |
| `MT` | Malta | `id_card` | Identity card number | natural | — | 8 | — | `0123456M` |
| `MT` | Malta | `tin` | TIN — tax identification number (non-nationals) | natural | — | 9 | — | `123456789` |
| `MX` | Mexico | `rfc` | RFC — Registro Federal de Contribuyentes | both | ✅ | 12, 13 | ✅ | `AAA010101AA1` |
| `MY` | Malaysia | `tin` | TIN — Nombor Cukai Pendapatan | both | ✅ | 11, 12, 13 | — | `C1234567890` |
| `NG` | Nigeria | `tin` | TIN — Taxpayer Identification Number | both | ✅ | 8, 9, 10 | — | `12345678` |
| `NI` | Nicaragua | `ruc` | RUC — Registro Único de Contribuyentes | both | ✅ | 14 | — | `J0310000000004` |
| `NL` | Netherlands | `vat` | Btw-identificatienummer | both | ✅ | 12 | ✅ | `123456782B01` |
| `NL` | Netherlands | `bsn` | BSN — Burgerservicenummer | natural | — | 9 | ✅ | `123456782` |
| `NO` | Norway | `mva` | MVA-nummer — VAT number | legal | ✅ | 12 | ✅ | `974760673MVA` |
| `NO` | Norway | `orgnr` | Organisasjonsnummer | legal | — | 9 | ✅ | `974760673` |
| `NO` | Norway | `fodselsnummer` | Fødselsnummer — national identity number | natural | — | 11 | ✅ | `01019012480` |
| `NZ` | New Zealand | `ird` | IRD number | both | ✅ | 8, 9 | ✅ | `490918507` |
| `PA` | Panama | `ruc` | RUC — Registro Único de Contribuyentes | both | ✅ | 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15 | — | `155646463225` |
| `PE` | Peru | `ruc` | RUC — Registro Único de Contribuyentes | both | ✅ | 11 | ✅ | `20100070970` |
| `PH` | Philippines | `tin` | TIN — Taxpayer Identification Number | both | ✅ | 9, 10, 11, 12 | — | `123456789` |
| `PK` | Pakistan | `ntn` | NTN — National Tax Number | both | ✅ | 7, 8 | — | `1234567` |
| `PL` | Poland | `nip` | NIP — Numer Identyfikacji Podatkowej | both | ✅ | 10 | ✅ | `1234563218` |
| `PL` | Poland | `pesel` | PESEL — personal number | natural | — | 11 | ✅ | `44051401359` |
| `PL` | Poland | `regon` | REGON — statistical number | legal | — | 9, 14 | ✅ | `123456785` |
| `PT` | Portugal | `nif` | NIF/NIPC — Número de Identificação Fiscal | both | ✅ | 9 | ✅ | `123456789` |
| `PY` | Paraguay | `ruc` | RUC — Registro Único del Contribuyente | both | ✅ | 5, 6, 7, 8, 9 | ✅ | `800177320` |
| `RO` | Romania | `cnp` | CNP — Cod Numeric Personal | natural | — | 13 | ✅ | `1800101221144` |
| `RO` | Romania | `cui` | CUI/CIF — Cod Unic de Înregistrare | legal | ✅ | 2, 3, 4, 5, 6, 7, 8, 9, 10 | ✅ | `13548146` |
| `RS` | Serbia | `pib` | PIB — Poreski identifikacioni broj | both | ✅ | 9 | ✅ | `100000008` |
| `RU` | Russia | `inn` | ИНН — Taxpayer Identification Number | both | ✅ | 10, 12 | ✅ | `7707083893` |
| `SA` | Saudi Arabia | `vat` | VAT registration number | both | ✅ | 15 | — | `300000000000003` |
| `SE` | Sweden | `vat` | Momsregistreringsnummer — VAT number | legal | ✅ | 12 | ✅ | `556036079301` |
| `SE` | Sweden | `personnummer` | Personnummer | natural | — | 12 | ✅ | `198001011231` |
| `SE` | Sweden | `orgnr` | Organisationsnummer | legal | — | 10 | ✅ | `5560360793` |
| `SG` | Singapore | `uen` | UEN — Unique Entity Number | legal | ✅ | 9, 10 | — | `53012345A` |
| `SI` | Slovenia | `vat` | Davčna številka — tax number | both | ✅ | 8 | ✅ | `15012557` |
| `SK` | Slovakia | `vat` | IČ DPH — VAT number | legal | ✅ | 10 | ✅ | `2020269922` |
| `SK` | Slovakia | `ico` | IČO — organisation number | legal | — | 8 | ✅ | `31234569` |
| `SV` | El Salvador | `nit` | NIT — Número de Identificación Tributaria | both | ✅ | 14 | — | `06140506901121` |
| `TH` | Thailand | `tin` | Taxpayer Identification Number | both | ✅ | 13 | — | `1234567890123` |
| `TN` | Tunisia | `mf` | MF — Matricule Fiscal | both | ✅ | 13 | — | `1234567ABC000` |
| `TR` | Türkiye | `tckn` | T.C. Kimlik No — citizen number | natural | — | 11 | ✅ | `12345678950` |
| `TR` | Türkiye | `vkn` | VKN — Vergi Kimlik Numarası | legal | ✅ | 10 | ✅ | `4540536920` |
| `TW` | Taiwan | `ban` | Business Administration Number | legal | ✅ | 8 | — | `12345675` |
| `UA` | Ukraine | `edrpou` | ЄДРПОУ — legal entity register code | legal | ✅ | 8 | — | `12345678` |
| `UA` | Ukraine | `inn` | ІПН — individual taxpayer number | natural | — | 10 | — | `1234567890` |
| `US` | United States | `itin` | ITIN — Individual Taxpayer Identification Number | natural | — | 9 | ✅ | `970512345` |
| `US` | United States | `ein` | EIN — Employer Identification Number | legal | — | 9 | ✅ | `123456789` |
| `US` | United States | `ssn` | SSN — Social Security Number | natural | — | 9 | ✅ | `123456789` |
| `UY` | Uruguay | `rut` | RUT — Registro Único Tributario | both | ✅ | 12 | ✅ | `211003420017` |
| `VE` | Venezuela | `rif` | RIF — Registro Único de Información Fiscal | both | ✅ | 10 | ✅ | `J305329958` |
| `VN` | Vietnam | `mst` | MST — Mã số thuế | both | ✅ | 10, 13 | — | `0101243150` |
| `XI` | United Kingdom (Northern Ireland) | `vat` | VAT registration number (Northern Ireland Protocol) | legal | ✅ | 9, 12 | ✅ | `123456782` |
| `ZA` | South Africa | `vat` | VAT registration number | legal | ✅ | 10 | — | `4012345678` |
| `ZA` | South Africa | `itr` | Income tax reference number | both | — | 10 | — | `0123456789` |
