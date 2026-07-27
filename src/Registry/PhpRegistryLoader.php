<?php

declare(strict_types=1);

namespace Daycry\TaxId\Registry;

use Daycry\TaxId\Contracts\RegistryLoaderInterface;

/**
 * Default {@see RegistryLoaderInterface}: the bundled `data/countries.php`.
 *
 * The path is resolved relative to this file, so it works regardless of the
 * caller's current working directory, and the data is a plain `return [...]`
 * array so OPcache keeps it compiled in memory across requests.
 *
 * Intentionally swappable: {@see Registry} accepts any loader, so an
 * application can compile the same shape from a database, a cache, or an
 * overlay that adds its own country.
 */
final class PhpRegistryLoader implements RegistryLoaderInterface
{
    /**
     * @return array<string, array<string, mixed>> Raw rules keyed by ISO 3166-1 alpha-2 country code.
     */
    public function load(): array
    {
        /** @var array<string, array<string, mixed>> $data */
        $data = require __DIR__ . '/data/countries.php';

        return $data;
    }
}
