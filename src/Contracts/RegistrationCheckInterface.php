<?php

declare(strict_types=1);

namespace Daycry\TaxId\Contracts;

/**
 * Optional, additive companion to {@see LookupProviderInterface} for providers
 * that can answer the harder question: not "do I have data for this ID?" but
 * "is this ID actually registered with the tax authority?".
 *
 * `findByTaxId()` returning `null` is ambiguous by construction — not found and
 * could-not-check look identical from outside. A provider that CAN tell them
 * apart implements this so {@see \Daycry\TaxId\Resolver\Resolver} can report
 * `registered = false` instead of `registered = null`.
 *
 * Only implement it if you genuinely know: a local database missing a row does
 * NOT mean the taxpayer doesn't exist, so
 * {@see \Daycry\TaxId\Providers\DatabaseProvider} deliberately does not.
 * {@see \Daycry\TaxId\Providers\ViesProvider} does — VIES answers authoritatively.
 */
interface RegistrationCheckInterface
{
    /**
     * @return bool|null `true` registered, `false` authoritatively not registered,
     *                   `null` when the question could not be answered.
     */
    public function isRegistered(string $countryCode, string $taxId): ?bool;
}
