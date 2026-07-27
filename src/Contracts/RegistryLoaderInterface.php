<?php

declare(strict_types=1);

namespace Daycry\TaxId\Contracts;

/**
 * Source of the raw country/scheme registry data.
 *
 * The default implementation ({@see \Daycry\TaxId\Registry\PhpRegistryLoader})
 * returns the bundled `data/countries.php`; swap in your own to load the same
 * shape from a database, a cache, or an application-specific overlay.
 *
 * @return array<string, array<string, mixed>>
 */
interface RegistryLoaderInterface
{
    /**
     * @return array<string, array<string, mixed>> Raw data keyed by ISO 3166-1 alpha-2 country code.
     */
    public function load(): array;
}
