<?php

declare(strict_types=1);

namespace Daycry\TaxId\Contracts;

use Daycry\TaxId\DTO\LookupResult;
use Daycry\TaxId\DTO\ParsedTaxId;

/**
 * Resolver contract: validation plus whatever a provider can add.
 *
 * Implementations MUST validate first and MUST NOT consult a provider for an
 * invalid value — sending garbage to a remote tax authority is both useless
 * and a privacy leak.
 *
 * @see docs/superpowers/specs/2026-07-27-daycry-taxid-v1-design.md §6
 */
interface ResolverInterface
{
    public function resolve(string|ParsedTaxId $taxId, ?string $country = null): LookupResult;
}
