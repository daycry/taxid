# Data authoring, licensing and privacy

Three decisions about the registry data, and the reasoning behind each.

## The rules are independently authored

`src/Registry/data/countries.php` was written from **publicly documented national formats and
check-digit algorithms** — the sort of thing a tax authority publishes in a specification, a
technical annex, or an invoicing guide.

It is not copied from, nor derived from, any third-party dataset, library or scraped compilation.
That matters twice over. Legally, a compiled dataset can carry its own licence even when the
underlying facts do not, and inheriting one silently would put an obligation on every consumer of
this package. Practically, a copied dataset inherits its source's mistakes with no way to tell which
entries were ever checked.

What is in the file is facts: a length, a character set, a weighting, a modulus. Those are not
anyone's property. The particular arrangement of them here is this package's, MIT-licensed with
everything else.

If you add a country, author it the same way. See [adding-a-country.md](adding-a-country.md).

## The bundled examples are synthetic

Every `examples` entry is a value that is **checksum-valid but not a real taxpayer's identifier**.

This is deliberate, and it is the more important of the two reasons this file exists.

A tax ID identifies a person or a company. Publishing real ones in a public repository — even ones
that appear on invoices, even ones a company prints on its own website — puts them in every clone,
every mirror, every training corpus, forever. There is no version of that which is worth the
convenience.

A synthetic value exercises exactly the same code path. The pattern, the length, the weighting and
the check digit are all tested identically; the only thing lost is the ability to say "this is
Siemens's VAT number", which no test needs to say.

The three exceptions are values that are **published as generic by the authority itself**: Mexico's
`XAXX010101000` and `XEXX010101000` (the SAT's placeholder RFCs for unidentified counterparties), and
the UK's `GD`/`HA` government and health-authority ranges. Those identify nobody.

## No taxpayer data ships with the package

`DatabaseProvider` reads a `tax_entities` table, the migration creates it **empty**, and
`TaxEntitiesSeeder` inserts nothing.

Two reasons. Company registers are usually licensed — some free for any use, some free only for
non-commercial use, some not free at all — and a library cannot make that choice on an application's
behalf. And a table pre-populated with plausible-looking fake data is actively dangerous: it would
answer lookups in production with fiction.

So the table is yours to fill: cached VIES answers, your own customer master, or a register you hold
a licence for. See [usage.md](usage.md#providers).

## VIES

`ViesProvider` queries the European Commission's VAT Information Exchange System, a free public
service. Three things to know before enabling it:

- **Every lookup sends a customer's VAT number to a third party.** That is the point of the service,
  and it is a normal part of intra-EU invoicing, but it is a disclosure and your privacy notice
  should say so.
- **It is rate-limited**, and the Commission is entitled to throttle abusive callers. Cache the
  results (`Config\TaxId::$cacheTtl`) and never loop it over a customer table.
- **Name and address may be withheld.** Traders can opt out of disclosure, in which case VIES returns
  `---`, which this package maps to `null` rather than passing through as data.

Nothing about VIES is enabled by default. `Config\TaxId::$provider` is `'null'` out of the box, and
no test in this repository touches the network.

## Handling tax IDs in your own application

Not this package's job to enforce, but worth saying once:

- A tax ID is personal data when it identifies a natural person, and in most jurisdictions that is
  most of them. GDPR and its equivalents apply.
- Some are legally restricted well beyond ordinary personal data. An Australian TFN may not be used
  as a general identifier; Japan's My Number and Korea's resident registration number are similarly
  constrained. This package validates them because applications legitimately need to — that is not
  an argument for collecting them.
- `TaxIdFormat::Anonymized` exists precisely so a value can appear in a log without the log becoming
  a list of identifiers. See [formatting.md](formatting.md).
- Store the compact form, and think about whether you need to store it at all after the transaction
  it was collected for.

## Licence

The package, including the registry data, is MIT. See [LICENSE](../LICENSE).
