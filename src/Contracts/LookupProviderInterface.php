<?php

declare(strict_types=1);

namespace Daycry\TaxId\Contracts;

use Daycry\TaxId\DTO\EntityInfo;

/**
 * A source of taxpayer data for an already-valid tax ID.
 *
 * Contract rules every implementation must honour:
 *
 * - Return `null` when the taxpayer is **not found** *or* when the lookup could
 *   not be performed (timeout, service down, unsupported country). The
 *   {@see \Daycry\TaxId\Resolver\Resolver} maps `null` to `registered = null`
 *   ("unknown"), never to `false`, precisely because these two cases are
 *   indistinguishable from here.
 * - Never throw. A network fault is an unknown answer, not an exception.
 * - Never mutate its input.
 *
 * @see \Daycry\TaxId\Providers\NullProvider   the zero-dependency default
 * @see \Daycry\TaxId\Providers\ViesProvider   opt-in EU VAT existence check
 * @see docs/superpowers/specs/2026-07-27-daycry-taxid-v1-design.md §6
 */
interface LookupProviderInterface
{
    /**
     * @param string $countryCode ISO 3166-1 alpha-2, uppercase.
     * @param string $taxId       The compact form (no separators, no VAT prefix).
     */
    public function findByTaxId(string $countryCode, string $taxId): ?EntityInfo;
}
