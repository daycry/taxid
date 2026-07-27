<?php

declare(strict_types=1);

namespace Daycry\TaxId\Providers;

use Daycry\TaxId\Contracts\LookupProviderInterface;
use Daycry\TaxId\DTO\EntityInfo;

/**
 * The default provider: looks nothing up, ever.
 *
 * This is what makes the package work out of the box with no database, no
 * network and no configuration — `resolve()` still returns a `LookupResult`,
 * with `registered = null` ("nobody looked") rather than a misleading `false`.
 *
 * Framework-free by design; it is the one provider guaranteed to work outside
 * CodeIgniter 4.
 */
final class NullProvider implements LookupProviderInterface
{
    public function findByTaxId(string $countryCode, string $taxId): ?EntityInfo
    {
        return null;
    }
}
