<?php

declare(strict_types=1);

namespace Daycry\TaxId\Models;

use CodeIgniter\Model;

/**
 * The `tax_entities` table behind {@see \Daycry\TaxId\Providers\DatabaseProvider}.
 *
 * This package ships **no taxpayer data** — the migration creates an empty
 * table and the seeder inserts nothing. What goes in it is entirely the
 * application's business: results cached from VIES, an internal customer
 * master, or a national register the organisation is licensed to hold.
 *
 * The table name and connection group are configurable
 * ({@see \Daycry\TaxId\Config\TaxId::$table} / `$dbGroup`) and passed through
 * the constructor, so a consuming app's overrides are honoured.
 */
class TaxEntityModel extends Model
{
    protected $table         = 'tax_entities';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    /** @var list<string> */
    protected $allowedFields = [
        'country_code',
        'tax_id',
        'name',
        'address',
        'entity_type',
        'source',
    ];

    /**
     * @param string|null $table   Overrides {@see $table} when the app configured one.
     * @param string|null $dbGroup Connection group; `null` leaves CI4's
     *                             environment-aware default in place (which is
     *                             what makes the package work under the test
     *                             bootstrap without extra configuration).
     */
    public function __construct(?string $table = null, ?string $dbGroup = null)
    {
        if ($table !== null && $table !== '') {
            $this->table = $table;
        }

        if ($dbGroup !== null && $dbGroup !== '') {
            $this->DBGroup = $dbGroup;
        }

        parent::__construct();
    }

    /**
     * One taxpayer by country and compact tax ID, or `null`.
     *
     * @return array<string, mixed>|null
     */
    public function findByTaxId(string $countryCode, string $taxId): ?array
    {
        /** @var array<string, mixed>|null $row */
        $row = $this->where('country_code', strtoupper($countryCode))
            ->where('tax_id', $taxId)
            ->first();

        return $row;
    }
}
