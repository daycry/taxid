<?php

declare(strict_types=1);

namespace Daycry\TaxId\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Creates the `tax_entities` table used by
 * {@see \Daycry\TaxId\Providers\DatabaseProvider}.
 *
 * The table is created **empty** and stays that way: this package ships no
 * taxpayer data, and populating it is the application's decision — cached VIES
 * answers, an internal customer master, or a register it is licensed to hold.
 *
 * The unique index on `(country_code, tax_id)` is what makes an upsert-style
 * cache safe: one row per taxpayer, whatever wrote it.
 */
class CreateTaxEntitiesTable extends Migration
{
    public function up(): void
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'country_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 2,
                'comment'    => 'ISO 3166-1 alpha-2, uppercase',
            ],
            'tax_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 32,
                'comment'    => 'Compact form: no separators, no VAT prefix',
            ],
            'name' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'address' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'entity_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 16,
                'null'       => true,
                'comment'    => 'natural | legal | both',
            ],
            'source' => [
                'type'       => 'VARCHAR',
                'constraint' => 32,
                'null'       => true,
                'comment'    => 'Provenance, e.g. vies or an internal system name',
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey(['country_code', 'tax_id']);
        $this->forge->createTable('tax_entities', true);
    }

    public function down(): void
    {
        $this->forge->dropTable('tax_entities', true);
    }
}
