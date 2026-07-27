<?php

declare(strict_types=1);

namespace Daycry\TaxId\Database\Seeds;

use CodeIgniter\Database\Seeder;

/**
 * Intentionally empty.
 *
 * The seeder exists so `spark db:seed "Daycry\TaxId\Database\Seeds\TaxEntitiesSeeder"`
 * is a valid command and so an application can extend it — but this package
 * inserts nothing. Shipping a list of real taxpayers would be both a licensing
 * and a privacy problem, and shipping fake ones would silently poison a
 * production lookup table.
 *
 * Populate `tax_entities` from your own source: cached VIES answers, a customer
 * master, or a register you are licensed to hold. See docs/usage.md.
 */
class TaxEntitiesSeeder extends Seeder
{
    public function run(): void
    {
        // No data by design — see the class docblock.
    }
}
