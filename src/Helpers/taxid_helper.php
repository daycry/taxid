<?php

declare(strict_types=1);

use Daycry\TaxId\Config\Services;
use Daycry\TaxId\DTO\LookupResult;
use Daycry\TaxId\DTO\ParsedTaxId;
use Daycry\TaxId\DTO\ValidationResult;
use Daycry\TaxId\Enums\TaxIdFormat;
use Daycry\TaxId\TaxId as TaxIdService;

/**
 * Procedural wrappers around `service('taxid')` ({@see \Daycry\TaxId\TaxId}).
 *
 * A CI4 helper: no namespace, every function guarded by `function_exists()` so
 * re-inclusion can never fatal with "Cannot redeclare".
 *
 * Loading: `helper('taxid')` — CI4's locator finds this file through the
 * package's `Daycry\TaxId\` => `src/` PSR-4 mapping, with no `require` and no
 * `Config\Autoload` entry needed (adding `'taxid'` there works too, and makes
 * it always-on rather than on demand).
 *
 * These wrappers differ from the facade in one deliberate way: where the facade
 * takes an explicit `?string $country`, they fall back to
 * {@see \Daycry\TaxId\Config\TaxId::$defaultCountry}. A single-country
 * application can configure it once and stop repeating itself.
 *
 * @see \Daycry\TaxId\Config\Registrar
 */
if (! function_exists('taxid_country_default')) {
    /**
     * The country to use when the caller named none: their argument, else
     * `Config\TaxId::$defaultCountry`, else `null` (meaning "infer from a VAT
     * prefix, or fail with CountryRequired").
     *
     * @internal Shared by the wrappers below; not part of the documented API.
     */
    function taxid_country_default(?string $country): ?string
    {
        if ($country !== null && $country !== '') {
            return $country;
        }

        $configured = Services::config()->defaultCountry;

        return $configured === '' ? null : $configured;
    }
}

if (! function_exists('taxid_validate')) {
    /**
     * Validate a tax ID and return the full result, violations included.
     * Never throws.
     */
    function taxid_validate(string $taxId, ?string $country = null, ?string $scheme = null): ValidationResult
    {
        /** @var TaxIdService $svc */
        $svc = service('taxid');

        return $svc->validate($taxId, taxid_country_default($country), $scheme);
    }
}

if (! function_exists('taxid_is_valid')) {
    /**
     * Boolean validity check. Never throws.
     */
    function taxid_is_valid(string $taxId, ?string $country = null, ?string $scheme = null): bool
    {
        return taxid_validate($taxId, $country, $scheme)->isValid();
    }
}

if (! function_exists('taxid_parse')) {
    /**
     * Parse into a {@see ParsedTaxId}, or `null` when the value is invalid.
     *
     * Unlike the facade's `parse()`, this never throws — it uses `tryParse()`
     * underneath, which is almost always what a template or controller wants.
     */
    function taxid_parse(string $taxId, ?string $country = null): ?ParsedTaxId
    {
        /** @var TaxIdService $svc */
        $svc = service('taxid');

        return $svc->tryParse($taxId, taxid_country_default($country));
    }
}

if (! function_exists('taxid_normalize')) {
    /**
     * The compact form: uppercase, no separators, no VAT prefix.
     */
    function taxid_normalize(string $taxId, ?string $country = null): string
    {
        /** @var TaxIdService $svc */
        $svc = service('taxid');

        return $svc->normalize($taxId, taxid_country_default($country));
    }
}

if (! function_exists('taxid_format')) {
    /**
     * Render a tax ID.
     *
     * `$format` of `null` (the default) uses
     * {@see \Daycry\TaxId\Config\TaxId::$defaultFormat}; the facade keeps its own
     * explicit `Print` default, and the config is consulted here, at the CI4
     * layer.
     */
    function taxid_format(string|ParsedTaxId $taxId, ?TaxIdFormat $format = null, ?string $country = null): string
    {
        /** @var TaxIdService $svc */
        $svc = service('taxid');

        $format ??= match (strtolower(Services::config()->defaultFormat)) {
            'compact'    => TaxIdFormat::Compact,
            'anonymized' => TaxIdFormat::Anonymized,
            default      => TaxIdFormat::Print,
        };

        return $svc->format($taxId, $format, taxid_country_default($country));
    }
}

if (! function_exists('taxid_resolve')) {
    /**
     * Validate, then ask the configured provider what it knows.
     *
     * With the default `NullProvider` the result's `registered` is `null` —
     * "nobody looked" — never `false`.
     */
    function taxid_resolve(string $taxId, ?string $country = null): LookupResult
    {
        /** @var TaxIdService $svc */
        $svc = service('taxid');

        return $svc->resolve($taxId, taxid_country_default($country));
    }
}

if (! function_exists('taxid_country')) {
    /**
     * The country a value belongs to, or `null` if it isn't a valid tax ID.
     */
    function taxid_country(string $taxId, ?string $country = null): ?string
    {
        return taxid_parse($taxId, $country)?->countryCode;
    }
}

if (! function_exists('taxid_scheme')) {
    /**
     * The scheme code a value matched (`nif`, `cif`, `cpf`…), or `null`.
     */
    function taxid_scheme(string $taxId, ?string $country = null): ?string
    {
        return taxid_parse($taxId, $country)?->scheme;
    }
}

if (! function_exists('taxid_name')) {
    /**
     * The taxpayer's registered name, when a provider can supply one.
     *
     * `null` covers every "we don't know" case: invalid ID, no provider
     * configured, VIES unreachable, or the trader having opted out of
     * disclosure.
     */
    function taxid_name(string $taxId, ?string $country = null): ?string
    {
        return taxid_resolve($taxId, $country)->name();
    }
}

if (! function_exists('vat_number')) {
    /**
     * The value as an internationally-quotable VAT number (`ESB12345678`), or
     * `null` when it is not a VAT scheme or the country uses no prefix.
     */
    function vat_number(string $taxId, ?string $country = null): ?string
    {
        return taxid_parse($taxId, $country)?->vatNumber();
    }
}

if (! function_exists('vat_is_registered')) {
    /**
     * Three-state VAT registration check: `true`, `false`, or `null` when
     * nobody could answer.
     *
     * **Never treat `null` as `false`.** It means the lookup did not happen or
     * did not complete — no provider configured, the country is outside VIES, or
     * the service was unreachable. Blocking a customer on `null` blocks them for
     * an outage on someone else's server.
     */
    function vat_is_registered(string $taxId, ?string $country = null): ?bool
    {
        return taxid_resolve($taxId, $country)->isRegistered();
    }
}
