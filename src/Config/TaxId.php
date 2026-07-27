<?php

declare(strict_types=1);

namespace Daycry\TaxId\Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Publishable configuration for the `daycry/taxid` package.
 *
 * Every property is overridable from `.env` with the `taxid.` prefix
 * (`taxid.provider = vies`), courtesy of {@see BaseConfig}'s environment
 * resolution. Publish a copy into your app with `spark taxid:publish` to change
 * the defaults in code instead.
 *
 * The defaults are deliberately inert: no provider, no database, no network.
 * `service('taxid')` works out of the box and does nothing you didn't ask for.
 *
 * @see \Daycry\TaxId\Config\Services::taxid()
 */
class TaxId extends BaseConfig
{
    /**
     * Where taxpayer data comes from when `resolve()` is called.
     *
     * - `'null'` (default) — nowhere. Validation still works; `resolve()`
     *   reports `registered = null`, i.e. "nobody looked".
     * - `'vies'` — the EU VAT information exchange system. Remote, rate-limited
     *   and EU-only; read {@see \Daycry\TaxId\Providers\ViesProvider} before
     *   enabling it in a request path.
     * - `'database'` — the local `tax_entities` table.
     * - `'chain'` — the database first, then VIES for what it doesn't know.
     *   The sensible production choice, and the reason `ChainProvider` exists.
     * - any FQCN implementing {@see \Daycry\TaxId\Contracts\LookupProviderInterface}.
     */
    public string $provider = 'null';

    /**
     * Format used when a caller doesn't name one: `'compact'`, `'print'` or
     * `'anonymized'`.
     */
    public string $defaultFormat = 'print';

    /**
     * Country assumed when a value carries no VAT prefix and the caller passes
     * none — e.g. `'ES'` for a Spanish-only application, which lets
     * `taxid_is_valid('12345678Z')` work without repeating the country
     * everywhere.
     *
     * `null` (the default) means no assumption: an unqualified value without a
     * prefix produces `CountryRequired` rather than being silently attributed
     * to a country the application never named.
     */
    public ?string $defaultCountry = null;

    /**
     * `Config\Database` connection group for the `tax_entities` table.
     *
     * `null` means "no override", leaving CI4's environment-aware default in
     * place — which is what makes the package work under the testing bootstrap
     * with no extra configuration. Set it only to force a specific group.
     */
    public ?string $dbGroup = null;

    /** Table backing {@see \Daycry\TaxId\Providers\DatabaseProvider}. */
    public string $table = 'tax_entities';

    /**
     * Cache TTL, in seconds, for resolved lookups.
     *
     * - `null` (default) — caching disabled; the provider is left unwrapped.
     * - `0` — wrapped, and `0` is passed through to CI4's cache, which reads it
     *   as *never expires*. This is CI4's convention, not ours.
     * - `> 0` — wrapped with that TTL.
     *
     * Worth enabling whenever VIES is in the chain: it is slow, throttled, and
     * the answer rarely changes within a day.
     */
    public ?int $cacheTtl = null;

    /**
     * VIES SOAP endpoint. Configurable so an application can point at a proxy
     * of its own — useful both for outbound firewall rules and for keeping the
     * Commission's rate limiter happy across many app servers.
     */
    public string $viesEndpoint = 'https://ec.europa.eu/taxation_customs/vies/services/checkVatService';

    /**
     * Request timeout for VIES, in seconds.
     *
     * The default is generous on purpose: VIES forwards the query to the issuing
     * member state, and some of them are slow. A timeout is reported as
     * *unknown*, never as "not registered".
     */
    public int $viesTimeout = 10;
}
