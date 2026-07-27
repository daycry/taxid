<?php

declare(strict_types=1);

namespace Tests\Database;

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Daycry\TaxId\Enums\EntityType;
use Daycry\TaxId\Models\TaxEntityModel;
use Daycry\TaxId\Providers\DatabaseProvider;
use Daycry\TaxId\Registry\Registry;
use Daycry\TaxId\TaxId;

/**
 * The local `tax_entities` table, against the in-memory SQLite database CI4's
 * test bootstrap provides.
 *
 * The table comes from this package's own migration, run through CI4's runner
 * (`$namespace`) rather than instantiated by hand — which also proves the
 * migration itself is discoverable and applies cleanly.
 */
final class DatabaseProviderTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate   = true;
    protected $refresh   = true;
    protected $namespace = 'Daycry\TaxId';

    private TaxEntityModel $model;

    protected function setUp(): void
    {
        parent::setUp();

        $this->model = new TaxEntityModel();
    }

    public function testAKnownTaxpayerIsReturned(): void
    {
        $this->model->insert([
            'country_code' => 'ES',
            'tax_id'       => 'B12345674',
            'name'         => 'Acme SL',
            'address'      => 'Calle Mayor 1',
            'entity_type'  => 'legal',
            'source'       => 'crm',
        ]);

        $entity = (new DatabaseProvider($this->model))->findByTaxId('ES', 'B12345674');

        self::assertNotNull($entity);
        self::assertSame('Acme SL', $entity->name);
        self::assertSame('Calle Mayor 1', $entity->address);
        self::assertSame(EntityType::Legal, $entity->entityType);
        self::assertSame('crm', $entity->resolvedBy, 'The row names its own source.');
    }

    public function testProvenanceFallsBackToTheTableItself(): void
    {
        $this->model->insert(['country_code' => 'ES', 'tax_id' => 'B12345674', 'name' => 'Acme SL']);

        self::assertSame('database', (new DatabaseProvider($this->model))->findByTaxId('ES', 'B12345674')?->resolvedBy);
    }

    public function testAnUnknownTaxpayerIsAMissNotAnError(): void
    {
        self::assertNull((new DatabaseProvider($this->model))->findByTaxId('ES', 'B12345674'));
    }

    public function testTheCountryLookupIsCaseInsensitive(): void
    {
        $this->model->insert(['country_code' => 'ES', 'tax_id' => 'B12345674', 'name' => 'Acme SL']);

        self::assertNotNull((new DatabaseProvider($this->model))->findByTaxId('es', 'B12345674'));
    }

    /**
     * Row present, but no data beyond the identifier. That is still a hit —
     * the taxpayer exists in our records — and every optional field is null.
     */
    public function testASparseRowStillResolves(): void
    {
        $this->model->insert(['country_code' => 'ES', 'tax_id' => 'B12345674']);

        $entity = (new DatabaseProvider($this->model))->findByTaxId('ES', 'B12345674');

        self::assertNotNull($entity);
        self::assertNull($entity->name);
        self::assertNull($entity->address);
        self::assertNull($entity->entityType);
    }

    public function testAnUnrecognisedEntityTypeIsTreatedAsAbsent(): void
    {
        $this->model->insert(['country_code' => 'ES', 'tax_id' => 'B12345674', 'entity_type' => 'nonsense']);

        self::assertNull((new DatabaseProvider($this->model))->findByTaxId('ES', 'B12345674')?->entityType);
    }

    /**
     * A missing row is NOT evidence the taxpayer doesn't exist, so this provider
     * must not be able to produce `registered = false`.
     */
    public function testTheDatabaseNeverClaimsATaxpayerIsUnregistered(): void
    {
        $taxid  = new TaxId(new Registry(), new DatabaseProvider($this->model));
        $result = $taxid->resolve('ESB12345674');

        self::assertTrue($result->isValid());
        self::assertNull($result->isRegistered(), 'An incomplete local table must not create false negatives.');
    }

    public function testEndToEndResolutionThroughTheFacade(): void
    {
        $this->model->insert([
            'country_code' => 'ES',
            'tax_id'       => 'B12345674',
            'name'         => 'Acme SL',
            'entity_type'  => 'legal',
        ]);

        $result = (new TaxId(new Registry(), new DatabaseProvider($this->model)))->resolve('ES B-12345674');

        self::assertTrue($result->isRegistered());
        self::assertSame('Acme SL', $result->name());
        self::assertSame('B12345674', $result->taxId()?->compact);
    }

    public function testTheModelHonoursAnOverriddenTableName(): void
    {
        self::assertSame('tax_entities', $this->getPrivateProperty(new TaxEntityModel(), 'table'));
        self::assertSame('custom_entities', $this->getPrivateProperty(new TaxEntityModel('custom_entities'), 'table'));
    }
}
